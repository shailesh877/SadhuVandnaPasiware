<?php
session_start();
include("connection.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if (!$user_email) {
    // If not logged in, redirect to login page
    header("Location: login.php");
    exit;
}

// Get logged-in user ID
$user = $con->query("SELECT id FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_id = intval($_POST['post_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($post_id && $comment) {
        // Prepare and insert comment
        $stmt = $con->prepare("INSERT INTO tbl_comments (post_id, user_id, comment, date) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $post_id, $user_id, $comment);

        if ($stmt->execute()) {
            // Redirect back to profile page
            header("Location: profile.php");
            exit;
        } else {
            echo "Error: " . $stmt->error;
        }
    } else {
        echo "Invalid post or empty comment.";
    }
} else {
    echo "Invalid request method.";
}
