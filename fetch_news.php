<?php
include("connection.php");

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

$news = mysqli_query($con, "SELECT * FROM tbl_news ORDER BY id DESC LIMIT $limit OFFSET $offset");

if(!$news || mysqli_num_rows($news) == 0){
    if($offset == 0) echo "<p class='text-center text-gray-500 text-xl font-semibold'>No news available.</p>";
    exit;
}

while($row = mysqli_fetch_assoc($news)) { 
    $images = array_filter(explode(",", $row['image']));
?>

<div class="bg-white border border-orange-300 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 w-full md:max-w-3xl mx-auto mb-10">

    <!-- TITLE -->
    <h2 class="text-[26px] font-extrabold text-gray-900 leading-snug mb-1 tracking-tight px-6 pt-6">
        <?= htmlspecialchars($row['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
    </h2>

    <!-- DATE -->
    <p class="text-[13px] text-orange-600 font-semibold mb-3 tracking-wide px-6">
        <i class="fa fa-calendar mr-1"></i>
        <?= date("F d, Y", strtotime($row['created_at'])) ?>
    </p>

    <!-- ✅ MANUAL IMAGE SLIDER -->
    <?php if(count($images) > 0){ ?>
    <div class="w-full cursor-pointer relative overflow-hidden news-slider" data-index="0">

        <?php foreach($images as $k => $img) { ?>
            <img src="uploads/news/<?= $img ?>" 
                 class="slide-img w-full rounded-2xl mx-auto max-h-[420px] object-cover <?= $k==0 ? '' : 'hidden' ?>" />
        <?php } ?>

        <!-- ✅ Number Buttons -->
        <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-2 bg-black/40 px-3 py-1 rounded-full">
            <?php foreach($images as $k => $img) { ?>
                <button onclick="goToSlide(this.closest('.news-slider'), <?= $k ?>)"
                    class="w-6 h-6 text-xs rounded-full bg-white/80 hover:bg-orange-500 hover:text-white transition">
                    <?= $k+1 ?>
                </button>
            <?php } ?>
        </div>

        <!-- ✅ Prev / Next Buttons -->
        <button onclick="prevSlide(this.closest('.news-slider'))"
            class="absolute left-3 top-1/2 -translate-y-1/2 bg-black/40 text-white w-8 h-8 rounded-full hover:bg-orange-500">
            ‹
        </button>

        <button onclick="nextSlide(this.closest('.news-slider'))"
            class="absolute right-3 top-1/2 -translate-y-1/2 bg-black/40 text-white w-8 h-8 rounded-full hover:bg-orange-500">
            ›
        </button>

    </div>
    <?php } ?>

    <!-- DESCRIPTION -->
    <div class="p-6">
        <p class="premium-text text-gray-700 text-[17px] leading-[1.65] mb-2 overflow-hidden max-h-28 transition-all duration-500">
            <?= nl2br(htmlspecialchars($row['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) ?>
        </p>
        <button class="read-btn text-orange-600 font-semibold text-[15px] mt-1 hover:underline">
            Read More
        </button>
    </div>

</div>

<?php } ?>
