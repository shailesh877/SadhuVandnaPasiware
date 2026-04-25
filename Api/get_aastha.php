<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

function scrapeYoutubeStreams($channelUrl) {
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n" .
                "Accept-Language: en-US,en;q=0.9\r\n" .
                "Cookie: CONSENT=YES+cb.20210328-17-p0.en+FX+478;\r\n" .
                "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                "Connection: close\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $html = @file_get_contents($channelUrl, false, $context);

    $videos = [];
    if ($html) {
        if (preg_match('/(?:var ytInitialData|window\["ytInitialData"\])\s*=\s*(.*?);<\/script>/', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            try {
                $tabs = $jsonData['contents']['twoColumnBrowseResultsRenderer']['tabs'] ?? [];
                foreach ($tabs as $tab) {
                    if (isset($tab['tabRenderer']['content']['richGridRenderer']['contents'])) {
                        $contents = $tab['tabRenderer']['content']['richGridRenderer']['contents'];
                        foreach ($contents as $item) {
                            if (isset($item['richItemRenderer']['content']['videoRenderer'])) {
                                $videoInfo = $item['richItemRenderer']['content']['videoRenderer'];
                                $videoId = $videoInfo['videoId'] ?? '';
                                $title = $videoInfo['title']['runs'][0]['text'] ?? '';
                                $publishedTime = $videoInfo['publishedTimeText']['simpleText'] ?? '';

                                $isLive = false;
                                if (isset($videoInfo['thumbnailOverlays'])) {
                                    foreach ($videoInfo['thumbnailOverlays'] as $overlay) {
                                        if (isset($overlay['thumbnailOverlayTimeStatusRenderer']['style']) && $overlay['thumbnailOverlayTimeStatusRenderer']['style'] === 'LIVE') {
                                            $isLive = true;
                                        }
                                    }
                                }

                                $pubDate = $publishedTime;
                                if ($isLive) {
                                    $pubDate = "🔴 LIVE NOW";
                                }

                                if ($videoId) {
                                    $videos[] = [
                                        'title' => $title,
                                        'link' => "https://www.youtube.com/watch?v=$videoId",
                                        'videoId' => $videoId,
                                        'pubDate' => $pubDate,
                                        'isLive' => $isLive
                                    ];
                                }
                            }
                        }
                        if (count($videos) > 0) break;
                    }
                }
            } catch (Exception $e) {}
        }
    }
    return $videos;
}

$channels = [
    "https://www.youtube.com/@MorariBapu/streams",
    "https://www.youtube.com/@AasthaTV/streams",
    "https://www.youtube.com/@AasthaBhajan/streams"
];

$allVideos = [];
foreach ($channels as $url) {
    $channelVideos = scrapeYoutubeStreams($url);
    $allVideos = array_merge($allVideos, $channelVideos);
}

// Remove duplicates by videoId
$uniqueVideos = [];
$seenIds = [];
foreach ($allVideos as $video) {
    if (!in_array($video['videoId'], $seenIds)) {
        $uniqueVideos[] = $video;
        $seenIds[] = $video['videoId'];
    }
}

// Sort: LIVE videos first
usort($uniqueVideos, function($a, $b) {
    if ($a['isLive'] && !$b['isLive']) return -1;
    if (!$a['isLive'] && $b['isLive']) return 1;
    return 0;
});

// Limit to top 25
$uniqueVideos = array_slice($uniqueVideos, 0, 25);

if (count($uniqueVideos) > 0) {
    echo json_encode(['status' => 'success', 'data' => $uniqueVideos]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'failed']);
}
?>
