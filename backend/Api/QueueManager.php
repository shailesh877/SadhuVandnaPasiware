<?php
// QueueManager.php
require_once __DIR__ . '/RedisConfig.php';

class QueueManager {
    const QUEUE_PENDING = 'queue:pending';
    const QUEUE_PROCESSING = 'queue:processing';
    const QUEUE_FAILED = 'queue:failed';
    const QUEUE_NOTIFICATIONS = 'queue:notifications';

    /**
     * Pushes a new job to the queue.
     * 
     * @param string $jobType
     * @param array $payload
     * @param string|null $deduplicationKey Optional key to prevent duplicate jobs
     * @return string|false Job ID on success, false on failure (or duplicate)
     */
    public static function pushJob($jobType, $payload, $deduplicationKey = null, $dedupTtl = 300) {
        $redis = RedisConfig::getConnection();
        if (!$redis) return false;

        if ($deduplicationKey) {
            $dedupKey = "dedup:{$jobType}:{$deduplicationKey}";
            // Set deduplication key. nx = only if not exists, ex = expire in seconds
            $isNew = $redis->set($dedupKey, 1, ['nx', 'ex' => $dedupTtl]);
            if (!$isNew) {
                return false; // Duplicate job blocked
            }
        }

        $jobId = uniqid('job_', true);
        $jobData = [
            'id' => $jobId,
            'type' => $jobType,
            'payload' => $payload,
            'deduplication_key' => $deduplicationKey ? "dedup:{$jobType}:{$deduplicationKey}" : null,
            'attempts' => 0,
            'created_at' => time()
        ];

        // Push to left of pending list
        $pushed = $redis->lPush(self::QUEUE_PENDING, json_encode($jobData));
        return $pushed ? $jobId : false;
    }

    /**
     * Pops a batch of jobs for efficient bulk processing.
     * Moving them atomically to the processing queue is difficult with a batch,
     * so we use a Lua script for atomic batch movement.
     */
    public static function popBatch($batchSize = 100) {
        $redis = RedisConfig::getConnection();
        if (!$redis) return [];

        $lua = "
            local pending = KEYS[1]
            local processing = KEYS[2]
            local count = tonumber(ARGV[1])
            local jobs = {}
            for i = 1, count do
                local job = redis.call('RPOP', pending)
                if not job then break end
                redis.call('LPUSH', processing, job)
                table.insert(jobs, job)
            end
            return jobs
        ";

        $result = $redis->eval($lua, [self::QUEUE_PENDING, self::QUEUE_PROCESSING, $batchSize], 2);
        
        $jobs = [];
        if ($result && is_array($result)) {
            foreach ($result as $jobJson) {
                $jobData = json_decode($jobJson, true);
                if ($jobData) {
                    $jobs[] = [
                        'json' => $jobJson,
                        'data' => $jobData
                    ];
                }
            }
        }
        return $jobs;
    }

    /**
     * Removes a processed job from the processing queue.
     */
    public static function finalizeJob($jobJson) {
        $redis = RedisConfig::getConnection();
        if ($redis) {
            $redis->lRem(self::QUEUE_PROCESSING, $jobJson, 1);
        }
    }

    /**
     * Re-queues a failed job for retry.
     */
    public static function requeueJob($jobData, $oldJobJson) {
        $redis = RedisConfig::getConnection();
        if (!$redis) return;
        
        $redis->lRem(self::QUEUE_PROCESSING, $oldJobJson, 1);
        unset($jobData['started_at']);
        $redis->rPush(self::QUEUE_PENDING, json_encode($jobData));
    }

    /**
     * Marks a job as failed and moves it to the failed queue.
     */
    public static function failJob($jobData, $error) {
        $redis = RedisConfig::getConnection();
        if (!$redis) return false;
        
        $jobData['error'] = $error;
        $jobData['failed_at'] = time();
        
        $redis->lPush(self::QUEUE_FAILED, json_encode($jobData));
        return true;
    }
    
    /**
     * Recovery function for orphaned jobs in processing queue.
     * This moves jobs that have been stuck in 'processing' for too long back to 'pending'.
     * We assume a job shouldn't take more than $timeoutSeconds to process.
     */
    public static function recoverOrphanedJobs($timeoutSeconds = 300) {
        $redis = RedisConfig::getConnection();
        if (!$redis) return 0;
        
        $processingJobs = $redis->lRange(self::QUEUE_PROCESSING, 0, -1);
        $recovered = 0;
        
        foreach ($processingJobs as $jobJson) {
            $jobData = json_decode($jobJson, true);
            if ($jobData) {
                // If started_at is not set, use created_at as fallback
                $startedAt = $jobData['started_at'] ?? $jobData['created_at'];
                
                if (time() - $startedAt > $timeoutSeconds) {
                    // Job took too long or worker crashed. Remove from processing.
                    $redis->lRem(self::QUEUE_PROCESSING, $jobJson, 1);
                    
                    // Increment attempts, update timestamp, and requeue
                    $jobData['attempts'] = ($jobData['attempts'] ?? 0) + 1;
                    unset($jobData['started_at']); // Clear started_at for fresh start
                    
                    if ($jobData['attempts'] >= 3) { // Hardcode 3 max retries for recovery
                        self::failJob($jobData, "Failed: Orphaned job recovered too many times.");
                    } else {
                        // Push back to right (or left) of pending queue
                        $redis->rPush(self::QUEUE_PENDING, json_encode($jobData));
                    }
                    $recovered++;
                }
            } else {
                // Invalid JSON, clean up
                $redis->lRem(self::QUEUE_PROCESSING, $jobJson, 1);
            }
        }
        
        return $recovered;
    }
}
?>
