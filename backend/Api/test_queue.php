<?php
require_once __DIR__ . '/QueueManager.php';

echo "1. Testing standard job push...\n";
$jobId1 = QueueManager::pushJob('test_job', ['message' => 'Hello World!']);
echo "Result Job ID: " . ($jobId1 ? $jobId1 : 'FAILED') . "\n";

echo "\n2. Testing deduplication (first attempt, should succeed)...\n";
$dedupKey = 'user_123_action_xyz';
$jobId2 = QueueManager::pushJob('test_job', ['message' => 'First attempt'], $dedupKey);
echo "Result Job ID: " . ($jobId2 ? $jobId2 : 'FAILED (Duplicate)') . "\n";

echo "\n3. Testing deduplication (second attempt, same key, should fail)...\n";
$jobId3 = QueueManager::pushJob('test_job', ['message' => 'Second attempt (duplicate)'], $dedupKey);
echo "Result Job ID: " . ($jobId3 ? $jobId3 : 'FAILED (Duplicate) - AS EXPECTED') . "\n";

echo "\n4. Testing worker failure handling and retry...\n";
$jobId4 = QueueManager::pushJob('test_job', ['fail_simulate' => true]);
echo "Result Job ID: " . ($jobId4 ? $jobId4 : 'FAILED') . " (Will fail 3 times and move to failed queue)\n";

echo "\nAll tests queued!\n";
echo "Run 'php worker.php' in the terminal to observe the worker processing, retrying, and handling duplicates.\n";
?>
