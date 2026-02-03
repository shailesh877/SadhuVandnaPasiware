<?php  
include("connection.php");
include("header.php");

$data = mysqli_query($con,"SELECT * FROM tbl_gallery ORDER BY id DESC");

// store for modal slider
$gallery = [];
while($g = mysqli_fetch_assoc($data)){
  $gallery[] = $g;
}
?>

<main class="flex-1 px-4 md:px-10 py-6 bg-[#f3f2ef] md:ml-20 mb-14 md:mb-0">

  <!-- HEADER -->
  <section class="py-5 mb-4 border-b border-gray-200">
    <h3 class="font-extrabold text-2xl text-gray-900 flex items-center gap-2 tracking-wide">
      <i class="fa-solid fa-image text-orange-600"></i> Gallery
    </h3>
    <p class="text-sm text-gray-500 mt-1">Premium Photo Gallery</p>
  </section>


  <!-- GRID -->
  <div class="max-w-8xl mx-auto 
              grid gap-3 
              grid-cols-2
              sm:grid-cols-3
              md:grid-cols-4
              lg:grid-cols-5">

  <?php foreach($gallery as $index=>$row){ ?>

    <div onclick="openGalleryModal('uploads/gallery/<?= $row['image'] ?>', <?= $index ?>)"
      class="relative cursor-pointer group rounded-xl overflow-hidden shadow-sm
             bg-white border border-orange-200">

      <!-- PHOTO -->
      <img src="uploads/gallery/<?= $row['image'] ?>" 
           class="w-full h-[110px] 
                  sm:h-[140px]
                  md:h-[160px]
                  object-cover
                  group-hover:scale-110 transition duration-500"/>

      <!-- Title -->
      <div class="absolute bottom-0 left-0 right-0
            bg-black/50 text-white text-[10px] sm:text-xs p-1 text-center
            group-hover:bg-black/70 transition">
        <?= htmlspecialchars($row['title']) ?>
      </div>

    </div>

  <?php } ?>

  </div>


  <?php if(empty($gallery)){ ?>
    <p class="text-center text-gray-500 text-lg font-semibold py-10">
      No images available.
    </p>
  <?php } ?>

</main>



<!-- FULLSCREEN MODAL -->
<div id="galleryModal"
     class="fixed inset-0 bg-black/80 hidden items-center justify-center z-[999] p-4">

  <!-- Close -->
  <button onclick="closeGallery()" 
          class="absolute top-4 right-4 text-white text-3xl font-bold">
    &times;
  </button>

  <!-- Prev -->
  <button onclick="prevImg()" 
          class="absolute left-3 text-white text-5xl opacity-60 hover:opacity-100">
    &#10094;
  </button>

  <!-- Image -->
  <img id="modalImg"
       class="max-w-full max-h-full rounded-xl shadow-lg object-contain" />

  <!-- Next -->
  <button onclick="nextImg()"
          class="absolute right-3 text-white text-5xl opacity-60 hover:opacity-100">
    &#10095;
  </button>

</div>


<script>
let gallery = <?= json_encode($gallery) ?>;
let currentIndex = 0;

// open modal
function openGalleryModal(src,index){
  currentIndex = index;
  document.getElementById("modalImg").src = src;
  document.getElementById("galleryModal").classList.remove("hidden");
  document.getElementById("galleryModal").classList.add("flex");
}

// close
function closeGallery(){
  document.getElementById("galleryModal").classList.add("hidden");
  document.getElementById("galleryModal").classList.remove("flex");
}

// show
function showImg(){
  document.getElementById("modalImg").src = "uploads/gallery/"+gallery[currentIndex].image;
}

// next
function nextImg(){
  currentIndex = (currentIndex + 1) % gallery.length;
  showImg();
}

// prev
function prevImg(){
  currentIndex = (currentIndex - 1 + gallery.length) % gallery.length;
  showImg();
}

// wheel scroll
modalImg.addEventListener("wheel",(e)=>{
  if(e.deltaY > 0) nextImg();
  else prevImg();
});

// swipe
let touchStart = 0;
modalImg.addEventListener("touchstart",(e)=> touchStart=e.touches[0].clientX);
modalImg.addEventListener("touchend",(e)=>{
  let diff = e.changedTouches[0].clientX - touchStart;
  if(diff > 50) prevImg();
  if(diff < -50) nextImg();
});
</script>
