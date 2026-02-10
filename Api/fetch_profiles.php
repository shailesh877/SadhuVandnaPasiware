<?php
include("connection.php");
session_start();

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email) die("Unauthorized access");

// Current user's marriage profile ID
$my_profile = $con->query("
    SELECT mp.id AS profile_id
    FROM tbl_marriage_profiles mp
    INNER JOIN tbl_members m ON m.id = mp.user_id
    WHERE m.email='$user_email' LIMIT 1
")->fetch_assoc();

$my_profile_id = $my_profile['profile_id'] ?? 0;

// Filters
$gender = $_POST['gender'] ?? '';
$ageRange = $_POST['age'] ?? '';
$city = trim($_POST['city'] ?? '');
$education = trim($_POST['education'] ?? '');

// Base Query
$query = "
SELECT mp.*, TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
FROM tbl_marriage_profiles mp
JOIN tbl_members m ON m.id = mp.user_id
WHERE m.status != 'Blocked' AND mp.id != '$my_profile_id'
";

// Apply Filters
if($gender) $query .= " AND gender='$gender'";
if($city) $query .= " AND city LIKE '%$city%'";
if($education) $query .= " AND education LIKE '%$education%'";
if($ageRange){
    $range = explode('-',$ageRange);
    if(count($range)==2){
        $min = (int)$range[0];
        $max = (int)$range[1];
        $query .= " AND TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) BETWEEN $min AND $max";
    }
}

$result = $con->query($query);
if(!$result || $result->num_rows==0){
    echo "<div class='text-center text-gray-500 mt-10'>No profiles found.</div>";
    exit;
}
?>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
<?php while($row = $result->fetch_assoc()):
    $photo = !empty($row['photo']) ? "uploads/photo/".$row['photo'] : "https://via.placeholder.com/150";

    // 🔎 Proposal Status from tbl_proposals
    $proposal_status = null;
    $proposal_check = $con->query("
        SELECT status, sender_id, receiver_id 
        FROM tbl_proposals
        WHERE (sender_id='$my_profile_id' AND receiver_id='".$row['id']."')
           OR (sender_id='".$row['id']."' AND receiver_id='$my_profile_id')
        ORDER BY id DESC LIMIT 1
    ");

    if($proposal_check && $proposal_check->num_rows>0){
        $p = $proposal_check->fetch_assoc();
        $proposal_status = strtolower($p['status']); // pending / accepted / friend / rejected
        $is_sender = ($p['sender_id'] == $my_profile_id);
    }
?>
<div class="bg-white rounded-2xl shadow-xl p-6 flex flex-col items-center border border-orange-200 gap-2">
    <img src="<?= $photo ?>" class="w-24 h-24 rounded-full border-4 border-orange-400 object-cover mb-2">
    <div class="font-bold text-orange-700 text-lg text-center"><?= htmlspecialchars($row['full_name']) ?></div>
    <div class="text-gray-600 text-sm text-center"><?= $row['age'] ?> yrs | <?= htmlspecialchars($row['education']) ?></div>
    <div class="text-gray-500 text-xs text-center"><?= htmlspecialchars($row['city']) ?></div>
    <span class="px-2 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs mb-2"><?= htmlspecialchars($row['status']) ?></span>

    <div class="flex gap-2 w-full">
        <a href="view_marriage_profile.php?id=<?= $row['id'] ?>" class="flex-1 bg-orange-50 border border-orange-300 text-orange-700 font-semibold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
            <i class="fa fa-eye"></i> View
        </a>

        <?php if(!$proposal_status || $proposal_status=='rejected'): ?>
            <a href="send_proposal.php?to=<?= $row['id'] ?>&profile_id=<?= $row['id'] ?>" class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                <i class="fa fa-paper-plane"></i> Send Proposal
            </a>
        <?php elseif($proposal_status=='pending'): ?>
            <?php if($is_sender): ?>
                <button disabled class="flex-1 bg-gray-300 text-gray-600 font-bold rounded-lg py-1 text-xs flex items-center justify-center gap-1 cursor-not-allowed">
                    <i class="fa fa-clock"></i> Requested
                </button>
            <?php else: ?>
                <a href="view_request.php" class="flex-1 bg-yellow-400 hover:bg-yellow-500 text-white font-bold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                    <i class="fa fa-handshake"></i> Accept
                </a>
            <?php endif; ?>
        <?php elseif($proposal_status=='accepted' || $proposal_status=='friend'): ?>
            <a href="message.php?sender_id=<?= $my_profile_id ?>&receiver_id=<?= $row['id'] ?>" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                <i class="fa fa-comments"></i> Message
            </a>
        <?php endif; ?>
    </div>
</div>
<?php endwhile; ?>
</div>
