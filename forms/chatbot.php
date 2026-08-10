<?php
/**
 * Chatbot API Handler using Google Gemini
 *
 * UI is currently disabled on the website. Keep $chatbot_enabled = false
 * until the Gemini key / product issues are resolved.
 */

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$allowed_origins = [
    'https://rgl.com.ph',
    'https://www.rgl.com.ph',
];
$origin = isset($_SERVER['HTTP_ORIGIN']) ? (string) $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
}
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Flip to true when chat is ready again
$chatbot_enabled = false;
if (!$chatbot_enabled) {
    ob_end_clean();
    http_response_code(503);
    echo json_encode(['error' => 'Chat is temporarily unavailable. Please use the inquiry form or call us.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$geminiConfigPath = __DIR__ . '/gemini-config.php';
$geminiConfig = is_file($geminiConfigPath) ? require $geminiConfigPath : [];
$gemini_api_key = isset($geminiConfig['api_key']) ? trim((string) $geminiConfig['api_key']) : '';
$gemini_model = !empty($geminiConfig['model']) ? (string) $geminiConfig['model'] : 'gemini-2.5-flash';

if ($gemini_api_key === '' || $gemini_api_key === 'YOUR_GEMINI_API_KEY') {
    ob_end_clean();
    http_response_code(503);
    echo json_encode(['error' => 'Chat is temporarily unavailable. Please use the inquiry form or call us.']);
    exit;
}

// Simple per-IP rate limit (file-based)
$rateDir = sys_get_temp_dir() . '/rgl_chat_rate';
if (!is_dir($rateDir)) {
    @mkdir($rateDir, 0700, true);
}
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateFile = $rateDir . '/' . hash('sha256', $ip) . '.json';
$now = time();
$window = 60;
$maxRequests = 20;
$hits = [];
if (is_file($rateFile)) {
    $rawHits = @file_get_contents($rateFile);
    $decoded = json_decode((string) $rawHits, true);
    if (is_array($decoded)) {
        $hits = $decoded;
    }
}
$hits = array_values(array_filter($hits, static function ($t) use ($now, $window) {
    return is_int($t) && ($now - $t) < $window;
}));
if (count($hits) >= $maxRequests) {
    ob_end_clean();
    http_response_code(429);
    echo json_encode(['error' => 'Too many messages. Please wait a moment and try again.']);
    exit;
}
$hits[] = $now;
@file_put_contents($rateFile, json_encode($hits), LOCK_EX);

$system_context = "You are RGL Assistant for RGL Business Solutions Inc. (Makati, Philippines). Be warm, professional, and concise.

ABOUT
RGL is an AI technology company that helps organisations work smarter with intelligent solutions, the right talent, and future-ready skills.

CONTACT
• Inquiry form on https://rgl.com.ph/
• Email: info@rgl.com.ph
• Tel: (02) 3224 2000
• Mobile: +63 975 785 6585
• Address: 22nd Floor, The Peak Bldg., 107 L.P. Leviste Street, Salcedo Village, Bel-air, Makati City 1209, Philippines

SERVICES (only these — do not invent others)
1) AI-Powered Decision Intelligence Engine — connects Generative AI, organisational knowledge, data insights, and decision frameworks for clearer, faster decisions.
2) IT Talent Introduction — matches organisations with technology professionals for the right opportunities.
3) IT Training & AI Enablement — practical training and AI adoption programs for teams.

GUIDELINES
• Stick to the services above; if asked about unmanaged IT outsourcing / generic consulting catalogs, redirect to these three focus areas.
• For pricing, say it is customized and invite them to the inquiry form or a call.
• Keep answers short (2–4 sentences) unless they ask for detail.
• Do not invent stats, awards, or guarantees that are not listed here.";

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['message']) || !is_string($input['message'])) {
    ob_end_clean();
    echo json_encode(['error' => 'No message provided']);
    exit;
}

$user_message = trim($input['message']);
if ($user_message === '' || strlen($user_message) > 2000) {
    ob_end_clean();
    echo json_encode(['error' => 'Please send a shorter message.']);
    exit;
}

$conversation_history = isset($input['history']) && is_array($input['history']) ? $input['history'] : [];
$contents = [];
$count = 0;
foreach ($conversation_history as $msg) {
    if ($count >= 10) {
        break;
    }
    if (!is_array($msg) || empty($msg['content']) || !is_string($msg['content'])) {
        continue;
    }
    $role = (isset($msg['role']) && $msg['role'] === 'user') ? 'user' : 'model';
    $text = trim($msg['content']);
    if ($text === '' || strlen($text) > 2000) {
        continue;
    }
    $contents[] = [
        'role' => $role,
        'parts' => [['text' => $text]],
    ];
    $count++;
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $user_message]]];

$api_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($gemini_model) . ':generateContent?key=' . urlencode($gemini_api_key);

$request_body = [
    'contents' => $contents,
    'systemInstruction' => ['parts' => [['text' => $system_context]]],
    'generationConfig' => [
        'temperature' => 0.75,
        'maxOutputTokens' => 1024,
        'topP' => 0.9,
        'topK' => 40,
    ],
];

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    ob_end_clean();
    error_log('RGL chatbot curl error: ' . $curl_error);
    echo json_encode(['error' => 'Chat is temporarily unavailable. Please try again later.']);
    exit;
}

$response_data = json_decode((string) $response, true);
if ($http_code !== 200 || !is_array($response_data)) {
    ob_end_clean();
    $apiMessage = is_array($response_data) && isset($response_data['error']['message'])
        ? (string) $response_data['error']['message']
        : '';
    error_log('RGL chatbot API error HTTP ' . $http_code . ': ' . $apiMessage);
    echo json_encode(['error' => 'Chat is temporarily unavailable. Please try again later.']);
    exit;
}

ob_end_clean();

if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
    echo json_encode(['response' => $response_data['candidates'][0]['content']['parts'][0]['text']]);
    exit;
}

$finish_reason = $response_data['candidates'][0]['finishReason'] ?? '';
if ($finish_reason === 'SAFETY' || isset($response_data['promptFeedback']['blockReason'])) {
    echo json_encode(['response' => 'I cannot process that request. Please ask me about RGL Business Solutions services.']);
    exit;
}

echo json_encode(['error' => 'No response generated. Please try again.']);
