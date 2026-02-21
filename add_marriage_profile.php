<?php
//@session_start();
include("header.php");
date_default_timezone_set("Asia/Kolkata");

$user_mobile = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_mobile){
    header("Location: login.php");
    exit;
}

/* ================= SAVE HANDLER (Before any HTML output) ================= */
if(isset($_POST['save_profile'])){
    $full_name = trim($_POST['full_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $about = trim($_POST['about'] ?? '');

    // Server-side Validation with Alerts
    if (preg_match('/[0-9]/', $full_name) || preg_match('/[0-9]/', $father_name) || preg_match('/[0-9]/', $mother_name)) {
        echo "<script>alert('Numbers not allowed in Name fields'); window.history.back();</script>";
        exit;
    }
    if (preg_match('/[0-9]/', $about)) {
        echo "<script>alert('Numbers not allowed in About/Bio field'); window.history.back();</script>";
        exit;
    }
    if (strlen($about) < 5) {
        echo "<script>alert('About/Bio must be at least 5 characters long'); window.history.back();</script>";
        exit;
    }

    /* ALL OTHER FIELDS (Null Coalescing for Safety) */
    $gender    = $_POST['gender'] ?? '';
    $dob       = $_POST['dob'] ?? '';
    $status    = $_POST['status'] ?? '';
    $height    = $_POST['height'] ?? '';
    $weight    = $_POST['weight'] ?? '';
    $religion  = $_POST['religion'] ?? '';
    $phone     = $_POST['phone'] ?? '';
    $email     = $_POST['email'] ?? '';
    $education  = $_POST['education'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    $work_place = $_POST['work_place'] ?? '';
    $income     = $_POST['income'] ?? '';
    $father_occupation = $_POST['father_occupation'] ?? '';
    $siblings          = $_POST['siblings'] ?? '';
    $family_type       = $_POST['family_type'] ?? '';
    $nature  = $_POST['nature'] ?? '';
    $food    = $_POST['food'] ?? '';
    $habits  = $_POST['habits'] ?? '';
    $hobbies = $_POST['hobbies'] ?? '';
    $partner_age_from     = (int)($_POST['partner_age_from'] ?? 0);
    $partner_age_to       = (int)($_POST['partner_age_to'] ?? 0);
    $partner_education    = $_POST['partner_education'] ?? '';
    $partner_expectations = $_POST['partner_expectations'] ?? '';
    $city      = $_POST['city'] ?? '';
    $residence = $_POST['residence'] ?? '';
    $caste     = $_POST['caste'] ?? '';
    $date  = date("Y-m-d H:i:s");

    $user = $con->query("SELECT id, profile_photo FROM tbl_members WHERE mobile='$user_mobile'")->fetch_assoc();
    $user_id = (int)$user['id'];
    $profile = $con->query("SELECT * FROM tbl_marriage_profiles WHERE user_id=$user_id")->fetch_assoc();

    /* PHOTO HANDLER */
    $photo = $profile['photo'] ? $profile['photo'] : ($user['profile_photo'] ?? '');
    if(!empty($_FILES['photo']['name'])){
        $uploadDir = 'uploads/photo/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $photo = time().'_'.preg_replace('/[^A-Za-z0-9._-]/','_', basename($_FILES['photo']['name']));
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo);
    }

    if($profile){
        $stmt = $con->prepare("UPDATE tbl_marriage_profiles SET full_name=?, gender=?, dob=?, status=?, height=?, weight=?, religion=?, phone=?, email=?, education=?, occupation=?, work_place=?, income=?, father_name=?, father_occupation=?, mother_name=?, siblings=?, family_type=?, nature=?, food=?, habits=?, hobbies=?, partner_age_from=?, partner_age_to=?, partner_education=?, partner_expectations=?, city=?, residence=?, caste=?, about=?, photo=? WHERE user_id=?");
        $stmt->bind_param("ssssssssssssssssssssssiisssssssi", $full_name,$gender,$dob,$status,$height,$weight,$religion,$phone,$email,$education,$occupation,$work_place,$income,$father_name,$father_occupation,$mother_name,$siblings,$family_type,$nature,$food,$habits,$hobbies,$partner_age_from,$partner_age_to,$partner_education,$partner_expectations,$city,$residence,$caste,$about,$photo,$user_id);
    } else {
        $stmt = $con->prepare("INSERT INTO tbl_marriage_profiles (user_id, full_name, gender, dob, status, height, weight, religion, phone, email, education, occupation, work_place, income, father_name, father_occupation, mother_name, siblings, family_type, nature, food, habits, hobbies, partner_age_from, partner_age_to, partner_education, partner_expectations, city, residence, caste, about, photo, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssssssssssssssssssssiissssssss", $user_id, $full_name,$gender,$dob,$status,$height,$weight,$religion,$phone,$email,$education,$occupation,$work_place,$income,$father_name,$father_occupation,$mother_name,$siblings,$family_type,$nature,$food,$habits,$hobbies,$partner_age_from,$partner_age_to,$partner_education,$partner_expectations,$city,$residence,$caste,$about,$photo,$date);
    }

    if($stmt->execute()){
        echo "<script>alert('Profile Saved Successfully'); window.location.href='profile.php';</script>";
    } else {
        echo "<script>alert('Error saving profile'); window.history.back();</script>";
    }
    exit;
}


$user = $con->query("SELECT * FROM tbl_members WHERE mobile='$user_mobile'")->fetch_assoc();
$user_id = (int)$user['id'];
$profile = $con->query("SELECT * FROM tbl_marriage_profiles WHERE user_id=$user_id")->fetch_assoc();
?>

<main class="flex-1 px-4 md:px-10 py-10 md:ml-20 mb-13 md:mb-0 max-w-7xl mx-auto w-full">
    <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-orange-100 p-6 md:p-10">
        <h2 class="text-3xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-orange-600 to-red-600 mb-8 flex items-center gap-3">
            <i class="fa fa-ring"></i>
            <?= $profile ? 'Edit Marriage Profile' : 'Create Marriage Profile'; ?>
        </h2>

        <form method="POST" enctype="multipart/form-data" class="space-y-10" onsubmit="return validateMarriageForm();">
            
            <!-- SECTION 1: PHOTO -->
            <div class="bg-orange-50/30 p-6 rounded-2xl border border-orange-100">
                <p class="text-orange-800 font-bold mb-4 flex items-center gap-2"><i class="fa fa-camera"></i> Profile Photo</p>
                <div class="flex flex-col items-center">
                    <div class="relative group">
                        <img id="preview" src="<?= (!empty($profile['photo']) ? 'uploads/photo/'.$profile['photo'] : (!empty($user['profile_photo']) ? 'uploads/photo/'.$user['profile_photo'] : 'images/default_user.png')); ?>" 
                             class="w-40 h-40 rounded-full object-cover border-4 border-white shadow-lg group-hover:opacity-75 transition-all">
                        <label class="absolute bottom-0 right-0 bg-orange-500 text-white p-2 rounded-full cursor-pointer hover:bg-orange-600 shadow-md">
                            <i class="fa fa-camera"></i>
                            <input type="file" name="photo" class="hidden" onchange="previewImage(this)">
                        </label>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: BASIC INFORMATION -->
            <div>
                <p class="text-orange-800 font-bold mb-4 pb-2 border-b border-orange-100 flex items-center gap-2">
                    <i class="fa fa-user"></i> Basic Information
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Full Name</label>
                        <input type="text" name="full_name" id="full_name" required
                            value="<?= htmlspecialchars($profile['full_name'] ?? $user['name'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Gender</label>
                        <select name="gender" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                            <option hidden value="">Select</option>
                            <option value="Male" <?=($profile['gender'] ?? $user['gender'])=='Male' ?'selected':''; ?>>Male</option>
                            <option value="Female" <?=($profile['gender'] ?? $user['gender'])=='Female' ?'selected':''; ?>>Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Date of Birth</label>
                        <input type="date" name="dob" required value="<?= htmlspecialchars($profile['dob'] ?? $user['dob'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Marital Status</label>
                        <select name="status" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                            <option value="Unmarried" <?=($profile['status'] ?? '')=='Unmarried'?'selected':''; ?>>Unmarried</option>
                            <option value="Married" <?=($profile['status'] ?? '')=='Married'?'selected':''; ?>>Married</option>
                            <option value="Divorced" <?=($profile['status'] ?? '')=='Divorced'?'selected':''; ?>>Divorced</option>
                            <option value="Widow" <?=($profile['status'] ?? '')=='Widow'?'selected':''; ?>>Widow</option>
                            <option value="Widower" <?=($profile['status'] ?? '')=='Widower'?'selected':''; ?>>Widower</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Height</label>
                        <input type="text" name="height" required value="<?= htmlspecialchars($profile['height'] ?? ''); ?>"
                            placeholder="e.g. 5'8\" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Weight (kg)</label>
                        <input type="number" name="weight" required value="<?= htmlspecialchars($profile['weight'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Religion</label>
                        <input type="text" name="religion" required value="<?= htmlspecialchars($profile['religion'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Caste/Samaj</label>
                        <input type="text" name="caste" required value="<?= htmlspecialchars($profile['caste'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                </div>
            </div>

            <!-- SECTION 3: CONTACT INFORMATION -->
            <div>
                <p class="text-orange-800 font-bold mb-4 pb-2 border-b border-orange-100 flex items-center gap-2">
                    <i class="fa fa-phone"></i> Contact Information
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Phone Number</label>
                        <input type="text" name="phone" required value="<?= htmlspecialchars($profile['phone'] ?? $user['mobile'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Email Address</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($profile['email'] ?? $user['email'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                </div>
            </div>

            <!-- SECTION 4: EDUCATION & CAREER -->
            <div>
                <p class="text-orange-800 font-bold mb-4 pb-2 border-b border-orange-100 flex items-center gap-2">
                    <i class="fa fa-briefcase"></i> Education & Career
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Education</label>
                        <input type="text" name="education" required value="<?= htmlspecialchars($profile['education'] ?? $user['education'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Occupation</label>
                        <input type="text" name="occupation" required value="<?= htmlspecialchars($profile['occupation'] ?? $user['occupation'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Work Place/City</label>
                        <input type="text" name="work_place" required value="<?= htmlspecialchars($profile['work_place'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Annual Income</label>
                        <input type="text" name="income" required value="<?= htmlspecialchars($profile['income'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                </div>
            </div>

            <!-- SECTION 5: FAMILY DETAILS -->
            <div>
                <p class="text-orange-800 font-bold mb-4 pb-2 border-b border-orange-100 flex items-center gap-2">
                    <i class="fa fa-users"></i> Family Details
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Father's Name</label>
                        <input type="text" name="father_name" id="father_name" required value="<?= htmlspecialchars($profile['father_name'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Father's Occupation</label>
                        <input type="text" name="father_occupation" required value="<?= htmlspecialchars($profile['father_occupation'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Mother's Name</label>
                        <input type="text" name="mother_name" id="mother_name" required value="<?= htmlspecialchars($profile['mother_name'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Siblings Details</label>
                        <input type="text" name="siblings" required value="<?= htmlspecialchars($profile['siblings'] ?? ''); ?>"
                            placeholder="e.g. 1 Brother, 2 Sisters" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Family Type</label>
                        <select name="family_type" required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                            <option value="" hidden>Select</option>
                            <option value="Nuclear" <?=($profile['family_type'] ?? '')=='Nuclear'?'selected':''; ?>>Nuclear</option>
                            <option value="Joint" <?=($profile['family_type'] ?? '')=='Joint'?'selected':''; ?>>Joint</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECTION 6: LIFESTYLE & LOCATION -->
            <div>
                <p class="text-orange-800 font-bold mb-4 pb-2 border-b border-orange-100 flex items-center gap-2">
                    <i class="fa fa-heart"></i> Lifestyle & Location
                </p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Nature/Personality</label>
                        <input type="text" name="nature" required value="<?= htmlspecialchars($profile['nature'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Food Habits</label>
                        <select name="food" required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                            <option value="" hidden>Select</option>
                            <option value="Vegetarian" <?=($profile['food'] ?? '')=='Vegetarian'?'selected':''; ?>>Vegetarian</option>
                            <option value="Non-Vegetarian" <?=($profile['food'] ?? '')=='Non-Vegetarian'?'selected':''; ?>>Non-Vegetarian</option>
                            <option value="Eggitarian" <?=($profile['food'] ?? '')=='Eggitarian'?'selected':''; ?>>Eggitarian</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Habits</label>
                        <input type="text" name="habits" required value="<?= htmlspecialchars($profile['habits'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Hobbies</label>
                        <input type="text" name="hobbies" required value="<?= htmlspecialchars($profile['hobbies'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Current City</label>
                        <input type="text" name="city" required value="<?= htmlspecialchars($profile['city'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Residence Status</label>
                        <input type="text" name="residence" required value="<?= htmlspecialchars($profile['residence'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                </div>
            </div>

            <!-- SECTION 7: PARTNER EXPECTATIONS -->
            <div>
                <p class="text-orange-800 font-bold mb-4 pb-2 border-b border-orange-100 flex items-center gap-2">
                    <i class="fa fa-search"></i> Partner Expectations
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Min Age</label>
                        <input type="number" name="partner_age_from" required value="<?= htmlspecialchars($profile['partner_age_from'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Max Age</label>
                        <input type="number" name="partner_age_to" required value="<?= htmlspecialchars($profile['partner_age_to'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-1">Desired Education</label>
                        <input type="text" name="partner_education" required value="<?= htmlspecialchars($profile['partner_education'] ?? ''); ?>"
                            class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-gray-700 text-sm font-bold mb-1">Partner Expectations</label>
                        <textarea name="partner_expectations" required
                            class="border rounded-lg px-4 py-2 w-full min-h-[80px] focus:ring-2 focus:ring-orange-300"><?= htmlspecialchars($profile['partner_expectations'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION 8: ABOUT ME -->
            <div class="pt-4 border-t">
                <label class="block text-orange-800 font-bold mb-1 text-lg">About Me / Bio</label>
                <textarea name="about" id="about" required
                    placeholder="Describe yourself, your interests and your expectation from a partner..."
                    class="border rounded-lg px-4 py-2 w-full min-h-[120px] focus:ring-2 focus:ring-orange-300 text-lg"><?= htmlspecialchars($profile['about'] ?? $user['about'] ?? ''); ?></textarea>
                <p class="text-xs text-gray-400 mt-1">* Minimum 5 characters, no numbers allowed.</p>
            </div>

            <!-- SUBMIT BUTTONS -->
            <div class="pt-10 flex flex-col md:flex-row gap-4">
                <button type="submit" name="save_profile" 
                        class="flex-1 bg-gradient-to-r from-orange-500 to-red-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-orange-500/50 transition-all active:scale-95 text-lg">
                    <i class="fa fa-save mr-2"></i> <?= $profile ? 'Update Profile' : 'Save Profile'; ?>
                </button>
                <a href="profile.php" class="flex-1 bg-gray-100 text-gray-700 font-bold py-4 rounded-xl border border-gray-200 text-center hover:bg-gray-200 transition-all text-lg">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview').src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function validateMarriageForm() {
    const fullName = document.getElementById('full_name').value;
    const fatherName = document.getElementById('father_name').value;
    const motherName = document.getElementById('mother_name').value;
    const about = document.getElementById('about').value;

    const numRegex = /[0-9]/;

    if (numRegex.test(fullName) || numRegex.test(fatherName) || numRegex.test(motherName)) {
        alert('Numbers are not allowed in Name fields!');
        return false;
    }

    if (numRegex.test(about)) {
        alert('Numbers are not allowed in About/Bio field!');
        return false;
    }

    if (about.trim().length < 5) {
        alert('About/Bio must be at least 5 characters long!');
        return false;
    }

    return true;
}
</script>
