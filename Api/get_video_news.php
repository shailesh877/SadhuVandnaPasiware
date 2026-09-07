<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header('Content-Type: application/json');

$channelUrl = "https://www.youtube.com/@DoordarshanGirnar/videos";

$options = [
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n" .
                    "Accept-Language: en-US,en;q=0.9\r\n" . 
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                    "Connection: close\r\n"
    ]
];
$context = stream_context_create($options);
$html = @file_get_contents($channelUrl, false, $context);

if ($html) {
    if (preg_match('/var ytInitialData = (.*?);<\/script>/', $html, $matches)) {
        $jsonData = json_decode($matches[1], true);
        $videos = [];
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
                            if ($videoId) {
                                $videos[] = [
                                    'title' => $title,
                                    'link' => "https://www.youtube.com/watch?v=$videoId",
                                    'videoId' => $videoId,
                                    'pubDate' => $publishedTime
                                ];
                            }
                            if (count($videos) >= 15) break;
                        }
                    }
                    if (count($videos) > 0) break;
                }
            }
            echo json_encode(['status' => 'success', 'data' => $videos]);
            exit;
        } catch (Exception $e) {}
    }
}
echo json_encode(['status' => 'error', 'message' => 'failed']);
?>
