<?php
include("header.php");
include("connection.php");

?>

<main class="flex-1 px-4 md:px-10 py-8 bg-[#f3f2ef] md:ml-20 mb-14 md:mb-0 overflow-hidden">

  <!-- HEADER -->
  <section class="py-5 mb-6 border-b border-gray-200">
    <h3 class="font-extrabold text-2xl text-gray-900 flex items-center gap-2 tracking-wide">
      <i class="fa-solid fa-briefcase text-orange-600"></i> Jobs & Education
    </h3>
  </section>

  <div class="max-w-6xl mx-auto flex flex-col gap-6">
    <div id="jobsContainer"></div>
    <div id="loader" class="text-center text-gray-500 hidden">Loading more...</div>
  </div>
</main>

<div id="imgModal"
     class="fixed inset-0 bg-black/90 hidden items-center justify-center z-[999] p-4">
  <img id="modalImage"
       class="max-w-full max-h-full rounded shadow-xl">
</div>

<script>
function openImgModal(src){
  document.getElementById("modalImage").src = src;
  document.getElementById("imgModal").classList.remove("hidden");
  document.getElementById("imgModal").classList.add("flex");
}

document.getElementById("imgModal").onclick = function(){
  this.classList.add("hidden");
  this.classList.remove("flex");
}
</script>


<style>
.job-text {
  position: relative;
  overflow: hidden;
  max-height: 112px;
  transition: max-height 0.4s ease;
}
/* Gradient fade effect for collapsed text */
.job-text::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 50px;
  background: linear-gradient(to top, white 10%, transparent);
  transition: opacity 0.3s;
  pointer-events: none;
}
/* Hide gradient when expanded or not needed */
.job-text.is-expanded::after,
.job-text.no-fade::after {
  opacity: 0;
}
</style>

<script>
let offset = 0;
const limit = 5;
let isLoading = false;
let hasMore = true;

function loadJobs(isInitial = false) {
    if (isLoading || (!hasMore && !isInitial)) return;
    isLoading = true;
    document.getElementById('loader').classList.remove('hidden');

    if (isInitial) {
        offset = 0;
        hasMore = true;
        document.getElementById('jobsContainer').innerHTML = '';
    }

    const formData = new FormData();
    formData.append('limit', limit);
    formData.append('offset', offset);

    fetch('fetch_jobs.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(html => {
        const trimmedHTML = html.trim();
        if (isInitial && (trimmedHTML === "" || trimmedHTML.includes("No jobs or education"))) {
            document.getElementById('jobsContainer').innerHTML = trimmedHTML || '<p class="text-center text-gray-500 text-xl font-semibold">No jobs or education updates available.</p>';
            hasMore = false;
        } else if (trimmedHTML === "") {
            hasMore = false;
        } else {
            document.getElementById('jobsContainer').insertAdjacentHTML('beforeend', trimmedHTML);
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
    document.querySelectorAll(".read-btn").forEach((btn) => {
        if(btn.dataset.attached) return;
        btn.dataset.attached = "true";

        const text = btn.previousElementSibling;
        
        // Delay to ensure rendering is complete (especially on mobile)
        setTimeout(() => {
            if (text.scrollHeight <= 115) {
                btn.style.display = "none";
                text.style.maxHeight = "none";
                text.classList.add("no-fade");
            } else {
                btn.onclick = function () {
                    if (text.classList.contains("is-expanded")) {
                        text.style.maxHeight = "112px";
                        text.classList.remove("is-expanded");
                        btn.textContent = "Read More";
                    } else {
                        text.style.maxHeight = text.scrollHeight + "px";
                        text.classList.add("is-expanded");
                        btn.textContent = "Read Less";
                    }
                };
            }
        }, 200);
    });
}

// Infinite Scroll
window.addEventListener('scroll', () => {
    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 500) {
        loadJobs();
    }
});

// Initial Load
document.addEventListener("DOMContentLoaded", function () {
    loadJobs(true);
});
</script>
