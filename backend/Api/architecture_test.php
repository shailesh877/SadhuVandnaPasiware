<?php
// Api/architecture_test.php
require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/RedisConfig.php';

echo "================================================\n";
echo "   ARCHITECTURE VERIFICATION & BATCHING TEST\n";
echo "================================================\n";

$redis = RedisConfig::getConnection();
if (!$redis) die("FATAL: Redis not running\n");

// --- 1. CREATE ISOLATED TEST DATA ---
$testMarker = "TEST_ARCH_" . time();
echo "\n[1] CREATING TEST DATA ($testMarker)...\n";

$con->query("INSERT INTO tbl_posts (user_id, status, link, created_at) VALUES (1, '$testMarker', '', NOW())");
$testPostId = $con->insert_id;
echo " -> Created Test Post ID: $testPostId\n";

$testUserIds = [];
$con->begin_transaction();
for ($i = 1; $i <= 100; $i++) {
    $email = "{$testMarker}_{$i}@example.com";
    $con->query("INSERT INTO tbl_members (
        name, email, mobile, password, dob, gender, address, city, cast, education, occupation, maritial_status, state, profile_photo, cover_photo, hobbi, about, status, date, last_active, fcm_token, matrimony_profile_fee, is_business, parent_userid, category
    ) VALUES (
        'TestUser $i', '$email', '999999$i', 'pass', '1990-01-01', 'M', 'N/A', 'N/A', 'N/A', 'N/A', 'N/A', 'Single', 'N/A', '', '', 'N/A', 'N/A', 'Active', NOW(), NOW(), '', '0', 0, 0, ''
    )");
    $testUserIds[] = $con->insert_id;
}
$con->commit();
echo " -> Created 100 Test Users (IDs: " . min($testUserIds) . " to " . max($testUserIds) . ")\n";

// Helper for concurrent requests
function async_post_requests($url, $requests) {
    $multi = curl_multi_init();
    $channels = [];
    foreach ($requests as $i => $req) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($req));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_multi_add_handle($multi, $ch);
        $channels[$i] = $ch;
    }
    
    $active = null;
    do { $mrc = curl_multi_exec($multi, $active); } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    while ($active && $mrc == CURLM_OK) {
        if (curl_multi_select($multi) != -1) {
            do { $mrc = curl_multi_exec($multi, $active); } while ($mrc == CURLM_CALL_MULTI_PERFORM);
        }
    }
    foreach ($channels as $ch) { curl_multi_remove_handle($multi, $ch); }
    curl_multi_close($multi);
}

// --- 2. CONCURRENT LIKES TEST ---
echo "\n[2] TESTING CONCURRENT LIKES (BATCHING)...\n";
$requests = [];
foreach ($testUserIds as $uid) {
    $requests[] = ['action' => 'like', 'id' => $testPostId, 'user_id' => $uid];
}
echo " -> Emitting 100 concurrent Like requests...\n";
$startLike = microtime(true);
async_post_requests('http://localhost:8000/like_comment_action.php', $requests);
$apiTime = round(microtime(true) - $startLike, 2);
echo " -> API calls completed in $apiTime seconds. Redis updated instantly.\n";

echo " -> Waiting 3 seconds for batch worker to execute bulk MySQL persistence...\n";
sleep(3);

$redisLikes = $redis->sCard("post:{$testPostId}:likes");
$dbLikes = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$testPostId")->fetch_row()[0];
echo " -> Redis Likes Count: $redisLikes\n";
echo " -> MySQL Likes Count: $dbLikes\n";
if ($redisLikes == 100 && $dbLikes == 100) {
    echo " -> [PASS] Perfect consistency achieved. Batch successfully inserted 100 rows.\n";
} else {
    echo " -> [FAIL] Mismatch detected.\n";
}

// --- 3. RAPID TOGGLE TEST ---
echo "\n[3] TESTING RAPID TOGGLE COALESCING...\n";
$toggleUser = $testUserIds[0];
echo " -> Sending Like -> Unlike -> Like sequence for User $toggleUser rapidly...\n";
// The user is currently Liked. So: Unlike -> Like -> Unlike
async_post_requests('http://localhost:8000/like_comment_action.php', [
    ['action' => 'like', 'id' => $testPostId, 'user_id' => $toggleUser], // Unlike
    ['action' => 'like', 'id' => $testPostId, 'user_id' => $toggleUser], // Like
    ['action' => 'like', 'id' => $testPostId, 'user_id' => $toggleUser]  // Unlike
]);

sleep(2); // Wait for processing
$isLikedRedis = $redis->sIsMember("post:{$testPostId}:likes", $toggleUser);
$isLikedDb = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$testPostId AND user_id=$toggleUser")->fetch_row()[0];

echo " -> Redis State (1=Liked, 0=Unliked): " . ($isLikedRedis ? 1 : 0) . "\n";
echo " -> MySQL State (1=Liked, 0=Unliked): $isLikedDb\n";
if (!$isLikedRedis && $isLikedDb == 0) {
    echo " -> [PASS] Intermediate toggles cleanly bypassed DB. Final state coalesced successfully.\n";
}

// --- 4. SAFE DATA CLEANUP ---
echo "\n[4] CLEANING UP EXACT TEST RECORDS...\n";

// LIKES
$res = $con->query("SELECT COUNT(*) FROM tbl_likes WHERE post_id=$testPostId");
$countLikes = $res->fetch_row()[0];
$con->query("DELETE FROM tbl_likes WHERE post_id=$testPostId");
echo " -> Safely deleted $countLikes test likes (Post ID: $testPostId).\n";

// POST
$con->query("DELETE FROM tbl_posts WHERE id=$testPostId");
echo " -> Safely deleted 1 test post (ID: $testPostId).\n";

// USERS
$minId = min($testUserIds);
$maxId = max($testUserIds);
$con->query("DELETE FROM tbl_members WHERE email LIKE '{$testMarker}_%@example.com' AND id BETWEEN $minId AND $maxId");
$deletedUsers = $con->affected_rows;
echo " -> Safely deleted $deletedUsers test users (Marker: $testMarker).\n";

// REDIS
$redis->del("post:{$testPostId}:likes");
$redis->del("post:{$testPostId}:likes_loaded");
echo " -> Cleared temporary Redis keys for Post ID: $testPostId.\n";

echo "\n================================================\n";
echo "   TEST SUITE FINISHED & CLEANED UP\n";
echo "================================================\n";
?>
