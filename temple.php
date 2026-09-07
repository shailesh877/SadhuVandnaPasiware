<?php
include("header.php");
include("connection.php");

// Fetch all temples
$temples = mysqli_query($con, "SELECT * FROM tbl_temple ORDER BY temple_id DESC");
?>

<main class="flex-1 px-4 md:px-10 py-15 bg-[#faf9f7] md:ml-20 mb-14 md:mb-0 overflow-hidden pt-5">


    <section class="py-5 mt-4 border-b border-orange-100 text-left">
        <h3 class="font-extrabold text-2xl text-orange-700 flex items-center gap-2 m-0 tracking-wide">
          <i class="fa-solid fa-church text-2xl mb-1"></i> All Temples
        </h3>
    </section>      

    <div class="max-w-8xl mx-auto flex flex-col gap-8">

        <?php while($row = mysqli_fetch_assoc($temples)) { ?>
        <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 w-full border-1 border-orange-300 mx-auto overflow-hidden">

            <!-- Name + Mobile -->
            <div class="px-6 pt-6 flex flex-col md:flex-row md:justify-between md:items-center gap-1">
                <h2 class="text-2xl font-extrabold text-gray-900"><?= $row['mahant_name'] ?></h2>
                <p class="text-sm text-gray-600 md:ml-4"><i class="fa fa-phone mr-1"></i><?= $row['mobile'] ?></p>
            </div>

            <!-- IMAGE -->
            <div class="w-full mt-4 cursor-pointer temple-image">
                <?php if ($row['photo'] != "") { ?>
                    <img src="uploads/temple/<?= $row['photo'] ?>" class="mx-auto rounded-2xl max-h-[420px] object-cover">
                <?php } else { ?>
                    <div class="w-full h-64 bg-gray-100 flex items-center justify-center text-gray-300">
                        <i class="fa-solid fa-user-tie text-5xl"></i>
                    </div>
                <?php } ?>
            </div>

            <!-- Details below image -->
            <div class="px-6 py-4 flex flex-col gap-1">
                <p class="text-sm text-gray-600"><i class="fa fa-map-marker-alt mr-1"></i> Village: <?= $row['village'] ?></p>
                <p class="text-sm text-gray-600"><i class="fa fa-map-location-dot mr-1"></i> Taluka: <?= $row['taluka'] ?></p>
                <p class="text-sm text-gray-600"><i class="fa fa-city mr-1"></i> District: <?= $row['district'] ?></p>
                <p class="text-xs text-orange-600 mt-1"><i class="fa fa-calendar mr-1"></i><?= date("F d, Y", strtotime($row['created_at'])) ?></p>
            </div>

            <!-- DESCRIPTION -->
            <?php if (!empty($row['description'])) { ?>
            <div class="px-6 pb-6">
                <p class="temple-text text-gray-700 text-[16px] leading-[1.6] mb-2 overflow-hidden max-h-28 transition-all duration-500">
                    <?= nl2br($row['description']) ?>
                </p>
                <button class="read-btn text-orange-600 font-semibold text-[15px] mt-1 hover:underline">Read More</button>
            </div>
            <?php } ?>

        </div>
        <?php } ?>

        <?php if(mysqli_num_rows($temples) == 0) { ?>
            <p class="text-center text-gray-500 text-xl font-semibold">No temples available.</p>
        <?php } ?>

    </div>

</main>

<!-- IMAGE MODAL -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closeModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalImg" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>

<style>
.temple-text {
    overflow: hidden;
    max-height: 7rem; /* 4-5 lines */
    transition: max-height 0.5s ease;
}
</style>

<script>
// Read More toggle
document.querySelectorAll(".read-btn").forEach((btn) => {
    const text = btn.previousElementSibling;
    if (text.scrollHeight <= 112) {
        btn.style.display = "none";
    } else {
        btn.onclick = function () {
            if (text.style.maxHeight && text.style.maxHeight !== "7rem") {
                text.style.maxHeight = "7rem";
                btn.textContent = "Read More";
            } else {
                text.style.maxHeight = text.scrollHeight + "px";
                btn.textContent = "Read Less";
            }
        };
    }
});

// Image modal
const modal = document.getElementById("imageModal");
const modalImg = document.getElementById("modalImg");
const closeModal = document.getElementById("closeModal");

document.querySelectorAll(".temple-image img").forEach((img) => {
    img.onclick = function () {
        modalImg.src = this.src;
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    };
});

closeModal.onclick = function () {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
};

modal.onclick = function(e) {
    if(e.target === modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
};
</script>
