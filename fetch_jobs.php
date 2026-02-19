<?php
include("connection.php");

$limit = isset($_POST['limit']) ? intval($_POST['limit']) : 5;
$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

$data = mysqli_query($con, "SELECT * FROM tbl_jobs_education ORDER BY id DESC LIMIT $limit OFFSET $offset");

if(!$data || mysqli_num_rows($data) == 0){
    if($offset == 0) echo '<p class="text-center text-gray-500 text-xl font-semibold">No jobs or education updates available.</p>';
    exit;
}

while($row = mysqli_fetch_assoc($data)) { ?>

<div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition overflow-hidden border border-gray-200 mb-6">

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
