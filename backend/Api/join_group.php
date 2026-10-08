<?php
// join_group.php

// 1. First, check if it's a browser request to show the landing page
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['inviteCode'])) {
    header("Content-Type: text/html; charset=UTF-8");
    $invite_code = $_GET['inviteCode'];
    $app_link = "sadhuvandna://join?inviteCode=" . htmlspecialchars($invite_code);
    $intent_link = "intent://join?inviteCode=" . htmlspecialchars($invite_code) . "#Intent;scheme=sadhuvandna;end";
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Join Group - SadhuVandna</title>
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                display: flex; 
                flex-direction: column; 
                align-items: center; 
                justify-content: center; 
                height: 100vh; 
                margin: 0; 
                background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            }
            .card { 
                background: white; 
                padding: 40px 30px; 
                border-radius: 30px; 
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
                text-align: center; 
                max-width: 90%;
                width: 350px;
                border: 1px solid rgba(234, 88, 12, 0.1);
            }
            .logo { 
                font-size: 28px; 
                font-weight: 900; 
                color: #ea580c; 
                margin-bottom: 20px;
                letter-spacing: -1px;
            }
            h2 { color: #1f2937; margin-bottom: 10px; font-size: 22px; }
            p { color: #6b7280; font-size: 14px; line-height: 1.5; margin-bottom: 30px; }
            .btn { 
                background: #ea580c; 
                color: white; 
                padding: 16px 32px; 
                border-radius: 16px; 
                text-decoration: none; 
                font-weight: 800; 
                display: block;
                transition: transform 0.2s, background-color 0.2s;
                box-shadow: 0 4px 6px -1px rgba(234, 88, 12, 0.4);
            }
            .btn:active { transform: scale(0.98); background-color: #c2410c; }
            .loader {
                width: 20px;
                height: 20px;
                border: 3px solid #f3f3f3;
                border-top: 3px solid #ea580c;
                border-radius: 50%;
                animation: spin 1s linear infinite;
                margin: 20px auto;
            }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        </style>
        <script>
            function openApp() {
                var isAndroid = /android/i.test(navigator.userAgent.toLowerCase());
                if (isAndroid) {
                    window.location.href = "<?php echo $intent_link; ?>";
                } else {
                    window.location.href = "<?php echo $app_link; ?>";
                }
            }
            
            // Try to open the app after a short delay
            setTimeout(function() {
                openApp();
            }, 1000);
        </script>
    </head>
    <body>
        <div class="card">
            <div class="logo">SadhuVandna</div>
            <h2>Group Invitation</h2>
            <p>You've been invited to join a group. We're opening the SadhuVandna app for you...</p>
            <div class="loader"></div>
            <a href="#" onclick="openApp(); return false;" class="btn">Open App Now</a>
            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">If the app is not installed, please download it from the Play Store.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 2. Otherwise, handle API requests (JSON)
include 'headers.php';
include 'connection.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

$invite_code = $con->real_escape_string($data['invite_code'] ?? $_POST['invite_code'] ?? '');
$user_id = intval($data['user_id'] ?? $_POST['user_id'] ?? 0);

if (!$invite_code || !$user_id) {
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
