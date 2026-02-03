<?php
include("connection.php");
session_start();

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email) die("Unauthorized");

$profile_id = intval($_GET['id']);
if(!$profile_id) die("Invalid ID");

$profile = $con->query("
    SELECT *, TIMESTAMPDIFF(YEAR, STR_TO_DATE(dob,'%Y-%m-%d'), CURDATE()) AS age
    FROM tbl_marriage_profiles
    WHERE id='$profile_id' LIMIT 1
")->fetch_assoc();

if(!$profile) die("Profile not found");

$photo = !empty($profile['photo']) ? "uploads/photo/".$profile['photo'] : "https://via.placeholder.com/150";

// Helper to display row
function displayRow($label, $value){
    if(empty($value)) return;
    echo "
    <div class='flex flex-col border-b border-gray-100 py-2'>
        <span class='text-xs text-orange-600 font-bold uppercase'>$label</span>
        <span class='text-gray-800 font-medium'>".htmlspecialchars($value)."</span>
    </div>";
}
?>

<div class="flex flex-col gap-4">
    <!-- Header -->
    <div class="flex flex-col items-center">
        <img src="<?= $photo ?>" class="w-32 h-32 rounded-full border-4 border-orange-400 object-cover shadow-lg mb-3">
        <h2 class="text-2xl font-bold text-orange-700"><?= htmlspecialchars($profile['full_name']) ?></h2>
        <div class="text-gray-600 font-semibold"><?= htmlspecialchars($profile['status']) ?> | <?= htmlspecialchars($profile['gender']) ?> | <?= $profile['age'] ?> yrs</div>
        <div class="text-gray-500 text-sm"><i class="fa fa-location-dot"></i> <?= htmlspecialchars($profile['city']) ?></div>
    </div>

    <!-- Group: Basic Info -->
    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
        <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">Basic Details</h3>
        <div class="grid grid-cols-2 gap-x-4">
            <?php 
            displayRow("Height", $profile['height']);
            displayRow("Weight", $profile['weight']);
            displayRow("Religion", $profile['religion']);
            displayRow("Caste", $profile['caste']);
            displayRow("Date of Birth", $profile['dob']);
            displayRow("Residence", $profile['residence']);
            ?>
        </div>
    </div>

    <!-- Group: Contact (Maybe hidden for some?) -->
    <!--<div class="bg-orange-50 p-4 rounded-xl border border-orange-100">-->
    <!--    <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">Contact Details</h3>-->
    <!--    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">-->
    <!--        <?php -->
    // <!--        displayRow("Phone", $profile['phone']);-->
    // <!--        displayRow("Email", $profile['email']);-->
    <!--        ?>-->
    <!--    </div>-->
    <!--</div>-->

    <!-- Group: Education & Career -->
    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
        <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">Education & Career</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
            <?php 
            displayRow("Education", $profile['education']);
            displayRow("Occupation", $profile['occupation']);
            displayRow("Work Place", $profile['work_place']);
            displayRow("Income", $profile['income']);
            ?>
        </div>
    </div>

    <!-- Group: Family -->
    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
        <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">Family Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
            <?php 
            displayRow("Father Name", $profile['father_name']);
            displayRow("Father Occupation", $profile['father_occupation']);
            displayRow("Mother Name", $profile['mother_name']);
            displayRow("Siblings", $profile['siblings']);
            displayRow("Family Type", $profile['family_type']);
            ?>
        </div>
    </div>

    <!-- Group: Personal Info -->
    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
        <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">Personal Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
            <?php 
            displayRow("Nature", $profile['nature']);
            displayRow("Food Habit", $profile['food']);
            displayRow("Habits", $profile['habits']);
            displayRow("Hobbies", $profile['hobbies']);
            ?>
        </div>
    </div>

     <!-- Group: About -->
     <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
        <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">About</h3>
        <p class="text-gray-700 text-sm whitespace-pre-wrap"><?= htmlspecialchars($profile['about']) ?></p>
    </div>

    <!-- Group: Partner Preference -->
    <div class="bg-orange-50 p-4 rounded-xl border border-orange-100">
        <h3 class="text-lg font-bold text-orange-800 border-b border-orange-200 mb-2 pb-1">Partner Preference</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4">
            <?php 
            displayRow("Age From", $profile['partner_age_from']);
            displayRow("Age To", $profile['partner_age_to']);
            displayRow("Education", $profile['partner_education']);
            ?>
        </div>
        <div class="mt-2">
            <span class='text-xs text-orange-600 font-bold uppercase'>Expectations</span>
            <p class="text-gray-700 text-sm whitespace-pre-wrap"><?= htmlspecialchars($profile['partner_expectations']) ?></p>
        </div>
    </div>

</div>