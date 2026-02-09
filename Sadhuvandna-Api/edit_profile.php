<?php
include("header.php");
include("connection.php");

// Logged-in user
$user_email = $_SESSION['sadhu_user_id'] ?? '';
if(!$user_email){
    header("Location: login.php");
    exit;
}

$user = $con->query("SELECT * FROM tbl_members WHERE email='$user_email'")->fetch_assoc();
?>

<main class="flex-1 px-2 md:px-10 py-10 bg-white md:ml-20 mb-13 md:mb-0 max-w-8xl overflow-hidden">
    <form action="update_profile.php" method="POST" enctype="multipart/form-data"
        class="bg-white rounded-2xl shadow-2xl p-8 border border-orange-200 flex flex-col items-center gap-6 max-w-7xl mx-auto mt-6">

        <!-- Cover Photo -->
        <label class="w-full relative mb-5 cursor-pointer group">
            <?php if(!empty($user['cover_photo']) && file_exists("uploads/photo/".$user['cover_photo'])): ?>
                <img id="coverPreview" src="uploads/photo/<?php echo $user['cover_photo']; ?>" class="w-full h-32 md:h-44 object-cover rounded-xl" />
            <?php else: ?>
                <div id="coverPreview" class="w-full h-32 md:h-44 bg-gray-100 flex items-center justify-center text-gray-400 rounded-xl text-2xl">Cover</div>
            <?php endif; ?>
            <span
                class="absolute bottom-2 right-3 bg-white/80 border border-orange-400 text-orange-700 p-2 rounded-full shadow flex items-center gap-2 text-xs group-hover:bg-orange-400 group-hover:text-white transition">
                <i class="fa fa-camera"></i> Edit Cover
            </span>
            <input type="file" name="cover_photo" id="coverPhoto" accept="image/*" class="hidden" />
        </label>

        <!-- Profile Photo -->
        <label class="relative -mt-14 md:-mt-20 border-4 border-orange-100 bg-white rounded-full p-1 shadow-xl cursor-pointer group">
            <?php if(!empty($user['profile_photo']) && file_exists("uploads/photo/".$user['profile_photo'])): ?>
                <img id="profilePreview" src="uploads/photo/<?php echo $user['profile_photo']; ?>" class="w-28 h-28 md:w-32 md:h-32 object-cover rounded-full" />
            <?php else: ?>
                <div id="profilePreview" class="w-28 h-28 md:w-32 md:h-32 bg-orange-200 text-white font-bold flex items-center justify-center text-3xl rounded-full">
                    <?php echo strtoupper($user['name'][0] ?? 'U'); ?>
                </div>
            <?php endif; ?>
            <span
                class="absolute bottom-2 right-1 bg-white/80 border border-orange-400 text-orange-700 p-2 rounded-full shadow group-hover:bg-orange-400 group-hover:text-white transition text-xs">
                <i class="fa fa-camera"></i>
            </span>
            <input type="file" name="profile_photo" id="profilePhoto" accept="image/*" class="hidden" />
        </label>

        <!-- Profile Fields -->
        <div class="w-full flex flex-col gap-6 mt-2">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Full Name</label>
                    <input type="text" name="name" class="border rounded-lg px-4 py-2 w-full" placeholder="Full Name" value="<?php echo htmlspecialchars($user['name']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Date of Birth</label>
                    <input type="date" name="dob" class="border rounded-lg px-4 py-2 w-full" value="<?php echo $user['dob'] ?? ''; ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Gender</label>
                    <select name="gender" class="border rounded-lg px-4 py-2 w-full">
                        <option value="Male" <?php if($user['gender']=='Male') echo 'selected'; ?>>Male</option>
                        <option value="Female" <?php if($user['gender']=='Female') echo 'selected'; ?>>Female</option>
                        <option value="Other" <?php if($user['gender']=='Other') echo 'selected'; ?>>Other</option>
                    </select>
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Marital Status</label>
                    <select name="maritial_status" class="border rounded-lg px-4 py-2 w-full">
                        <option value="">Select Maritial Status</option>
                        <option value="Unmarried" <?php if($user['maritial_status']=='Unmarried') echo 'selected'; ?>>Unmarried</option>
                        <option value="Married" <?php if($user['maritial_status']=='Married') echo 'selected'; ?>>Married</option>
                        <option value="Divorced" <?php if($user['maritial_status']=='Divorced') echo 'selected'; ?>>Divorced</option>
                        <option value="Widowed" <?php if($user['maritial_status']=='Widowed') echo 'selected'; ?>>Widowed</option>
                    </select>
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Education</label>
                    <input type="text" name="education" class="border rounded-lg px-4 py-2 w-full" placeholder="Highest Qualification" value="<?php echo htmlspecialchars($user['education']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Occupation</label>
                    <input type="text" name="occupation" class="border rounded-lg px-4 py-2 w-full" placeholder="Profession/Job" value="<?php echo htmlspecialchars($user['occupation']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Phone Number</label>
                    <input type="text" name="mobile" class="border rounded-lg px-4 py-2 w-full" placeholder="Mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Email</label>
                    <input type="email" name="email" class="border rounded-lg px-4 py-2 w-full" readonly placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" />
                    
                </div>
                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Cast/Community</label>
                    <input type="text" name="cast" class="border rounded-lg px-4 py-2 w-full"  placeholder="community" value="<?php echo htmlspecialchars($user['cast']); ?>" />
                    
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Address (Present)</label>
                    <input type="text" name="address" class="border rounded-lg px-4 py-2 w-full" placeholder="Current Address" value="<?php echo htmlspecialchars($user['address']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">City</label>
                    <input type="text" name="city" class="border rounded-lg px-4 py-2 w-full" placeholder="City" value="<?php echo htmlspecialchars($user['city']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">State</label>
                    <input type="text" name="state" class="border rounded-lg px-4 py-2 w-full" placeholder="State" value="<?php echo htmlspecialchars($user['state']); ?>" />
                </div>

                <div>
                    <label class="text-md font-bold text-orange-700 mb-1">Hobbies & Interests</label>
                    <input type="text" name="hobbi" class="border rounded-lg px-4 py-2 w-full" placeholder="Singing, Reading..." value="<?php echo htmlspecialchars($user['hobbi']); ?>" />
                </div>
                <div>
                <label class="text-md font-bold text-orange-700 mb-1">About Me / Biodata</label>
                <textarea name="about" class="border rounded-lg px-4 py-2 w-full min-h-[60px]" placeholder="Your bio..."><?php echo htmlspecialchars($user['about']); ?></textarea>
            </div>
            </div>

            
        </div>

        <!-- Buttons -->
        <div class="w-full flex flex-col md:flex-row gap-4 mt-8">
            <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-7 py-3 rounded-lg font-bold w-full shadow transition flex items-center gap-2 justify-center">
                <i class="fa fa-save"></i> Save Profile
            </button>
            <a href="profile.php" class="bg-orange-100 text-orange-700 font-bold border border-orange-300 hover:bg-orange-200 px-7 py-3 rounded-lg w-full flex items-center gap-2 justify-center shadow transition">
                Back <i class="fa fa-arrow-right"></i>
            </a>
        </div>
    </form>
</main>

<script>
    // Profile photo preview
    document.getElementById('profilePhoto').addEventListener('change', function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(e){
                const img = document.getElementById('profilePreview');
                if(img.tagName === 'IMG'){
                    img.src = e.target.result;
                } else {
                    img.innerHTML = '';
                    const image = document.createElement('img');
                    image.src = e.target.result;
                    image.className = "w-28 h-28 md:w-32 md:h-32 object-cover rounded-full";
                    img.appendChild(image);
                }
            }
            reader.readAsDataURL(file);
        }
    });

    // Cover photo preview
    document.getElementById('coverPhoto').addEventListener('change', function(e){
        const file = e.target.files[0];
        if(file){
            const reader = new FileReader();
            reader.onload = function(e){
                const preview = document.getElementById('coverPreview');
                if(preview.tagName === 'IMG'){
                    preview.src = e.target.result;
                } else {
                    preview.innerHTML = '';
                    const image = document.createElement('img');
                    image.src = e.target.result;
                    image.className = "w-full h-32 md:h-44 object-cover rounded-xl";
                    preview.appendChild(image);
                }
            }
            reader.readAsDataURL(file);
        }
    });
</script>
