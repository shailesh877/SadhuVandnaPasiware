<?php
include("header.php");
include("connection.php");

$data = mysqli_query($con, "SELECT * FROM tbl_jobs_education ORDER BY id DESC");
?>

<main class="flex-1 px-4 md:px-10 py-8 bg-[#f3f2ef] md:ml-20 mb-14 md:mb-0 overflow-hidden">

  <!-- HEADER -->
  <section class="py-5 mb-6 border-b border-gray-200">
    <h3 class="font-extrabold text-2xl text-gray-900 flex items-center gap-2 tracking-wide">
      <i class="fa-solid fa-briefcase text-orange-600"></i> Jobs & Education
    </h3>
  </section>

  <div class="max-w-6xl mx-auto flex flex-col gap-6">

   <?php while($row = mysqli_fetch_assoc($data)) { ?>

<div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden border border-gray-200">

  <!-- IMAGE DISPLAY LIKE SOCIAL POST -->
  <?php if(!empty($row['image'])) { ?>
  <div onclick="openImgModal('uploads/jobs/<?= $row['image'] ?>')" class="cursor-pointer">
    <img src="uploads/jobs/<?= $row['image'] ?>" 
         class="w-full max-h-[320px] object-cover">
  </div>
  <?php } ?>

  <div class="p-5 flex flex-col gap-3">

    <div>
      <span class="inline-block mb-2 px-3 py-1 rounded-full text-[11px] font-bold
        <?= $row['type']=='job' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
        <?= strtoupper($row['type']) ?>
      </span>

      <h2 class="text-xl font-bold text-gray-900">
        <?= htmlspecialchars($row['title']) ?>
      </h2>

      <?php if($row['type']=='job'){ ?>
      <span class="inline-block mt-1 px-2 py-1 rounded bg-yellow-100 text-yellow-800 text-xs font-bold">
        ⭐ Premium Opportunity
      </span>
      <?php } ?>
    </div>

    <p class="text-gray-700 text-[15px] leading-[1.7] whitespace-pre-line job-text">

      <?= htmlspecialchars($row['description']) ?>
    </p>
    <button class="read-btn text-orange-600 text-sm font-semibold hover:underline self-start">
      Read More
    </button>

    <div class="flex items-center justify-between pt-3 border-t">

      <span class="text-xs text-gray-500">
        <i class="fa fa-calendar"></i>
        <?= date("d M Y", strtotime($row['created_at'])) ?>
      </span>

      <?php if($row['type'] == 'job'){ ?>
        <a href="apply_job.php?job_id=<?= $row['id'] ?>"
          class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-5 py-2 rounded-lg text-sm font-semibold">
          Apply Now
        </a>
      <?php } ?>
    </div>

  </div>
</div>

<?php } ?>


    <?php if(mysqli_num_rows($data) == 0) { ?>
      <p class="text-center text-gray-500 text-xl font-semibold">
        No jobs or education updates available.
      </p>
    <?php } ?>

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
  overflow: hidden;
  max-height: 7rem;
  transition: max-height 0.4s ease;
}
</style>

<script>
// ✅ Read More Toggle (LinkedIn style)
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
</script>
