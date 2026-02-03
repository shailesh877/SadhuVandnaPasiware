<?php
include("header.php");
include("connection.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){
    header("Location: login.php");
    exit;
}

date_default_timezone_set("Asia/Kolkata");

/* ================= USER ================= */
$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = (int)$user['id'];

/* ================= PROFILE ================= */
$profile = $con->query("SELECT * FROM tbl_marriage_profiles WHERE user_id=$user_id")->fetch_assoc();

/* ================= SAVE ================= */
if(isset($_POST['save_profile'])){

    /* BASIC */
    $full_name = $_POST['full_name'];
    $gender    = $_POST['gender'];
    $dob       = $_POST['dob'];
    $status    = $_POST['status'];
    $height    = $_POST['height'];
    $weight    = $_POST['weight'];
    $religion  = $_POST['religion'];

    /* CONTACT */
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    /* CAREER */
    $education  = $_POST['education'];
    $occupation = $_POST['occupation'];
    $work_place = $_POST['work_place'];
    $income     = $_POST['income'];

    /* FAMILY */
    $father_name       = $_POST['father_name'];
    $father_occupation = $_POST['father_occupation'];
    $mother_name       = $_POST['mother_name'];
    $siblings          = $_POST['siblings'];
    $family_type       = $_POST['family_type'];

    /* PERSONAL */
    $nature  = $_POST['nature'];
    $food    = $_POST['food'];
    $habits  = $_POST['habits'];
    $hobbies = $_POST['hobbies'];

    /* PARTNER */
    $partner_age_from     = (int)$_POST['partner_age_from'];
    $partner_age_to       = (int)$_POST['partner_age_to'];
    $partner_education    = $_POST['partner_education'];
    $partner_expectations = $_POST['partner_expectations'];

    /* LOCATION */
    $city      = $_POST['city'];
    $residence = $_POST['residence'];
    $caste     = $_POST['caste'];

    $about = $_POST['about'];
    $date  = date("Y-m-d H:i:s");

    /* PHOTO */
    $photo = $profile['photo'] ? $profile['photo'] : ($user['profile_photo'] ?? '');
    $upload_err = '';
    $abort_save = false;
    if(!empty($_FILES['photo']['name']) && isset($_FILES['photo'])){
        $uploadDirRel = 'uploads/photo/';
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . $uploadDirRel;
        if(!is_dir($uploadDir)){
            if(!@mkdir($uploadDir, 0755, true)){
                $upload_err = 'Failed to create upload directory: ' . $uploadDirRel;
                $abort_save = true;
            }
        }

        if(empty($upload_err)){
            $fileErr = $_FILES['photo']['error'];
            if($fileErr !== UPLOAD_ERR_OK){
                $upload_err = 'File upload error code: ' . $fileErr;
                $abort_save = true;
            } else {
                if(!empty($_POST['old_photo']) && file_exists($uploadDir . $_POST['old_photo'])){
                    @unlink($uploadDir . $_POST['old_photo']);
                }
                $photo = time().'_'.preg_replace('/[^A-Za-z0-9._-]/','_', basename($_FILES['photo']['name']));
                $target = $uploadDir . $photo;
                if(!@move_uploaded_file($_FILES['photo']['tmp_name'], $target)){
                    // fallback to copy for some environments
                    if(!@copy($_FILES['photo']['tmp_name'], $target)){
                        $upload_err = 'Failed to move uploaded file to ' . $uploadDirRel;
                        error_log('move_uploaded_file failed for ' . $target . ' tmp:' . $_FILES['photo']['tmp_name']);
                        $abort_save = true;
                    }
                }
            }
        }
    }

    /* ================= INSERT / UPDATE ================= */

    $saved = false;
    if(!$abort_save){
        if($profile){
            $stmt = $con->prepare("
            UPDATE tbl_marriage_profiles SET
            full_name=?, gender=?, dob=?, status=?, height=?, weight=?, religion=?,
            phone=?, email=?, education=?, occupation=?, work_place=?, income=?,
            father_name=?, father_occupation=?, mother_name=?, siblings=?, family_type=?,
            nature=?, food=?, habits=?, hobbies=?,
            partner_age_from=?, partner_age_to=?, partner_education=?, partner_expectations=?,
            city=?, residence=?, caste=?, about=?, photo=?
            WHERE user_id=?
            ");

            $stmt->bind_param(
            "ssssssssssssssssssssssiisssssssi",
            $full_name,$gender,$dob,$status,$height,$weight,$religion,
            $phone,$email,$education,$occupation,$work_place,$income,
            $father_name,$father_occupation,$mother_name,$siblings,$family_type,
            $nature,$food,$habits,$hobbies,
            $partner_age_from,$partner_age_to,$partner_education,$partner_expectations,
            $city,$residence,$caste,$about,$photo,
            $user_id
            );

            $saved = $stmt->execute();

        } else {

            $stmt = $con->prepare("
            INSERT INTO tbl_marriage_profiles
            (user_id, full_name, gender, dob, status, height, weight, religion,
            phone, email, education, occupation, work_place, income,
            father_name, father_occupation, mother_name, siblings, family_type,
            nature, food, habits, hobbies,
            partner_age_from, partner_age_to, partner_education, partner_expectations,
            city, residence, caste, about, photo, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
            "issssssssssssssssssssssiissssssss",
            $user_id,
            $full_name,$gender,$dob,$status,$height,$weight,$religion,
            $phone,$email,$education,$occupation,$work_place,$income,
            $father_name,$father_occupation,$mother_name,$siblings,$family_type,
            $nature,$food,$habits,$hobbies,
            $partner_age_from,$partner_age_to,$partner_education,$partner_expectations,
            $city,$residence,$caste,$about,$photo,$date
            );

            $saved = $stmt->execute();
        }
    } else {
        // upload failed; do not save to DB
        error_log('Profile save aborted due to upload error: ' . $upload_err);
    }

    if($saved){
        echo "<script>window.location='profile.php';</script>";
    } elseif(!$abort_save && !$saved) {
        $db_err = 'Database error: ' . ($stmt->error ?? $con->error ?? 'Unknown');
        error_log('Marriage profile DB error: ' . $db_err);
    }
}
?>


<main class="flex-1 px-2 md:px-10 py-10 bg-white md:ml-20 mb-13 md:mb-0">
    <div class="w-full max-w-7xl mx-auto bg-white rounded-2xl shadow-2xl border border-orange-300 p-8">
        <?php $upload_err = $upload_err ?? ''; $db_err = $db_err ?? ''; ?>
        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
            <?php if(!empty($upload_err)): ?>
                <div class="text-red-600 font-bold mb-4"><?= htmlspecialchars($upload_err) ?></div>
            <?php endif; ?>
            <?php if(!empty($db_err)): ?>
                <div class="text-red-600 font-bold mb-4"><?= htmlspecialchars($db_err) ?></div>
            <?php endif; ?>

            <!-- Profile Photo Upload -->
            <div class="flex justify-center items-center">
                <label class="relative cursor-pointer">
                    <input type="file" name="photo" class="hidden" id="marriagePhotoInput" accept="image/*"/>

     

                    <?php
        $photoPath = !empty($profile['photo']) 
            ? "uploads/photo/" . $profile['photo'] 
            : (!empty($user['profile_photo']) 
                ? "uploads/photo/" . $user['profile_photo'] 
                : "assets/img/default-user.png");
        ?>
                    <span
                        class="w-32 h-32 bg-orange-100 rounded-full border-2 border-orange-400 flex items-center justify-center overflow-hidden hover:bg-orange-200 transition">
                        <img src="<?= htmlspecialchars($photoPath) ?>" id="marriagePhotoPreview"
                            class="w-full h-full object-cover rounded-full" alt="Profile">
                        <span
                            class="absolute inset-0 flex items-center justify-center bg-orange-400/50 opacity-0 hover:opacity-100 transition rounded-full">
                            <i class="fa fa-camera text-white text-xl"></i>
                        </span>
                    </span>
                </label>
            </div>


            <!-- User Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-orange-700 font-bold mb-1">Full Name</label>
                    <input type="text" name="full_name"
                        value="<?= htmlspecialchars($profile['full_name'] ?? $user['name']); ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Gender</label>
                    <select name="gender" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300"
                        required>
                        <option hidden value="">Select</option>
                        <option value="Male" <?=($profile['gender'] ?? $user['gender'])=='Male' ?'selected':''; ?>>Male
                        </option>
                        <option value="Female" <?=($profile['gender'] ?? $user['gender'])=='Female' ?'selected':''; ?>
                            >Female</option>
                        <option value="Other" <?=($profile['gender'] ?? $user['gender'])=='Other' ?'selected':''; ?>
                            >Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Date of Birth</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars($profile['dob'] ??$user['dob']); ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Status</label>
                    <select name="status" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300"
                        required>
                        <option hidden value="">Select</option>
                        <?php
                        $statusArr = ['Unmarried','Married','Divorced','Widowed','Separated'];
                        foreach($statusArr as $st){
                            $sel = ($profile['status'] ?? $user['maritial_status']) == $st ? 'selected' : '';
                            echo "<option value='$st' $sel>$st</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Height</label>
                    <input type="text" name="height" value="<?= htmlspecialchars($profile['height'] ?? '') ?>" required
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Weight</label>
                    <input type="text" name="weight" value="<?= htmlspecialchars($profile['weight'] ?? '') ?>" required
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Religion</label>
                    <input type="text" name="religion" value="<?= htmlspecialchars($profile['religion'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Phone Number</label>
                    <input type="text" name="phone"
                        value="<?= htmlspecialchars($profile['phone'] ?? $user['mobile']); ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Email ID</label>
                    <input type="email" name="email"
                        value="<?= htmlspecialchars($profile['email'] ?? $user['email']); ?>" required
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Education</label>
                    <input type="text" name="education"
                        value="<?= htmlspecialchars($profile['education'] ?? $user['education']); ?>" required
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Occupation</label>
                    <input type="text" name="occupation"
                        value="<?= htmlspecialchars($profile['occupation'] ?? $user['occupation']); ?>" required
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Work Place</label>
                    <input type="text" name="work_place" value="<?= htmlspecialchars($profile['work_place'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Income</label>
                    <input type="text" name="income" value="<?= htmlspecialchars($profile['income'] ?? '') ?>" required
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($profile['city'] ?? $user['city']); ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Residence</label>
                    <input type="text" name="residence" value="<?= htmlspecialchars($profile['residence'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Caste/Community</label>
                    <input type="text" name="caste" value="<?= htmlspecialchars($profile['caste'] ?? ''); ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Father Name</label>
                    <input type="text" name="father_name" value="<?= htmlspecialchars($profile['father_name'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Father Occupation</label>
                    <input type="text" name="father_occupation"
                        value="<?= htmlspecialchars($profile['father_occupation'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Mother Name</label>
                    <input type="text" name="mother_name" value="<?= htmlspecialchars($profile['mother_name'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Siblings</label>
                    <input type="text" name="siblings" value="<?= htmlspecialchars($profile['siblings'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Family Type</label>
                    <input type="text" name="family_type" value="<?= htmlspecialchars($profile['family_type'] ?? '') ?>"
                        required class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Nature</label>
                    <input type="text" name="nature" value="<?= htmlspecialchars($profile['nature'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Food Habit</label>
                    <input type="text" name="food" value="<?= htmlspecialchars($profile['food'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Habits</label>
                    <input type="text" name="habits" value="<?= htmlspecialchars($profile['habits'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Hobbies</label>
                    <input type="text" name="hobbies" value="<?= htmlspecialchars($profile['hobbies'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Partner Age From</label>
                    <input type="text" name="partner_age_from"
                        value="<?= htmlspecialchars($profile['partner_age_from'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Partner Age To</label>
                    <input type="text" name="partner_age_to"
                        value="<?= htmlspecialchars($profile['partner_age_to'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Partner Education</label>
                    <input type="text" name="partner_education"
                        value="<?= htmlspecialchars($profile['partner_education'] ?? '') ?>"
                        class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required="true">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-orange-700 font-bold mb-1">Partner Expectations</label>
                    <textarea name="partner_expectations" required
                        class="border rounded-lg px-4 py-2 w-full min-h-[80px] focus:ring-2 focus:ring-orange-300"><?= htmlspecialchars($profile['partner_expectations'] ?? '') ?></textarea>
                </div>
            </div>

            <div>
                <label class="block text-orange-700 font-bold mb-1">About / Bio</label>
                <textarea name="about" required
                    class="border rounded-lg px-4 py-2 w-full min-h-[80px] focus:ring-2 focus:ring-orange-300"
                    placeholder="Short Bio, lifestyle, partner expectations..."><?= htmlspecialchars($profile['about'] ?? $user['about']); ?></textarea>
            </div>

            <div>
                <button type="submit" name="save_profile"
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg shadow mb-2 flex items-center gap-2 justify-center text-base">
                    <i class="fa fa-save"></i>
                    <?= $profile ? 'Update Profile' : 'Save Marriage Profile'; ?>
                </button>
                <a href="profile.php"
                    class="w-full bg-orange-100 text-orange-700 font-bold border border-orange-300 hover:bg-orange-200 py-3 px-7 rounded-lg flex items-center gap-2 justify-center shadow transition text-base">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<script>
    document.getElementById('marriagePhotoInput').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = ev => document.getElementById('marriagePhotoPreview').src = ev.target.result;
            reader.readAsDataURL(file);
        }
    });
</script>