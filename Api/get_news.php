<?php
include 'headers.php';
include 'connection.php';

// Auto-migration: Ensure News Anchor job exists in tbl_jobs_education
$check_anchor = $con->query("SELECT id FROM tbl_jobs_education WHERE id = 9999");
if ($check_anchor && $check_anchor->num_rows == 0) {
    $con->query("INSERT INTO tbl_jobs_education (id, type, title, description, image, created_at) VALUES (9999, 'job', 'News Anchor', 'Apply for News Anchor. We are looking for talented news anchors to join our team.', '', NOW())");
}

// Auto-migration: Ensure category column exists in tbl_news
$check_column = $con->query("SHOW COLUMNS FROM tbl_news LIKE 'category'");
if ($check_column && $check_column->num_rows == 0) {
    $con->query("ALTER TABLE tbl_news ADD COLUMN category VARCHAR(100) DEFAULT 'ताज़ा खबर'");
}

// Auto-migration: Ensure state and district columns exist in tbl_news
$check_state = $con->query("SHOW COLUMNS FROM tbl_news LIKE 'state'");
if ($check_state && $check_state->num_rows == 0) {
    $con->query("ALTER TABLE tbl_news ADD COLUMN state VARCHAR(100) DEFAULT NULL, ADD COLUMN district VARCHAR(100) DEFAULT NULL");
}

// Auto-migration: Ensure user_id column exists in tbl_news
$check_uid = $con->query("SHOW COLUMNS FROM tbl_news LIKE 'user_id'");
if ($check_uid && $check_uid->num_rows == 0) {
    $con->query("ALTER TABLE tbl_news ADD COLUMN user_id INT(11) DEFAULT 0");
}

// Auto-migration: Ensure tbl_settings exists with breaking_news row
$con->query("CREATE TABLE IF NOT EXISTS tbl_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    `key` VARCHAR(100) NOT NULL UNIQUE DEFAULT 'breaking_news',
    `value` TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");
$check_breaking = $con->query("SELECT id FROM tbl_settings WHERE `key`='breaking_news' LIMIT 1");
if ($check_breaking && $check_breaking->num_rows == 0) {
    $con->query("INSERT INTO tbl_settings (`key`, `value`) VALUES ('breaking_news', 'ताज़ा खबरों के लिए जुड़े रहें | Breaking News Updates | Stay connected for latest news')");
}

// Auto-create breaking news table
$con->query("CREATE TABLE IF NOT EXISTS `tbl_breaking_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text NOT NULL,
  `posted_by` varchar(50) DEFAULT 'Admin',
  `poster_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$where = '';
if (isset($_REQUEST['user_id']) && intval($_REQUEST['user_id']) > 0) {
    $uid = intval($_REQUEST['user_id']);
    $where = "WHERE user_id = $uid";
}

$query = "SELECT * FROM tbl_news $where ORDER BY id DESC";
$result = $con->query($query);

$news = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // App expects 'images' field. In DB it might be comma separated 'image' column?
        // php/news.php used: $images = array_filter(explode(",", $row['image']));
        // Let's standarize to 'images' array in response.
        $imgs = [];
        if(!empty($row['image'])){
             $imgs = array_values(array_filter(explode(",", $row['image'])));
        }
        $row['images'] = $imgs; // Add array field
        $news[] = $row;
    }
}

// Fetch Breaking News text from tbl_breaking_news
$tickerData = [];
$settingsQuery = "SELECT title FROM tbl_breaking_news WHERE is_active = 1 ORDER BY id DESC LIMIT 10";
$settingsResult = $con->query($settingsQuery);
if ($settingsResult && $settingsResult->num_rows > 0) {
    while($settingsRow = $settingsResult->fetch_assoc()) {
        $breakingText = trim($settingsRow['title']);
        if (!empty($breakingText)) {
            $cleanText = trim(preg_replace('/\s+/', ' ', $breakingText));
            if (!empty($cleanText)) {
                $tickerData[] = $cleanText;
            }
        }
    }
}
// Fallback: use top news titles if no setting set
if (empty($tickerData)) {
    $tickerQuery = "SELECT title FROM tbl_news ORDER BY id DESC LIMIT 5";
    $tickerResult = $con->query($tickerQuery);
    if ($tickerResult && $tickerResult->num_rows > 0) {
        while ($row = $tickerResult->fetch_assoc()) {
            $tickerData[] = $row['title'];
        }
    }
}


echo json_encode(["status" => "success", "data" => $news, "ticker" => $tickerData]);
?>
