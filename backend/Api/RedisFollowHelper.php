<?php
require_once 'RedisConfig.php';

class RedisFollowHelper {
    public static function getStatus($redis, $con, $follower_id, $following_id) {
        if (!$redis) return false;
        
        $key = "user:{$follower_id}:following";
        $loadedKey = "user:{$follower_id}:following_loaded";
        
        if (!$redis->exists($loadedKey)) {
            $q = $con->query("SELECT following_id, status FROM tbl_followers WHERE follower_id=$follower_id");
            if ($q && $q->num_rows > 0) {
                $data = [];
                while($r = $q->fetch_assoc()) {
                    $data[$r['following_id']] = $r['status'];
                }
                $redis->hMSet($key, $data);
            }
            $redis->set($loadedKey, 1, ['ex' => 86400]);
            $redis->expire($key, 86400);
        }
        
        return $redis->hGet($key, $following_id);
    }
    
    public static function getCounts($redis, $con, $uid) {
        if (!$redis) return false;
        
        $countsKey = "user:{$uid}:counts";
        if (!$redis->exists($countsKey)) {
            $friends = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$uid AND status='accepted'")->fetch_row()[0];
            $followers = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$uid")->fetch_row()[0];
            $following = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE follower_id=$uid")->fetch_row()[0];
            $requested = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE following_id=$uid AND status='pending'")->fetch_row()[0];
            $sent = $con->query("SELECT COUNT(*) FROM tbl_followers WHERE follower_id=$uid AND status='pending'")->fetch_row()[0];
            $posts = $con->query("SELECT COUNT(*) FROM tbl_posts WHERE user_id=$uid")->fetch_row()[0];
            
            $counts = [
                'friends' => $friends,
                'followers' => $followers,
                'following' => $following,
                'requested' => $requested,
                'sent' => $sent,
                'posts' => $posts
            ];
            $redis->hMSet($countsKey, $counts);
            $redis->expire($countsKey, 86400);
            return $counts;
        }
        
        return $redis->hGetAll($countsKey);
    }
}
?>
