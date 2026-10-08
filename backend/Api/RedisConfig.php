<?php
// RedisConfig.php
class RedisConfig {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            try {
                if (!extension_loaded('redis')) {
                    throw new Exception("Redis extension is not loaded in PHP.");
                }

                $redis = new Redis();
                // In production, get these from env variables or a config file
                $host = "127.0.0.1";
                $port = 6379;
                
                // Connect with 2 seconds timeout
                if (!$redis->connect($host, $port, 2.0)) {
                    throw new Exception("Could not connect to Redis at $host:$port");
                }
                
                // Prefix all keys to avoid conflicts with other apps on the same Redis
                $redis->setOption(Redis::OPT_PREFIX, 'bs_queue:');
                
                self::$instance = $redis;
            } catch (Exception $e) {
                error_log("Redis connection error: " . $e->getMessage());
                return null;
            }
        }
        return self::$instance;
    }
}
?>
