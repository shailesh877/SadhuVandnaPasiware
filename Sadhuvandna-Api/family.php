<?php
include("header.php");
include("connection.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if (!$user_email) { header("Location: login.php"); exit; }

$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];

// ADD MEMBER
if(isset($_POST['save_member'])){
    $name = trim($_POST['name']);
    $relation = trim($_POST['relation']);
    $gender = trim($_POST['gender']);
    $occupation = trim($_POST['occupation']);
    $dob = $_POST['dob'] ?? null;
    $marital_status = $_POST['marital_status'] ?? '';

    $photo = '';

    if(!empty($_FILES['photo']['name'])){
        $photo = time().'_'.basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/family/".$photo);
    }

    $stmt = $con->prepare("INSERT INTO tbl_family_members 
    (user_id, name, relation, gender, occupation, photo, dob, marital_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", 
    $user_id, $name, $relation, $gender, $occupation, $photo, $dob, $marital_status);
    $stmt->execute();

     echo "<script> window.location='family';</script>";
}

// DELETE MEMBER
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $member = $con->query("SELECT * FROM tbl_family_members WHERE id='$id' AND user_id='$user_id'")->fetch_assoc();

    if($member && !empty($member['photo']) && file_exists("uploads/family/".$member['photo'])){
        unlink("uploads/family/".$member['photo']);
    }

    $con->query("DELETE FROM tbl_family_members WHERE id='$id' AND user_id='$user_id'");
    echo "<script> window.location='family';</script>";
}

// FETCH ALL MEMBERS
$members = $con->query("SELECT * FROM tbl_family_members WHERE user_id='$user_id' ORDER BY id DESC");
?>

<main class="flex-1 px-2 md:px-10 py-10 bg-white md:ml-20 max-w-8xl overflow-hidden">
    <div class="w-full max-w-7xl mx-auto bg-white rounded-2xl border border-orange-200 shadow-lg mt-6 py-7 px-5">

        <!-- TOGGLE BUTTON -->
        <button type="button" id="toggleMemberForm"
            class="mb-6 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg shadow transition flex items-center gap-2 justify-center w-full">
            <i class="fa fa-plus"></i> Add New Member
        </button>

        <!-- Add Member Form (HIDDEN BY DEFAULT) -->
        <div id="memberFormContainer" class="hidden" style="max-height:0; overflow:hidden; transition:max-height .3s ease;">
        
        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4 md:gap-6 mt-4">

            <div class="flex items-center gap-4 grid grid-cols-1">
                <label class="relative cursor-pointer mx-auto">
                    <input type="file" name="photo" class="hidden" id="memberPhotoInput" accept="image/*">
                    <img id="memberPhotoPreview" src="https://via.placeholder.com/100x100?text=Photo"
                        class="w-28 h-28 rounded-full border border-orange-300 object-cover" />
                    <span class="absolute bottom-1 right-1 bg-orange-500 text-white p-2 rounded-full text-xs">
                        <i class="fa fa-camera"></i>
                    </span>
                </label>

                <div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Name</label>
                        <input type="text" name="name" placeholder="Enter Name"
                            class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Relation</label>
                        <input type="text" name="relation" placeholder="Enter Relation"
                            class="w-full border rounded px-3 py-2" required>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Gender</label>
                        <select name="gender" class="w-full border rounded px-3 py-2" required>
                            <option hidden>Select Gender</option>
                            <option>Male</option>
                            <option>Female</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Date of Birth</label>
                        <input type="date" name="dob" class="w-full border rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Marital Status</label>
                        <select name="marital_status" class="w-full border rounded px-3 py-2">
                            <option hidden>Select Status</option>
                            <option>Unmarried</option>
                            <option>Married</option>
                            <option>Divorced</option>
                            <option>Widow</option>
                        </select>
                    </div>

                    <div>
                        <label class="block mb-1 text-sm font-bold text-orange-600">Occupation</label>
                        <input type="text" name="occupation" placeholder="Enter Occupation"
                            class="w-full border rounded px-3 py-2">
                    </div>

                </div>
            </div>

            <button type="submit" name="save_member"
                class="mt-2 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg shadow">
                <i class="fa fa-plus"></i> Save Member
            </button>

        </form>
        </div>

        <!-- Member List -->
        <div class="mt-8">
            <div class="font-bold text-orange-700 mb-4 flex items-center gap-2">
                <i class="fa fa-list"></i> Family Members
            </div>

            <div class="flex flex-col gap-4">

                <?php while($m = $members->fetch_assoc()): ?>
                    <div class="flex flex-wrap items-center p-3 bg-orange-50 rounded-xl border border-orange-100 shadow-sm gap-4">

                        <img 
                            src="<?= !empty($m['photo']) && file_exists('uploads/family/'.$m['photo']) 
                                ? 'uploads/family/'.$m['photo'] 
                                : 'https://via.placeholder.com/60x60?text=Photo'; ?>"
                            class="w-12 h-12 rounded-full border border-orange-300 object-cover cursor-pointer"
                            onclick="openImageModal(this.src)" 
                        />

                        <div class="flex-1 flex flex-wrap gap-2 items-center">
                            <span class="font-bold text-orange-700 text-base"><?= htmlspecialchars($m['name']); ?></span>
                            <span class="text-xs bg-orange-200 text-orange-700 rounded-full px-2 py-1"><?= htmlspecialchars($m['relation']); ?></span>
                            <span class="text-xs bg-orange-100 text-orange-700 rounded-full px-2 py-1"><?= htmlspecialchars($m['gender']); ?></span>
                            <span class="text-xs bg-orange-100 text-orange-700 rounded-full px-2 py-1"><?= htmlspecialchars($m['occupation']); ?></span>

                            <?php 
                                $age = '';
                                if(!empty($m['dob'])){
                                    $age = date_diff(date_create($m['dob']), date_create('today'))->y;
                                }
                            ?>

                            <span class="text-xs bg-orange-100 text-orange-700 rounded-full px-2 py-1">
                                DOB: <?= !empty($m['dob']) ? date("d M Y", strtotime($m['dob'])) : 'N/A'; ?>
                            </span>

                            <?php if($age !== ''): ?>
                                <span class="text-xs bg-orange-200 text-orange-700 rounded-full px-2 py-1">
                                    Age: <?= $age; ?> Years
                                </span>
                            <?php endif; ?>

                            <span class="text-xs bg-orange-200 text-orange-700 rounded-full px-2 py-1">
                                <?= htmlspecialchars($m['marital_status']); ?>
                            </span>
                        </div>

                        <div class="flex gap-3 ml-auto">
                            <a href="edit_family.php?id=<?= $m['id']; ?>" class="text-blue-500 hover:text-blue-700">
                                <i class="fa fa-edit"></i>
                            </a>
                            <a href="?delete=<?= $m['id']; ?>" onclick="return confirm('Delete this member?')" 
                                class="text-red-500 hover:text-red-700">
                                <i class="fa fa-trash"></i>
                            </a>
                        </div>

                    </div>
                <?php endwhile; ?>

            </div>
        </div>
    </div>
</main>

<!-- IMAGE MODAL -->
<div id="imageModal" class="modal" onclick="closeImageModal()">
  <span class="close">&times;</span>
  <img class="modal-content" id="modalImage">
</div>

<style>
.modal {
  display:none; position:fixed; z-index:1000; padding-top:60px;
  left:0; top:0; width:100%; height:100%;
  background:rgba(0,0,0,.8); text-align:center;
}
.modal-content { max-width:90%; max-height:80vh; border-radius:10px; }
.close {
  position:absolute; top:20px; right:35px; color:white;
  font-size:40px; cursor:pointer;
}
.close:hover { color:orange; }
</style>

<script>
function openImageModal(src){ 
  document.getElementById('modalImage').src = src;
  document.getElementById('imageModal').style.display = "block";
}
function closeImageModal(){ 
  document.getElementById('imageModal').style.display = "none";
}

document.getElementById('memberPhotoInput').addEventListener('change', function(e){
    const file = e.target.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = ev => document.getElementById('memberPhotoPreview').src = ev.target.result;
        reader.readAsDataURL(file);
    }
});

// TOGGLE FORM
document.getElementById("toggleMemberForm").addEventListener("click", function () {
    const box = document.getElementById("memberFormContainer");

    if (box.classList.contains("hidden")) {
        box.classList.remove("hidden");
        box.style.maxHeight = box.scrollHeight + "px";
    } else {
        box.style.maxHeight = "0px";
        setTimeout(() => box.classList.add("hidden"), 300);
    }
});
</script>
