<?php
include("header.php");
include("connection.php");

$user_email = $_SESSION['sadhu_user_id'] ?? '';
if (!$user_email) { header("Location: login.php"); exit; }

$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
$user_id = $user['id'];

/* ================= ADD MEMBER ================= */
if(isset($_POST['save_member'])){
    $name = trim($_POST['name']);
    $relation = trim($_POST['relation']);
    $gender = $_POST['gender'];
    $occupation = $_POST['occupation'];
    $dob = $_POST['dob'];
    $marital_status = $_POST['marital_status'];

    $height = $_POST['height'];
    $weight = $_POST['weight'];
    $education = $_POST['education'];
    $income = $_POST['income'];
    $caste = $_POST['caste'];
    $kuldevi = $_POST['kuldevi'];

    $photo = '';
    if(!empty($_FILES['photo']['name'])){
        $photo = time().'_'.basename($_FILES['photo']['name']);
        move_uploaded_file($_FILES['photo']['tmp_name'], "uploads/family/".$photo);
    }

    $stmt = $con->prepare("INSERT INTO tbl_family_members 
    (user_id,name,relation,gender,occupation,photo,dob,marital_status,
     height,weight,education,income,caste,kuldevi)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

    $stmt->bind_param(
        "isssssssssssss",
        $user_id,$name,$relation,$gender,$occupation,$photo,$dob,$marital_status,
        $height,$weight,$education,$income,$caste,$kuldevi
    );
    $stmt->execute();

    echo "<script>window.location='family';</script>";
}

/* ================= DELETE ================= */
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];
    $m = $con->query("SELECT * FROM tbl_family_members WHERE id='$id' AND user_id='$user_id'")->fetch_assoc();
    if($m && $m['photo'] && file_exists("uploads/family/".$m['photo'])){
        unlink("uploads/family/".$m['photo']);
    }
    $con->query("DELETE FROM tbl_family_members WHERE id='$id' AND user_id='$user_id'");
    echo "<script>window.location='family';</script>";
}

$members = $con->query("SELECT * FROM tbl_family_members WHERE user_id='$user_id' ORDER BY id DESC");
?>

<main class="flex-1 px-2 md:px-10 py-10 bg-white md:ml-20">
<div class="max-w-7xl mx-auto bg-white rounded-2xl border border-orange-200 shadow-lg py-6 px-5">

<!-- TOGGLE -->
<button id="toggleMemberForm"
class="mb-6 bg-orange-500 text-white font-bold py-3 rounded-lg w-full">
<i class="fa fa-plus"></i> Add New Member
</button>

<!-- ================= FORM ================= -->
<div id="memberFormContainer" class="hidden">

<form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4 md:gap-6 mt-4">

<div class="flex items-center gap-4 grid grid-cols-1">

<!-- PHOTO -->
<label class="relative cursor-pointer mx-auto">
    <input type="file" name="photo" class="hidden" id="memberPhotoInput" accept="image/*" required>
    <img id="memberPhotoPreview"
         src="https://via.placeholder.com/100x100?text=Photo"
         class="w-28 h-28 rounded-full border border-orange-300 object-cover" />
    <span class="absolute bottom-1 right-1 bg-orange-500 text-white p-2 rounded-full text-xs">
        <i class="fa fa-camera"></i>
    </span>
</label>

<!-- FIELDS -->
<div class="flex-1 grid grid-cols-1 md:grid-cols-2 gap-3">

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Name *</label>
<input type="text" name="name" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Relation *</label>
<input type="text" name="relation" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Gender *</label>
<select name="gender" required class="w-full border rounded px-3 py-2">
<option hidden>Select Gender</option>
<option>Male</option>
<option>Female</option>
<option>Other</option>
</select>
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Marital Status *</label>
<select name="marital_status" required class="w-full border rounded px-3 py-2">
<option hidden>Select Status</option>
<option>Unmarried</option>
<option>Married</option>
<option>Divorced</option>
<option>Widow</option>
</select>
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Date of Birth *</label>
<input type="date" name="dob" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Occupation *</label>
<input type="text" name="occupation" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Height *</label>
<input type="text" name="height" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Weight *</label>
<input type="text" name="weight" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Education *</label>
<input type="text" name="education" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Monthly Income *</label>
<input type="text" name="income" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Caste / Samaj *</label>
<input type="text" name="caste" required class="w-full border rounded px-3 py-2">
</div>

<div>
<label class="block mb-1 text-sm font-bold text-orange-600">Kuldevi *</label>
<input type="text" name="kuldevi" required class="w-full border rounded px-3 py-2">
</div>

</div>
</div>

<button type="submit" name="save_member"
class="mt-2 bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-lg shadow">
<i class="fa fa-plus"></i> Save Member
</button>

</form>
</div>

<!-- ================= LIST ================= -->
<div class="mt-8 space-y-5">

<?php while($m=$members->fetch_assoc()):
$age='';
if($m['dob']) $age=date_diff(date_create($m['dob']),date_create())->y;
?>

<div class="p-4 bg-orange-50 rounded-xl border border-orange-200 shadow-sm">
<div class="flex items-start gap-4">

<img src="<?= $m['photo']?'uploads/family/'.$m['photo']:'https://via.placeholder.com/80'; ?>"
onclick="openImageModal(this.src)"
class="w-20 h-20 rounded-full border cursor-pointer object-cover">

<div class="flex-1 text-sm leading-6">
<b>Name:</b> <?= $m['name']; ?><br>
<b>Relation:</b> <?= $m['relation']; ?><br>
<b>Gender:</b> <?= $m['gender']; ?><br>
<b>DOB / Age:</b>
<?= $m['dob'] ? date("d-m-Y",strtotime($m['dob'])) : 'N/A'; ?>
<?= $age ? " / $age Years" : ''; ?><br>
<b>Height:</b> <?= $m['height']; ?><br>
<b>Weight:</b> <?= $m['weight']; ?><br>
<b>Education:</b> <?= $m['education']; ?><br>
<b>Occupation:</b> <?= $m['occupation']; ?><br>
<b>Income:</b> <?= $m['income']; ?><br>
<b>Marital Status:</b> <?= $m['marital_status']; ?><br>
<b>Caste / Samaj:</b> <?= $m['caste']; ?><br>
<b>Kuldevi:</b> <?= $m['kuldevi']; ?>
</div>

<div class="flex flex-col gap-3">
<a href="edit_family.php?id=<?= $m['id']; ?>" class="text-blue-600">
<i class="fa fa-edit"></i>
</a>
<a href="?delete=<?= $m['id']; ?>" onclick="return confirm('Delete this member?')" class="text-red-600">
<i class="fa fa-trash"></i>
</a>
</div>

</div>
</div>
<?php endwhile; ?>

</div>
</div>
</main>

<!-- ================= IMAGE MODAL ================= -->
<div id="imageModal" class="modal" onclick="closeImageModal()">
<span class="close">&times;</span>
<img class="modal-content" id="modalImage">
</div>

<style>
.modal{
display:none; position:fixed; z-index:999;
left:0; top:0; width:100%; height:100%;
background:rgba(0,0,0,.8); text-align:center;
}
.modal-content{
margin-top:60px;
max-width:90%; max-height:85vh; border-radius:10px;
}
.close{
position:absolute; top:20px; right:35px;
color:white; font-size:40px; cursor:pointer;
}
</style>

<script>
toggleMemberForm.onclick=()=>{
memberFormContainer.classList.toggle("hidden");
};
memberPhotoInput.onchange=e=>{
let r=new FileReader();
r.onload=()=>memberPhotoPreview.src=r.result;
r.readAsDataURL(e.target.files[0]);
};
function openImageModal(src){
modalImage.src=src;
imageModal.style.display="block";
}
function closeImageModal(){
imageModal.style.display="none";
}
</script>
