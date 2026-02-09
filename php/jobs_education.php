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

    <!-- ✅ LINKEDIN STYLE CARD -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition flex flex-col overflow-hidden border border-gray-200">

      <div class="p-6 flex flex-col gap-3">

        <!-- TOP ROW -->
        <div class="flex items-start justify-between gap-3">

          <div>
            <!-- TYPE BADGE -->
            <span class="inline-block mb-2 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide
              <?= $row['type']=='job' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' ?>">
              <?= strtoupper($row['type']) ?>
            </span>

            <!-- TITLE -->
            <h2 class="text-xl font-bold text-gray-900">
              <?= htmlspecialchars($row['title']) ?>
            </h2>
          </div>

          <!-- DATE -->
          <span class="text-xs text-gray-400 whitespace-nowrap">
            <i class="fa fa-calendar mr-1"></i>
            <?= date("d M Y", strtotime($row['created_at'])) ?>
          </span>
        </div>

        <!-- DESCRIPTION -->
        <p class="job-text text-gray-700 text-[15px] leading-[1.7]">
          <?= nl2br(htmlspecialchars($row['description'])) ?>
        </p>
        <button class="read-btn text-orange-600 text-sm font-semibold hover:underline self-start">
          Read More
        </button>

        <!-- FOOTER CTA -->
        <div class="flex items-center justify-between pt-4 mt-3 border-t">

          <?php if($row['type'] == 'job'){ ?>
            <span class="text-sm text-gray-500">
              <i class="fa-solid fa-users mr-1"></i> Hiring Open
            </span>

            <a href="apply_job.php?job_id=<?= $row['id'] ?>"
              class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700
                     text-white px-5 py-2.5 rounded-lg text-sm font-semibold shadow-md transition">
              <i class="fa fa-paper-plane"></i> Apply Now
            </a>
          <?php } else { ?>
            <span class="text-sm text-blue-600 font-semibold">
              <i class="fa-solid fa-graduation-cap mr-1"></i> Education Update
            </span>

            <span class="bg-blue-100 text-blue-700 text-xs px-4 py-1 rounded-full font-semibold">
              Info Only
            </span>
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
