<?php
include("header.php");
include("connection.php");

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_mobile) { echo "<div class='text-center p-10'>Please login.</div>"; exit; }

// My Member ID
$me_q = $con->query("SELECT id FROM tbl_members WHERE mobile='$user_mobile'");
$me = $me_q->fetch_assoc();
$my_id = $me['id'];

/*
  Query Strategy:
  1. Get all mutual follows (Connected).
  2. For each, get last message and unread count where chat_platform = 'community'.
  3. Sort by last message time, or name if no message.
*/

$sql = "
    SELECT 
        m.id AS member_id, 
        m.name, 
        m.profile_photo,
        (
            SELECT message 
            FROM tbl_messages 
            WHERE 
                (
                    (sender_id = $my_id AND receiver_id = m.id) 
                    OR 
                    (sender_id = m.id AND receiver_id = $my_id)
                )
                AND chat_platform = 'community'
            ORDER BY id DESC LIMIT 1
        ) AS last_msg,
        (
            SELECT created_at 
            FROM tbl_messages 
            WHERE 
                (
                    (sender_id = $my_id AND receiver_id = m.id) 
                    OR 
                    (sender_id = m.id AND receiver_id = $my_id)
                )
                AND chat_platform = 'community'
            ORDER BY id DESC LIMIT 1
        ) AS last_msg_time,
        (
            SELECT COUNT(*) 
            FROM tbl_messages 
            WHERE sender_id = m.id AND receiver_id = $my_id AND seen = 0 AND chat_platform = 'community'
        ) AS unread_count
    FROM tbl_members m
    JOIN tbl_followers f1 ON (f1.follower_id = $my_id AND f1.following_id = m.id AND f1.status = 'accepted')
    JOIN tbl_followers f2 ON (f2.follower_id = m.id AND f2.following_id = $my_id AND f2.status = 'accepted')
    WHERE m.id != $my_id
    ORDER BY last_msg_time DESC, m.name ASC
";

$res = $con->query($sql);
?>

<main class="flex-1 px-3 md:px-10 py-15 bg-gray-50 md:ml-20 mb-14 md:mb-0">
    <div class="max-w-2xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-orange-700">Messages</h1>
            <div class="text-sm text-gray-500 bg-orange-100 px-3 py-1 rounded-full border border-orange-200">
                Community Chats
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg border border-orange-100 overflow-hidden">
            <?php if($res->num_rows > 0): ?>
                <div class="divide-y divide-gray-100">
                    <?php while($row = $res->fetch_assoc()): 
                        $photo = $row['profile_photo'] ? 'uploads/photo/'.$row['profile_photo'] : 'https://via.placeholder.com/150';
                    ?>
                        <a href="message.php?receiver_id=<?= $row['member_id'] ?>&platform=community" 
                           class="flex items-center gap-4 p-4 hover:bg-orange-50 transition-all group">
                            
                            <!-- Profile Photo -->
                            <div class="relative">
                                <img src="<?= $photo ?>" 
                                     class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-md group-hover:border-orange-300 transition-all">
                                <?php if($row['unread_count'] > 0): ?>
                                    <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full border-2 border-white">
                                        <?= $row['unread_count'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <!-- Content -->
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h3 class="font-bold text-gray-800 text-lg group-hover:text-orange-700 transition-colors">
                                        <?= htmlspecialchars($row['name']) ?>
                                    </h3>
                                    <?php if($row['last_msg_time']): ?>
                                        <span class="text-[10px] text-gray-400">
                                            <?= date('d M, h:i A', strtotime($row['last_msg_time'])) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-sm <?= $row['unread_count'] > 0 ? 'text-gray-900 font-semibold' : 'text-gray-500' ?> truncate mt-0.5">
                                    <?= $row['last_msg'] ? htmlspecialchars($row['last_msg']) : '<span class="italic text-gray-400">Say Hi!</span>' ?>
                                </p>
                            </div>

                            <!-- Arrow -->
                            <i class="fa-solid fa-chevron-right text-gray-300 group-hover:text-orange-400 px-2 transition-all"></i>
                        </a>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="fa-solid fa-comments text-3xl text-orange-500"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-800">No Conversations Yet</h3>
                    <p class="text-gray-500 text-sm mt-2 max-w-xs mx-auto">
                        Connect with people from the community to start messaging. 
                        Mutual follows will appear here.
                    </p>
                    <a href="index.php" class="mt-6 inline-block bg-orange-500 text-white px-6 py-2 rounded-full font-bold shadow-md hover:bg-orange-600 transition">
                        Explore Community
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<style>
    /* Smooth hover effect for list items */
    .group:hover img {
        transform: scale(1.05);
    }
</style>

