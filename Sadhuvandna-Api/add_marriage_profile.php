<?php
include("header.php");
include("connection.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){
    header("Location: login.php");
    exit;
}

// date set here local 
 date_default_timezone_set("Asia/Kolkata");

// ✅ Logged-in user details fetch
$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];

// ✅ Check if profile already exists (for edit/fill auto)
$profile = $con->query("SELECT * FROM tbl_marriage_profiles WHERE user_id='$user_id'")->fetch_assoc();

// ✅ Handle profile save/update
if(isset($_POST['save_profile'])){
    $full_name = trim($_POST['full_name']);
    $gender = trim($_POST['gender']);
    $dob = $_POST['dob'];
    $status = $_POST['status'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $education = trim($_POST['education']);
    $occupation = trim($_POST['occupation']);
    $city = trim($_POST['city']);
    $caste = trim($_POST['caste']);
    $about = trim($_POST['about']);
    $photo = $profile['photo'] ?? '';
     $date=date("d-m-Y H:i:s");
// ✅ Photo upload
if (!empty($_FILES['photo']['name'])) {
    // Delete old photo if exists
    if (!empty($_POST['old_photo']) && file_exists("uploads/photo/" . $_POST['old_photo'])) {
        unlink("uploads/photo/" . $_POST['old_photo']);
    }

    // Save new photo
    $photo = time() . '_' . basename($_FILES['photo']['name']);
    move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/photo/" . $photo);
} else {
    // Agar naya photo upload nahi hua to purana hi rakho
    $photo = $_POST['old_photo'] ?? '';
}


    // ✅ Insert or Update profile
    if($profile){
        $stmt = $con->prepare("UPDATE tbl_marriage_profiles SET full_name=?, gender=?, dob=?, status=?, phone=?, email=?, education=?, occupation=?, city=?, caste=?, about=?, photo=? WHERE user_id=?");
        $stmt->bind_param("ssssssssssssi", $full_name, $gender, $dob, $status, $phone, $email, $education, $occupation, $city, $caste, $about, $photo, $user_id);
        $stmt->execute();
        echo "<script> window.location='profile';</script>";
    } else {
        $stmt = $con->prepare("INSERT INTO tbl_marriage_profiles (user_id, full_name, gender, dob, status, phone, email, education, occupation, city, caste, about, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssssssss", $user_id, $full_name, $gender, $dob, $status, $phone, $email, $education, $occupation, $city, $caste, $about, $photo, $date);
        $stmt->execute();
        echo "<script> window.location='profile';</script>";
    }

     

}
?>

<main class="flex-1 px-2 md:px-10 py-10 bg-white md:ml-20 mb-13 md:mb-0">
    <div class="w-full max-w-7xl mx-auto bg-white rounded-2xl shadow-2xl border border-orange-300 p-8">
        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">

            <!-- Profile Photo Upload -->
<div class="flex justify-center items-center">
    <label class="relative cursor-pointer">
        <input type="file" name="photo" class="hidden" id="marriagePhotoInput" accept="image/*" />
        
        <!-- Hidden input for old photo -->
        <input type="hidden" name="old_photo" value="<?= htmlspecialchars($profile['photo'] ?? $user['profile_photo'] ?? '') ?>">

        <?php
        $photoPath = !empty($profile['photo']) 
            ? "uploads/photo/" . $profile['photo'] 
            : (!empty($user['profile_photo']) 
                ? "uploads/photo/" . $user['profile_photo'] 
                : "assets/img/default-user.png");
        ?>
        <span class="w-32 h-32 bg-orange-100 rounded-full border-2 border-orange-400 flex items-center justify-center overflow-hidden hover:bg-orange-200 transition">
            <img src="<?= htmlspecialchars($photoPath) ?>"
                 id="marriagePhotoPreview"
                 class="w-full h-full object-cover rounded-full"
                 alt="Profile">
            <span class="absolute inset-0 flex items-center justify-center bg-orange-400/50 opacity-0 hover:opacity-100 transition rounded-full">
                <i class="fa fa-camera text-white text-xl"></i>
            </span>
        </span>
    </label>
</div>


            <!-- User Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-orange-700 font-bold mb-1">Full Name</label>
                    <input type="text" name="full_name" value="<?= htmlspecialchars($profile['full_name'] ?? $user['name']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Gender</label>
                    <select name="gender" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                        <option hidden value="">Select</option>
                        <option value="Male" <?= ($profile['gender'] ?? $user['gender'])=='Male'?'selected':''; ?>>Male</option>
                        <option value="Female" <?= ($profile['gender'] ?? $user['gender'])=='Female'?'selected':''; ?>>Female</option>
                        <option value="Other" <?= ($profile['gender'] ?? $user['gender'])=='Other'?'selected':''; ?>>Other</option>
                    </select>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Date of Birth</label>
                    <input type="date" name="dob" value="<?= htmlspecialchars($profile['dob'] ??$user['dob']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Status</label>
                    <select name="status" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
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
                    <label class="block text-orange-700 font-bold mb-1">Phone Number</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? $user['mobile']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Email ID</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? $user['email']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Education</label>
                    <input type="text" name="education" value="<?= htmlspecialchars($profile['education'] ?? $user['education']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Occupation</label>
                    <input type="text" name="occupation" value="<?= htmlspecialchars($profile['occupation'] ?? $user['occupation']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">City</label>
                    <input type="text" name="city" value="<?= htmlspecialchars($profile['city'] ?? $user['city']); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300" required>
                </div>

                <div>
                    <label class="block text-orange-700 font-bold mb-1">Caste/Community</label>
                    <input type="text" name="caste" value="<?= htmlspecialchars($profile['caste'] ?? ''); ?>" class="border rounded-lg px-4 py-2 w-full focus:ring-2 focus:ring-orange-300">
                </div>
            </div>

            <div>
                <label class="block text-orange-700 font-bold mb-1">About / Bio</label>
                <textarea name="about" class="border rounded-lg px-4 py-2 w-full min-h-[80px] focus:ring-2 focus:ring-orange-300" placeholder="Short Bio, lifestyle, partner expectations..."><?= htmlspecialchars($profile['about'] ?? $user['about']); ?></textarea>
            </div>

            <div>
                <button type="submit" name="save_profile" class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg shadow mb-2 flex items-center gap-2 justify-center text-base">
                    <i class="fa fa-save"></i> <?= $profile ? 'Update Profile' : 'Save Marriage Profile'; ?>
                </button>

                <a href="profile" class="w-full bg-orange-100 text-orange-700 font-bold border border-orange-300 hover:bg-orange-200 py-3 px-7 rounded-lg flex items-center gap-2 justify-center shadow transition text-base">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('marriagePhotoInput').addEventListener('change', function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = ev => document.getElementById('marriagePhotoPreview').src = ev.target.result;
        reader.readAsDataURL(file);
    }
});
</script>
