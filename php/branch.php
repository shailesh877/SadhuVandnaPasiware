<?php
include("header.php");
include("connection.php");

// Fetch all branches
$branches = mysqli_query($con, "SELECT * FROM tbl_branch ORDER BY id DESC");
?>
<title>Sadhu Vandana | Branches</title>
<main class="flex-1 px-4 md:px-10 py-10 bg-[#faf9f7] md:ml-20 mb-14 md:mb-0 overflow-hidden">

    <!-- Header -->
    <section class="py-3 mb-5 border-b border-orange-100 text-left">
        <h3 class="font-extrabold text-2xl text-orange-700 flex items-center gap-2 m-0 tracking-wide">
            <i class="fa-solid fa-sitemap"></i> Branches
        </h3>
    </section>

    <!-- Branch Cards -->
    <section class="flex flex-col gap-8 mt-3 max-w-7xl pb-10">

        <?php while($row = mysqli_fetch_assoc($branches)) { ?>

        <div class="bg-white border border-orange-300 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 w-full mx-auto">

            <!-- TITLE / MAHANT NAME -->
            <h2 class="text-[24px] font-extrabold text-gray-900 leading-snug mb-1 tracking-tight px-6 pt-6">
                <?= htmlspecialchars($row['mahant_name']) ?>
            </h2>

            <!-- BRANCH NAME -->
            <p class="text-[16px] font-semibold text-gray-800 px-6 mb-1">
                Branch: <?= htmlspecialchars($row['branch_name']) ?>
            </p>

            <!-- VILLAGE & MOBILE -->
            <p class="text-[14px] text-orange-600 font-semibold px-6 mb-3">
                <i class="fa-solid fa-location-dot mr-1"></i> <?= htmlspecialchars($row['branch_village']) ?> |
                <i class="fa-solid fa-phone mr-1"></i> <?= htmlspecialchars($row['mahant_mobile']) ?>
            </p>

            <!-- IMAGE -->
            <?php if($row['photo'] && file_exists("uploads/branches/".$row['photo'])): ?>
            <div class="w-full cursor-pointer branch-image">
                <img src="uploads/branches/<?= $row['photo'] ?>" class=" mx-auto rounded-2xl max-h-[420px] object-cover" />
            </div>
            <?php endif; ?>

            <!-- DETAILS -->
            <div class="p-6">
                <p class="branch-text text-gray-700 text-[16px] leading-[1.65] mb-2 overflow-hidden max-h-28 transition-all duration-500">
                    <?= nl2br(htmlspecialchars($row['details'])) ?>
                </p>
                <button class="read-btn text-orange-600 font-semibold text-[15px] mt-1 hover:underline">
                    Read More
                </button>
            </div>

        </div>

        <?php } ?>

        <?php if(mysqli_num_rows($branches) == 0) { ?>
            <p class="text-center text-gray-500 text-xl font-semibold">
                No branches available.
            </p>
        <?php } ?>

    </section>

</main>

<!-- FULL IMAGE MODAL -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closeModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalImg" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>

<style>
.branch-text {
    overflow: hidden;
    max-height: 7rem; /* approx 4-5 lines */
    transition: max-height 0.5s ease;
}
</style>

<script>
// Read More / Less toggle function
function toggleDescription(text, btn) {
    if (text.style.maxHeight && text.style.maxHeight !== "7rem") {
        text.style.maxHeight = "7rem";
        btn.textContent = "Read More";
    } else {
        text.style.maxHeight = text.scrollHeight + "px";
        btn.textContent = "Read Less";
    }
}

// Loop through all branch cards
document.querySelectorAll(".p-6").forEach((container) => {
    const text = container.querySelector(".branch-text");
    const btn = container.querySelector(".read-btn");

    // Hide Read More button if text is short
    if (text.scrollHeight <= 112) { // 7rem
        btn.style.display = "none";
    } else {
        btn.onclick = function () {
            toggleDescription(text, btn);
        };
    }
});

// Image Modal
const modal = document.getElementById("imageModal");
const modalImg = document.getElementById("modalImg");
const closeModal = document.getElementById("closeModal");

document.querySelectorAll(".branch-image img").forEach((img) => {
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

modal.onclick = function(e){
    if(e.target === modal){
        modal.classList.add("hidden");
        modal.classList.remove("flex");
    }
};
</script>
