<?php
include 'headers.php';
include 'connection.php';

// This handles the actual joining logic when called from the app
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Support both JSON and POST (for flexibility)
$invite_code = $con->real_escape_string($data['invite_code'] ?? $_POST['invite_code'] ?? $_GET['inviteCode'] ?? '');
$user_id = intval($data['user_id'] ?? $_POST['user_id'] ?? 0);

if (!$invite_code || !$user_id) {
    // If opened in browser (GET request without user_id)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $invite_code) {
        header("Content-Type: text/html; charset=UTF-8");
        $app_link = "sadhuvandna://Api/join_group.php?inviteCode=$invite_code";
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Join Group - SadhuVandna</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background-color: #fff7ed; }
                .card { background: white; padding: 30px; border-radius: 20px; shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); text-align: center; border: 1px solid #ffedd5; }
                .btn { background: #ea580c; color: white; padding: 12px 30px; border-radius: 10px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 20px; }
                .logo { font-size: 24px; font-weight: bold; color: #ea580c; margin-bottom: 10px; }
            </style>
            <script>
                // Try to open the app automatically
                window.location.href = "<?php echo $app_link; ?>";
            </script>
        </head>
        <body>
            <div class="card">
                <div class="logo">SadhuVandna</div>
                <h2>You've been invited!</h2>
                <p>If the app didn't open automatically, click the button below.</p>
                <a href="<?php echo $app_link; ?>" class="btn">Join Group in App</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    echo json_encode(["status" => "error", "message" => "Invite code and User ID are required"]);
    exit;
}

// 1. Find the group
$res = $con->query("SELECT id, name, photo, platform FROM tbl_groups WHERE invite_code = '$invite_code'");
$group = $res->fetch_assoc();

if (!$group) {
    echo json_encode(["status" => "error", "message" => "Invalid invite link."]);
    exit;
}

$group_id = $group['id'];

// 2. Check if already a member
$check = $con->query("SELECT id FROM tbl_group_members WHERE group_id = $group_id AND user_id = $user_id");
if ($check->num_rows > 0) {
    echo json_encode(["status" => "success", "message" => "You are already a member of this group.", "group" => $group, "already_member" => true]);
    exit;
}

// 3. Join the group
$sql = "INSERT INTO tbl_group_members (group_id, user_id, role) VALUES ($group_id, $user_id, 'member')";
if ($con->query($sql)) {
    echo json_encode(["status" => "success", "message" => "Successfully joined the group!", "group" => $group]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to join group: " . $con->error]);
}
?>
