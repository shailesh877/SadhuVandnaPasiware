<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST, GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

try {
    include 'connection.php';
    include 'push_helper.php';

    // JSON Input Support
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    $user_id = $_REQUEST['user_id'] ?? $data['user_id'] ?? 0;
    $type = $_REQUEST['type'] ?? $data['type'] ?? '';
    
    $limit = intval($_REQUEST['limit'] ?? $data['limit'] ?? 20);
    $offset = intval($_REQUEST['offset'] ?? $data['offset'] ?? 0);

    // Filters (Match labels exactly with App)
    $gender = $_REQUEST['gender'] ?? $data['gender'] ?? '';
    $age_group = $_REQUEST['age'] ?? $data['age'] ?? '';
    $city = trim($_REQUEST['city'] ?? $data['city'] ?? '');
    $education = trim($_REQUEST['education'] ?? $data['education'] ?? '');
    $search = trim($_REQUEST['search'] ?? $data['search'] ?? '');

    if (!$user_id) {
        echo json_encode(["status" => "error", "message" => "User ID required"]);
        exit;
    }

    // Get My Profile ID (Ensure user_id search is robust)
    $mq = $con->query("SELECT id FROM tbl_marriage_profiles WHERE user_id='$user_id' LIMIT 1");
    $my_profile_id = ($mq && $mq->num_rows > 0) ? $mq->fetch_assoc()['id'] : 0;

    // Building Query (Mirroring Website fetch_profiles.php)
    $where = " WHERE 1 ";

    if ($gender) $where .= " AND mp.gender = '$gender' ";
    if ($city) {
        $cleanCity = $con->real_escape_string($city);
        $where .= " AND mp.city LIKE '%$cleanCity%' ";
    }
    if ($education) {
        $cleanEdu = $con->real_escape_string($education);
        $where .= " AND mp.education LIKE '%$cleanEdu%' ";
    }

    // Age Filter (Website Style)
    if ($age_group) {
        $parts = explode('-', $age_group);
        if (count($parts) == 2) {
            $min = intval($parts[0]);
            $max = intval($parts[1]);
            $where .= " AND TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) BETWEEN $min AND $max ";
        }
    }

    if ($search) {
        $cleanSearch = $con->real_escape_string($search);
        $where .= " AND (mp.full_name LIKE '%$cleanSearch%' OR mp.city LIKE '%$cleanSearch%' OR mp.caste LIKE '%$cleanSearch%') ";
    }

    // Connected Profiles Logic (Matches) - Ensure both directions are fully covered
    if ($type === 'connected' && $my_profile_id) {
        $where .= " AND mp.id IN (
            SELECT p.sender_id FROM tbl_proposals p WHERE p.receiver_id='$my_profile_id' AND p.status IN ('friend', 'accepted')
            UNION
            SELECT p.receiver_id FROM tbl_proposals p WHERE p.sender_id='$my_profile_id' AND p.status IN ('friend', 'accepted')
        )";
    }

    $query = "
        SELECT mp.*, m.status as user_status, 
        TIMESTAMPDIFF(YEAR, STR_TO_DATE(mp.dob,'%Y-%m-%d'), CURDATE()) AS age 
        FROM tbl_marriage_profiles mp
        JOIN tbl_members m ON mp.user_id = m.id
        $where AND m.status != 'Blocked'
        ORDER BY mp.id DESC 
        LIMIT $limit OFFSET $offset
    ";

    $res = $con->query($query);
    $profiles = [];
    $profile_ids = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $profiles[] = $row;
            $profile_ids[] = $row['id'];
        }
    }

    // Map proposal statuses
    if (!empty($profile_ids) && $my_profile_id) {
        $ids_str = implode(',', array_map('intval', $profile_ids));
        $pq = $con->query("SELECT sender_id, receiver_id, status FROM tbl_proposals WHERE (sender_id='$my_profile_id' AND receiver_id IN ($ids_str)) OR (receiver_id='$my_profile_id' AND sender_id IN ($ids_str))");
        $pmap = [];
        if ($pq) {
            while ($p = $pq->fetch_assoc()) {
                $other = ($p['sender_id'] == $my_profile_id) ? $p['receiver_id'] : $p['sender_id'];
                $s = $p['status'];
                if ($s == 'pending') $s = ($p['sender_id'] == $my_profile_id) ? 'sent' : 'received';
                $pmap[$other] = $s;
            }
        }
        foreach ($profiles as &$r) {
            $r['proposal_status'] = $pmap[$r['id']] ?? null;
        }
    }

    // Request Count for badge
    $rc = 0;
    if ($my_profile_id) {
        $rcq = $con->query("SELECT COUNT(*) FROM tbl_proposals WHERE receiver_id='$my_profile_id' AND status='pending'");
        if ($rcq) $rc = $rcq->fetch_row()[0];
    }

    echo json_encode([
        "status" => "success",
        "data" => $profiles,
        "my_profile_id" => (int)$my_profile_id,
        "request_count" => (int)$rc
    ]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
