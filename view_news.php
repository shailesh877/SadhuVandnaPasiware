<?php
include("header.php");
include("connection.php");

$id = intval($_GET['id'] ?? 0);

if(!$id){
    echo "<div class='text-center mt-10 text-red-500'>Invalid Link</div>";
    exit;
}

$q = mysqli_query($con, "SELECT * FROM tbl_news WHERE id=$id LIMIT 1");
if(mysqli_num_rows($q) == 0){
    echo "<div class='text-center mt-10 text-red-500'>News not found</div>";
    exit;
}

$row = mysqli_fetch_assoc($q);
$images = array_filter(explode(",", $row['image']));
?>

<main class="flex-1 px-2 md:px-10 py-15 md:ml-20 mb-13 md:mb-0 max-w-5xl mx-auto min-h-screen">
    <div class="bg-white rounded-xl shadow-lg border border-orange-200 overflow-hidden mt-8">
        <div class="p-6">
            <h1 class="text-3xl font-extrabold text-gray-900 leading-tight mb-2"><?= $row['title'] ?></h1>
            <p class="text-orange-600 font-semibold mb-4 text-sm">
                <i class="fa fa-calendar mr-1"></i> <?= date("F d, Y", strtotime($row['created_at'])) ?>
            </p>

            <?php if(count($images) > 0){ ?>
                <div class="flex flex-col gap-4 mb-6">
                    <img src="uploads/news/<?= $images[0] ?>" class="w-full rounded-xl object-cover max-h-[500px] shadow-sm">
                    <?php if(count($images) > 1){ ?>
                        <div class="flex gap-2 overflow-x-auto pb-2">
                            <?php foreach($images as $k => $img){ if($k==0) continue; ?>
                                <img src="uploads/news/<?= $img ?>" class="w-24 h-24 rounded-lg object-cover flex-shrink-0 border border-gray-200">
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>

            <div class="text-gray-800 text-lg leading-relaxed">
                <?= nl2br($row['description']) ?>
            </div>
            
            <div class="mt-8 text-center pt-6 border-t border-gray-100">
                 <a href="news.php" class="inline-block bg-orange-100 text-orange-700 px-6 py-2 rounded-full font-bold hover:bg-orange-200 transition">
                    View All News
                </a>
                <a href="index.php" class="inline-block bg-orange-600 text-white px-6 py-2 rounded-full font-bold hover:bg-orange-700 transition ml-2">
                    Download App
                </a>
            </div>
        </div>
    </div>
</main>
</body>
</html>
