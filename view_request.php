<?php
include("connection.php");
include("header.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){
    echo "<div class='text-center text-red-500 mt-10'>Please login to continue.</div>";
    exit;
}

// Current user's marriage profile ID
$user_profile = $con->query("
    SELECT mp.id AS profile_id
    FROM tbl_marriage_profiles mp
    INNER JOIN tbl_members m ON m.id = mp.user_id
    WHERE m.email='$user_email' LIMIT 1
")->fetch_assoc();

$my_profile_id = $user_profile['profile_id'] ?? 0;

if(!$my_profile_id){
    echo '
    <div class="max-w-lg mx-auto mt-10 p-6 bg-red-50 border border-red-300 rounded-xl shadow-md text-center">
        <div class="text-red-600 text-4xl mb-3">
            <i class="fa fa-exclamation-circle"></i>
        </div>
        <h2 class="text-xl font-bold text-red-700 mb-2">Marriage Profile Not Found</h2>
        <p class="text-red-600 text-sm mb-4">You need to create your marriage profile first.</p>
        <a href="add_marriage_profile.php"
           class="inline-block bg-orange-600 hover:bg-orange-700 text-white font-semibold px-6 py-2 rounded-lg shadow">
            Create Marriage Profile
        </a>
    </div>';
    exit;
}


// ACCEPT / REJECT / REMOVE
if(isset($_GET['action'], $_GET['pid'])){
    $pid = intval($_GET['pid']);
    $action = $_GET['action'];

    if($action=='accepted'){
        $con->query("UPDATE tbl_proposals SET status='friend' WHERE id='$pid' AND receiver_id='$my_profile_id'");
    }elseif($action=='rejected'){
        $con->query("DELETE FROM tbl_proposals WHERE id='$pid' AND receiver_id='$my_profile_id'");
    }

    echo "<script>window.location='view_request.php';</script>";
    exit;
}


// FETCH ALL REQUESTS RECEIVED
$query = "
SELECT 
    p.id AS proposal_id,
    p.status,
    mp.id AS profile_id,
    mp.full_name,
    mp.city,
    mp.education,
    mp.photo,
    mp.caste,
    mp.status AS marital_status,
    mp.occupation,
    TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age
FROM tbl_proposals p
INNER JOIN tbl_marriage_profiles mp ON mp.id = p.sender_id
WHERE p.receiver_id='$my_profile_id'
ORDER BY p.id DESC
";

$result = $con->query($query);
?>

<main class="flex-1 px-2 md:px-10 py-15 bg-white md:ml-20 mb-10 max-w-8xl overflow-hidden">
  <div class="w-full">
    <h2 class="font-extrabold text-2xl text-orange-700 mb-6 flex items-center gap-2">
      <i class="fa fa-envelope-open-text"></i> Received Requests
    </h2>

    <?php if($result && $result->num_rows>0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

        <?php while($req = $result->fetch_assoc()): 

            $photo = !empty($req['photo']) 
                    ? "uploads/photo/".$req['photo'] 
                    : "https://via.placeholder.com/100";
        ?>

        <div class="bg-white rounded-2xl shadow-xl border border-orange-200 flex flex-col items-center p-6 gap-2">
            
            <img src="<?= $photo ?>" class="w-16 h-16 rounded-full border-4 border-orange-400 object-cover mb-1" />

            <div class="font-bold text-orange-700 text-lg text-center">
                <?= htmlspecialchars($req['full_name']); ?>
            </div>

            <div class="text-gray-600 text-sm mb-1 text-center">
                <i class="fa fa-calendar-day"></i> <?= $req['age']; ?> yrs 
                | <i class="fa fa-graduation-cap"></i> <?= htmlspecialchars($req['education']); ?>
            </div>

            <div class="text-gray-600 text-xs text-center mb-1">
                <i class="fa fa-location-dot"></i> <?= htmlspecialchars($req['city']); ?>
            </div>

            <div class="text-gray-600 text-xs mb-1">
                <i class="fa fa-users"></i> <?= htmlspecialchars($req['caste']); ?>
            </div>

            <div class="px-3 py-0.5 bg-orange-100 text-orange-700 rounded-full font-bold text-xs">
                <?= htmlspecialchars($req['marital_status']); ?>
            </div>
            
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
            <!-- ACTION BUTTONS -->
            <div class="flex gap-2 w-full mt-2">

                <!-- VIEW -->
                <a href="view_marriage_profile.php?id=<?= $req['profile_id'] ?>" 
                   class="flex-1 bg-orange-50 border border-orange-300 text-orange-700 font-semibold rounded-lg py-1 text-xs hover:bg-orange-100 transition flex items-center justify-center gap-1">
                   <i class="fa fa-eye"></i> View
                </a>

                <?php if($req['status']=='pending'): ?>

                <!-- ACCEPT -->
                <a href="?action=accepted&pid=<?= $req['proposal_id'] ?>" 
                   class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                   <i class="fa fa-check"></i> Accept
                </a>

                <!-- REJECT -->
                <a href="?action=rejected&pid=<?= $req['proposal_id'] ?>" 
                   onclick="return confirm('Reject this request?');"
                   class="flex-1 bg-red-100 hover:bg-red-200 text-red-600 font-bold border border-red-300 rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                   <i class="fa fa-times"></i> Reject
                </a>

                <?php else: ?>

                <!-- CHAT -->
                <a href="message.php?sender_id=<?= $my_profile_id ?>&receiver_id=<?= $req['profile_id'] ?>"
                   class="flex-1 bg-green-600 hover:bg-green-700 text-white font-bold rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                   <i class="fa fa-comments"></i> Chat
                </a>

                <!-- REMOVE -->
                <a href="?action=rejected&pid=<?= $req['proposal_id'] ?>"
                   onclick="return confirm('Remove this connection?');"
                   class="flex-1 bg-red-100 hover:bg-red-200 text-red-600 font-bold border border-red-300 rounded-lg py-1 text-xs flex items-center justify-center gap-1">
                   <i class="fa fa-trash"></i> Remove
                </a>

                <?php endif; ?>
            </div>

        </div>

        <?php endwhile; ?>
    </div>

    <?php else: ?>
        <div class="text-center text-gray-500 mt-10 text-sm">
            No requests received yet.
        </div>
    <?php endif; ?>

  </div>
</main>
