<?php
include("connection.php");
include("header.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){
    echo "<div class='text-center text-red-500 mt-10'>Please login to continue.</div>";
    exit;
}

// Fetch current user's marriage profile
$user_profile = $con->query("
    SELECT mp.id AS profile_id 
    FROM tbl_marriage_profiles mp
    INNER JOIN tbl_members m ON m.id = mp.user_id
    WHERE m.email='$user_email' LIMIT 1
")->fetch_assoc();

$my_profile_id = $user_profile['profile_id'] ?? 0;

if(!$my_profile_id){
    echo "
    <div class='max-w-lg mx-auto mt-10 p-6 bg-red-50 border border-red-300 rounded-xl shadow-md text-center'>
        <div class='text-red-600 text-4xl mb-3'>
            <i class='fa fa-exclamation-circle'></i>
        </div>
        <h2 class='text-xl font-bold text-red-700 mb-2'>Marriage Profile Not Found</h2>
        <p class='text-red-600 text-sm mb-4'>Please create your marriage profile first.</p>
        <a href='add_marriage_profile.php' class='bg-orange-600 text-white px-6 py-2 rounded-lg shadow'>Create Profile</a>
    </div>
    <script>setTimeout(()=>window.location='add_marriage_profile.php',1500);</script>
    ";
    exit;
}

// Cancel Proposal
if(isset($_GET['cancel'])){
    $pid = intval($_GET['cancel']);
    $con->query("DELETE FROM tbl_proposals WHERE id='$pid' AND sender_id='$my_profile_id'");
    echo "<script>window.location='send_request';</script>";
    exit;
}

// Reject After Becoming Friend
if(isset($_GET['reject_friend'])){
    $pid = intval($_GET['reject_friend']);
    $con->query("DELETE FROM tbl_proposals WHERE id='$pid' AND sender_id='$my_profile_id'");
    echo "<script>window.location='send_request';</script>";
    exit;
}

// Fetch proposals sent by me
$query = "
SELECT 
    p.id AS proposal_id,
    p.status,
    mp.id AS receiver_profile_id,
    mp.full_name,
    mp.city,
    mp.education,
    mp.status AS marital_status,
    mp.caste,
    mp.photo,
    TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
FROM tbl_proposals p
INNER JOIN tbl_marriage_profiles mp ON mp.id = p.receiver_id
WHERE p.sender_id='$my_profile_id'
ORDER BY p.id DESC
";

$result = $con->query($query);
?>

<main class="flex-1 px-2 md:px-10 py-15 bg-white md:ml-20 max-w-8xl overflow-hidden">

    <h2 class="font-extrabold text-2xl text-orange-700 mb-6 flex items-center gap-2">
        <i class="fa fa-paper-plane"></i> Sent Proposals
    </h2>

    <?php if($result && $result->num_rows>0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

        <?php while($req = $result->fetch_assoc()):
            $photo = !empty($req['photo']) ? "uploads/photo/".$req['photo'] : "https://via.placeholder.com/100";
        ?>

        <div class="bg-white rounded-2xl shadow-xl border border-orange-200 flex flex-col items-center p-6 gap-2">

            <img src="<?= $photo ?>" class="w-16 h-16 rounded-full border-4 border-orange-400 object-cover mb-1" />

            <div class="font-bold text-orange-700 text-lg text-center"><?= $req['full_name'] ?></div>

            <div class="text-gray-600 text-sm text-center">
                <i class="fa fa-calendar"></i> <?= $req['age'] ?> yrs |
                <i class="fa fa-graduation-cap"></i> <?= $req['education'] ?>
            </div>

            <div class="text-gray-500 text-xs">
                <i class="fa fa-location-dot"></i> <?= $req['city'] ?>
            </div>

            <div class="px-3 py-0.5 bg-orange-100 text-orange-700 rounded-full text-xs font-bold">
                <?= $req['marital_status'] ?>
            </div>

            <div class="mt-3 w-full">

                <!-- Status Labels -->
                <?php if($req['status'] == 'pending'): ?>
                    <div class="text-yellow-600 text-sm font-bold text-center mb-2">
                        <i class="fa fa-clock"></i> Pending Approval
                    </div>
                <?php elseif($req['status'] == 'friend'): ?>
                    <div class="text-green-600 text-sm font-bold text-center mb-2">
                        <i class="fa fa-check-circle"></i> Connected
                    </div>
                <?php endif; ?>

                <div class="flex gap-2">

                    <a href="view_marriage_profile.php?id=<?= $req['receiver_profile_id'] ?>"
                        class="flex-1 bg-orange-50 border border-orange-300 text-orange-700 font-semibold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                        <i class="fa fa-eye"></i> View
                    </a>

                    <?php if($req['status']=='pending'): ?>
                        <a href="?cancel=<?= $req['proposal_id'] ?>"
                            class="flex-1 bg-red-100 border border-red-300 text-red-600 font-bold rounded-lg py-1 text-xs">
                            <i class="fa fa-times"></i> Cancel
                        </a>

                    <?php elseif($req['status']=='friend'): ?>
                        <a href="message.php?sender_id=<?= $my_profile_id ?>&receiver_id=<?= $req['receiver_profile_id'] ?>"
                            class="flex-1 bg-green-600 text-white rounded-lg py-1 text-xs font-bold">
                            <i class="fa fa-comments"></i> Message
                        </a>

                        <a href="?reject_friend=<?= $req['proposal_id'] ?>"
                            class="flex-1 bg-red-50 border border-red-300 text-red-600 rounded-lg py-1 text-xs font-bold">
                            <i class="fa fa-user-minus"></i> Remove
                        </a>
                    <?php endif; ?>

                </div>

            </div>
        </div>

        <?php endwhile; ?>
    </div>

    <?php else: ?>
        <div class="text-center text-gray-500 mt-10">No proposals sent yet.</div>
    <?php endif; ?>

</main>
