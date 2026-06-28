<?php
include 'headers.php';
include 'connection.php';

// Define your Gemini API Key here (or retrieve it from environment variables / settings database)
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'AIzaSyC8-GlqTdHfXhNEoTfhsyi3v4FDu7ZFnkQ');

// Read JSON input
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$prompt = $data['prompt'] ?? '';
$history = $data['history'] ?? []; // Array of {role: 'user'|'model', text: '...'}

if (empty($prompt) && empty($history)) {
    echo json_encode(["status" => "error", "message" => "No message or prompt provided."]);
    exit;
}

// Format conversation history for Gemini API content structure
$contents = [];
foreach ($history as $msg) {
    if (isset($msg['role']) && isset($msg['text'])) {
        $contents[] = [
            "role" => $msg['role'] === 'user' ? 'user' : 'model',
            "parts" => [
                ["text" => $msg['text']]
            ]
        ];
    }
}

// Append the latest user prompt if provided
if (!empty($prompt)) {
    $contents[] = [
        "role" => "user",
        "parts" => [
            ["text" => $prompt]
        ]
    ];
}

// System instructions to define the persona
$systemInstruction = "You are Linko, the official AI Support Assistant for the Linkup app. Your tone is warm, polite, helpful, and friendly. You speak in Hindi (written in Devnagari script or Hinglish) and English. Your goal is to guide users on app features like posting news, registering matrimonial profiles, browsing jobs, viewing shok sandesh, editing profiles, messaging other users, creating smart cards, and basic troubleshooting. Keep answers relatively short, readable, and structured. If someone asks unrelated questions, politely remind them that you are here to assist with Linkup app support.";

// Construct the payload for Gemini API
$payload = [
    "contents" => $contents,
    "systemInstruction" => [
        "parts" => [
            ["text" => $systemInstruction]
        ]
    ],
    "generationConfig" => [
        "temperature" => 0.7,
        "maxOutputTokens" => 800
    ]
];

$apiKey = GEMINI_API_KEY;
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey;

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($response === false) {
    echo json_encode([
        "status" => "error",
        "message" => "cURL request failed: " . $error
    ]);
    exit;
}

$responseDecoded = json_decode($response, true);

if ($httpCode !== 200) {
    // If API Key is placeholder or invalid, return a friendly mock fallback response for testing
    if (strpos($apiKey, 'YOUR_GEMINI_API_KEY_HERE') !== false || $httpCode === 400 || $httpCode === 403) {
        $mockReplies = [
            "नमस्कार! मैं आपका AI असिस्टेंट Linko हूँ। मैं Linkup ऐप में आपकी मदद के लिए तैयार हूँ। आप मुझसे मैट्रिमोनी प्रोफाइल बनाने, न्यूज़ पोस्ट करने या जॉब्स देखने के बारे में पूछ सकते हैं।",
            "Linkup ऐप में प्रोफाइल बनाने के लिए: Profile सेक्शन में जाकर 'Create Matrimony Profile' पर क्लिक करें और अपनी डिटेल्स भरें।",
            "News पोस्ट करने के लिए: News & Updates सेक्शन में जाएँ, और 'Post News' बटन पर क्लिक करें।",
            "यदि आपको कोई और समस्या आ रही है, तो कृपया मुझे बताएं!",
            "मेरा नाम Linko है। मैं Linkup ऐप का AI सपोर्ट असिस्टेंट हूँ। मैं आपकी सहायता के लिए हमेशा उपलब्ध हूँ।"
        ];
        
        $mockReply = $mockReplies[0];
        if (!empty($prompt)) {
            $lowerPrompt = mb_strtolower($prompt, 'UTF-8');
            if (strpos($lowerPrompt, 'news') !== false || strpos($lowerPrompt, 'समाचार') !== false || strpos($lowerPrompt, 'खबर') !== false) {
                $mockReply = $mockReplies[2];
            } else if (strpos($lowerPrompt, 'marriage') !== false || strpos($lowerPrompt, 'matrimony') !== false || strpos($lowerPrompt, 'शादी') !== false || strpos($lowerPrompt, 'profile') !== false || strpos($lowerPrompt, 'प्रोफाइल') !== false) {
                $mockReply = $mockReplies[1];
            } else if (strpos($lowerPrompt, 'name') !== false || strpos($lowerPrompt, 'नाम') !== false || strpos($lowerPrompt, 'कौन') !== false || strpos($lowerPrompt, 'linko') !== false || strpos($lowerPrompt, 'लिंको') !== false) {
                $mockReply = $mockReplies[4];
            } else if (strpos($lowerPrompt, 'hi') !== false || strpos($lowerPrompt, 'hello') !== false || strpos($lowerPrompt, 'hey') !== false || strpos($lowerPrompt, 'नमस्ते') !== false || strpos($lowerPrompt, 'नमस्कार') !== false || strpos($lowerPrompt, 'हलो') !== false) {
                $mockReply = $mockReplies[0];
            } else {
                $mockReply = $mockReplies[3];
            }
        }
        
        echo json_encode([
            "status" => "success",
            "reply" => $mockReply,
            "note" => "Fallback response. Please set a valid Gemini API Key in Api/ai_support.php."
        ]);
        exit;
    }
    
    echo json_encode([
        "status" => "error",
        "message" => "Gemini API returned error code: " . $httpCode,
        "details" => $responseDecoded
    ]);
    exit;
}

if (isset($responseDecoded['candidates'][0]['content']['parts'][0]['text'])) {
    $replyText = $responseDecoded['candidates'][0]['content']['parts'][0]['text'];
    echo json_encode([
        "status" => "success",
        "reply" => $replyText
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid response structure from Gemini API",
        "details" => $responseDecoded
    ]);
}
?>
