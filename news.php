<?php
include("header.php");
include("connection.php");



$ticker = mysqli_query($con, "SELECT title FROM tbl_news ORDER BY id DESC LIMIT 5");
if (!$ticker) {
    die("Ticker Query Failed: " . mysqli_error($con));
}
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
        <div id="newsContainer"></div>
        <div id="loader" class="text-center text-gray-500 hidden">Loading more news...</div>
    </section>

</main>

<!-- ✅ FULL IMAGE MODAL -->
<div id="imageModal" class="fixed inset-0 bg-black bg-opacity-80 hidden items-center justify-center z-50">
    <span id="closeModal" class="absolute top-5 right-5 text-white text-3xl cursor-pointer">&times;</span>
    <img id="modalImg" class="max-h-full max-w-full rounded-lg shadow-lg" />
</div>

<script>
let offset = 0;
const limit = 5;
let isLoading = false;
let hasMore = true;

function loadNews(isInitial = false) {
    if (isLoading || (!hasMore && !isInitial)) return;
    isLoading = true;
    document.getElementById('loader').classList.remove('hidden');

    if (isInitial) {
        offset = 0;
        hasMore = true;
        document.getElementById('newsContainer').innerHTML = '';
    }

    const formData = new FormData();
    formData.append('limit', limit);
    formData.append('offset', offset);

    fetch('fetch_news.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(html => {
        const trimmedHTML = html.trim();
        if (isInitial && (trimmedHTML === "" || trimmedHTML.includes("No news available"))) {
            document.getElementById('newsContainer').innerHTML = trimmedHTML || '<p class="text-center text-gray-500 text-xl font-semibold">No news available.</p>';
            hasMore = false;
        } else if (trimmedHTML === "") {
            hasMore = false;
        } else {
            document.getElementById('newsContainer').insertAdjacentHTML('beforeend', trimmedHTML);
            offset += limit;
            attachReadMoreListeners();
        }
    })
    .catch(err => console.error(err))
    .finally(() => {
        isLoading = false;
        document.getElementById('loader').classList.add('hidden');
    });
}

function attachReadMoreListeners() {
    document.querySelectorAll(".p-6").forEach((container) => {
        const text = container.querySelector(".premium-text");
        const btn = container.querySelector(".read-btn");

        if (!text || !btn || btn.dataset.attached) return; 

        btn.dataset.attached = "true"; // Prevent double attachment

        if (text.scrollHeight <= 112) {
            btn.style.display = "none";
        } else {
            btn.addEventListener("click", function () {
                if (text.classList.contains("expanded")) {
                    text.style.maxHeight = "7rem";
                    text.classList.remove("expanded");
                    btn.textContent = "Read More";
                } else {
                    text.style.maxHeight = text.scrollHeight + "px";
                    text.classList.add("expanded");
                    btn.textContent = "Read Less";
                }
            });
        }
    });
}

// Infinite Scroll
window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        loadNews();
    }
});

// Initial Load
document.addEventListener("DOMContentLoaded", function () {
    loadNews(true);
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
