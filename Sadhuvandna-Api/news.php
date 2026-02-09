<?php
include("header.php");
include("connection.php");

$news   = mysqli_query($con, "SELECT * FROM tbl_news ORDER BY id DESC");
$ticker = mysqli_query($con, "SELECT title FROM tbl_news ORDER BY id DESC LIMIT 5");
?>
<style>
/* ===== PREMIUM TICKER DESIGN ===== */
.news-ticker {
  background: linear-gradient(90deg, #b45309, #f97316, #b45309);
  border-radius: 10px;
  box-shadow: 0 8px 22px rgba(0,0,0,0.25);
  max-width: 100%;
}

.ticker-inner {
  display: flex;
  align-items: center;
  height: 44px;
  overflow: hidden;
}

.ticker-label {
  background: linear-gradient(180deg, #ec4912ff, #ea580c);
  color: #fff;
  font-weight: 800;
  font-size: 13px;
  letter-spacing: 1px;
  padding: 0 16px;
  height: 100%;
  display: flex;
  align-items: center;
  border-right: 2px solid rgba(255,255,255,0.3);
  text-transform: uppercase;
}

.ticker-text {
  font-size: 15px;
  font-weight: 600;
  color: #fff;
  padding: 0 20px;
  letter-spacing: 0.4px;
  text-shadow: 0 1px 3px rgba(0,0,0,0.5);
  white-space: nowrap;
}


body {
    overflow-x: hidden;
}


</style>
<main class="flex-1 px-4 md:px-10 py-10 bg-[#faf9f7] md:ml-20 mb-14 md:mb-0">
<!-- ✅ TOP NEWS TICKER (WORKING STICKY) -->
<!-- ✅ PREMIUM STICKY MARQUEE TICKER -->
<section class="news-ticker sticky top-[45px] md:top-[45px] z-40 w-full  overflow-hidden mb-4">

    <div class="ticker-inner">
        <div class="ticker-label">
            Latest
        </div>

        <marquee behavior="scroll" direction="left" scrollamount="5"
                 onmouseover="this.stop();" onmouseout="this.start();">

            <!-- First Loop -->
            <?php 
            mysqli_data_seek($ticker, 0); 
            while($t = mysqli_fetch_assoc($ticker)) { ?>
                <span class="ticker-text">
                    📰 <?= $t['title'] ?>
                </span>
            <?php } ?>

            <!-- Second Loop (Seamless) -->
            <?php 
            mysqli_data_seek($ticker, 0); 
            while($t = mysqli_fetch_assoc($ticker)) { ?>
                <span class="ticker-text">
                    📰 <?= $t['title'] ?>
                </span>
            <?php } ?>

        </marquee>
    </div>

</section>




  

    <!-- Header -->
    <section class="py-3 mb-5 border-b border-orange-100 text-left">
        <h3 class="font-extrabold text-2xl text-orange-700 flex items-center gap-2 m-0 tracking-wide">
            <i class="fa fa-newspaper"></i> Latest News
        </h3>
    </section>

    <!-- News Section -->
    <section class="flex flex-col gap-10 mt-3 pb-10">

        <?php while($row = mysqli_fetch_assoc($news)) { 
            $images = array_filter(explode(",", $row['image']));
        ?>

        <div class="bg-white border border-orange-300 rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 w-full md:max-w-3xl mx-auto">

            <!-- TITLE -->
            <h2 class="text-[26px] font-extrabold text-gray-900 leading-snug mb-1 tracking-tight px-6 pt-6">
                <?= $row['title'] ?>
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
                    <?= nl2br($row['description']) ?>
                </p>
                <button class="read-btn text-orange-600 font-semibold text-[15px] mt-1 hover:underline">
                    Read More
                </button>
            </div>

        </div>

        <?php } ?>

        <?php if(mysqli_num_rows($news) == 0) { ?>
            <p class="text-center text-gray-500 text-xl font-semibold">
                No news available.
            </p>
        <?php } ?>

    </section>

</main>

<!-- ✅ FULL IMAGE MODAL -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closeModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalImg" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>



<script>
// ✅ READ MORE / LESS
document.querySelectorAll(".p-6").forEach((container) => {
    const text = container.querySelector(".premium-text");
    const btn = container.querySelector(".read-btn");

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

// ✅ MANUAL SLIDER LOGIC
function showSlide(slider, index){
    const slides = slider.querySelectorAll(".slide-img");
    slides.forEach(img => img.classList.add("hidden"));
    slides[index].classList.remove("hidden");
    slider.dataset.index = index;
}

function nextSlide(slider){
    const slides = slider.querySelectorAll(".slide-img");
    let index = parseInt(slider.dataset.index || 0);
    index = (index + 1) % slides.length;
    showSlide(slider, index);
}

function prevSlide(slider){
    const slides = slider.querySelectorAll(".slide-img");
    let index = parseInt(slider.dataset.index || 0);
    index = (index - 1 + slides.length) % slides.length;
    showSlide(slider, index);
}

function goToSlide(slider, index){
    showSlide(slider, index);
}

// ✅ IMAGE MODAL
const modal = document.getElementById("imageModal");
const modalImg = document.getElementById("modalImg");
const closeModal = document.getElementById("closeModal");

document.addEventListener("click", function(e){
    if(e.target.classList.contains("slide-img")){
        modalImg.src = e.target.src;
        modal.classList.remove("hidden");
        modal.classList.add("flex");
    }
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
