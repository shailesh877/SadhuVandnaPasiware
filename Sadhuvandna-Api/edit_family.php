<?php
include("header.php");
include("connection.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if (!$user_email) { header("Location: login.php"); exit; }

$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];

$id = intval($_GET['id'] ?? 0);
$member = $con->query("SELECT * FROM tbl_family_members WHERE id='$id' AND user_id='$user_id'")->fetch_assoc();
if(!$member){
    echo "<div class='text-center text-red-600 mt-20 font-bold'>Invalid Member ID!</div>";
    exit;
}

// UPDATE MEMBER
if(isset($_POST['update_member'])){
    $name = trim($_POST['name']);
    $relation = trim($_POST['relation']);
    $gender = trim($_POST['gender']);
    $occupation = trim($_POST['occupation']);
    $dob = $_POST['dob'] ?? null;
    $marital_status = $_POST['marital_status'] ?? '';
    $photo = $member['photo'];

    // New Photo Upload
    if(!empty($_FILES['photo']['name'])){
        if($photo && file_exists("uploads/family/".$photo)) unlink("uploads/family/".$photo);
        $photo = time().'_'.basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/family/".$photo);
    }

    $stmt = $con->prepare("UPDATE tbl_family_members 
        SET name=?, relation=?, gender=?, occupation=?, dob=?, marital_status=?, photo=? 
        WHERE id=? AND user_id=?");

    $stmt->bind_param("sssssssii", 
        $name, $relation, $gender, $occupation, $dob, $marital_status, $photo, $id, $user_id
    );
    $stmt->execute();

    echo "<script> window.location='family';</script>";
}
?>

<main class="flex-1 px-2 md:px-10 py-10 bg-white md:ml-20 max-w-8xl overflow-hidden">
    <div class="w-full max-w-3xl mx-auto bg-white rounded-2xl border border-orange-200 shadow-lg mt-6 py-7 px-5">
        <h2 class="text-lg font-bold text-orange-700 mb-4 flex items-center gap-2">
            <i class="fa fa-edit"></i> Edit Family Member
        </h2>

        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4">

            <div class="flex items-center gap-4">
                <label class="relative cursor-pointer">
                    <input type="file" name="photo" class="hidden" id="photoInput" accept="image/*">
                    <img id="photoPreview" 
                        src="<?= !empty($member['photo']) && file_exists('uploads/family/'.$member['photo']) 
                            ? 'uploads/family/'.$member['photo'] 
                            : 'https://via.placeholder.com/100x100?text=Photo'; ?>"
                        class="w-28 h-28 rounded-full border border-orange-300 object-cover" />
                    <span class="absolute bottom-1 right-1 bg-orange-500 text-white p-2 rounded-full text-xs">
                        <i class="fa fa-camera"></i>
                    </span>
                </label>

                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Name</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($member['name']); ?>" required
                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-orange-300 outline-none">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Relation</label>
                        <input type="text" name="relation" value="<?= htmlspecialchars($member['relation']); ?>" required
                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-orange-300 outline-none">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Gender</label>
                        <select name="gender"
                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-orange-300 outline-none" required>
                            <option <?= $member['gender']=='Male'?'selected':''; ?>>Male</option>
                            <option <?= $member['gender']=='Female'?'selected':''; ?>>Female</option>
                            <option <?= $member['gender']=='Other'?'selected':''; ?>>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Occupation</label>
                        <input type="text" name="occupation" value="<?= htmlspecialchars($member['occupation']); ?>"
                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-orange-300 outline-none">
                    </div>

                    <!-- DOB Field -->
                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Date of Birth</label>
                        <input type="date" name="dob" value="<?= $member['dob']; ?>"
                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-orange-300 outline-none">
                    </div>

                    <!-- Marital Status Field -->
                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Marital Status</label>
                        <select name="marital_status"
                            class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-orange-300 outline-none">
                            <option hidden>Select Status</option>
                            <option <?= $member['marital_status']=='Unmarried'?'selected':''; ?>>Unmarried</option>
                            <option <?= $member['marital_status']=='Married'?'selected':''; ?>>Married</option>
                            <option <?= $member['marital_status']=='Divorced'?'selected':''; ?>>Divorced</option>
                            <option <?= $member['marital_status']=='Widow'?'selected':''; ?>>Widow</option>
                        </select>
                    </div>

                </div>
            </div>

            <div class="flex gap-3 mt-5">
                <button type="submit" name="update_member"
                    class="bg-orange-500 hover:bg-orange-600 text-white font-bold py-2 px-5 rounded-lg shadow transition flex items-center gap-2">
                    <i class="fa fa-save"></i> Update
                </button>

                <a href="family.php" 
                    class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold py-2 px-5 rounded-lg flex items-center gap-2">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
            </div>

        </form>
    </div>
</main>

<script>
document.getElementById('photoInput').addEventListener('change', function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(ev){
            document.getElementById('photoPreview').src = ev.target.result;
        }
        reader.readAsDataURL(file);
    }
});
</script>
