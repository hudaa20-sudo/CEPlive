<?php
// AI-powered chat assistant for the VendorVerse landing page.
// Uses Google's Gemini API, which has a free tier (no card required).
// The browser never sees the API key — this file calls Gemini on the
// server and only returns the reply text to the front end.

require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$b = read_json_body();
$message = trim($b['message'] ?? '');
$history = is_array($b['history'] ?? null) ? $b['history'] : [];

if ($message === '') {
    json_out(['success' => false, 'error' => 'Please type a message.'], 422);
}
if (mb_strlen($message) > 800) {
    json_out(['success' => false, 'error' => 'That message is too long — please keep it under 800 characters.'], 422);
}

if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '' || str_starts_with(GEMINI_API_KEY, 'REPLACE-WITH')) {
    json_out([
        'success' => false,
        'error' => 'The chatbot is not set up yet. Add your GEMINI_API_KEY in api/config.php.',
    ], 500);
}

/*
|--------------------------------------------------------------------------
| BUILD THE MESSAGE LIST
|--------------------------------------------------------------------------
| A system instruction grounds the assistant in what VendorVerse actually
| does so it gives useful, on-topic answers instead of generic chit-chat.
| We keep only the last few turns of history to control response size.
*/

$systemPrompt =
    "You are the VendorVerse Assistant, a friendly helper embedded on the VendorVerse "
    . "website. VendorVerse helps small offline vendors (kirana stores, street food "
    . "stalls, home-based businesses, etc.) go digital. "
    . "Only these parts of the site are actually live right now — do not claim any "
    . "other page, section, or feature exists: "
    . "(1) Sign up and log in (the Log In / Sign Up page); "
    . "(2) Vendor profile setup — business details, contact info, and basic digital "
    . "info like whether they have UPI, a smartphone, and internet access; "
    . "(3) QR code generation — the 'Generate QR' page creates a UPI payment QR (or a "
    . "plain link/text QR) that a customer can scan to pay; "
    . "(4) An Earnings Tracker page that lets a vendor log transactions and see a "
    . "weekly summary. "
    . "There is currently NO separate 'Finance & Safety' hub, scam-awareness guide, "
    . "tutorials library, government-schemes page, certificates, or admin dashboard on "
    . "the live site — these are only planned for later. If someone asks about safety "
    . "or scams, do NOT point them to a page that doesn't exist; instead give 2-3 short, "
    . "genuinely useful tips yourself (e.g. a real UPI payment never asks the RECEIVER "
    . "to enter a PIN or approve anything — only the person paying does that; verify the "
    . "payee name shown before confirming; be wary of anyone claiming they 'accidentally' "
    . "overpaid). You can mention that VendorVerse's QR codes are generated safely by the "
    . "server rather than typed in manually. If asked about a feature that isn't live yet "
    . "(tutorials, schemes, admin tools), say it's planned for a future update rather than "
    . "describing it as available now. "
    . "Answer briefly, in 2-4 short sentences, using simple everyday language a small "
    . "vendor would understand. When it helps, point the person to the right existing "
    . "page or button (Sign Up, Generate QR, Profile, Earnings Tracker). If asked "
    . "something unrelated to VendorVerse, small business, payments, or digital tools, "
    . "politely say you can only help with VendorVerse-related questions.";

$history = array_slice($history, -12); // last 6 user/assistant turns

// Gemini calls the assistant's role "model" instead of "assistant".
$contents = [];
foreach ($history as $turn) {
    $role = ($turn['role'] ?? '') === 'assistant' ? 'model' : 'user';
    $content = trim((string) ($turn['content'] ?? ''));
    if ($content !== '') {
        $contents[] = ['role' => $role, 'parts' => [['text' => mb_substr($content, 0, 800)]]];
    }
}
$contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

/*
|--------------------------------------------------------------------------
| CALL THE GEMINI API
|--------------------------------------------------------------------------
*/

$payload = json_encode([
    'contents' => $contents,
    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
    'generationConfig' => [
        // Generous budget so the model's internal "thinking" plus its
        // actual visible reply both fit without getting cut off.
        'maxOutputTokens' => 2048,
        'temperature' => 0.4,
    ],
]);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/'
    . GEMINI_MODEL . ':generateContent?key=' . GEMINI_API_KEY;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

if ($response === false) {
    json_out(['success' => false, 'error' => 'Could not reach the AI service: ' . $curlErr], 502);
}

$data = json_decode($response, true);
$reply = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($httpCode >= 300 || $reply === null) {
    $apiError = $data['error']['message'] ?? 'The AI service returned an unexpected response.';
    json_out(['success' => false, 'error' => $apiError], 502);
}

$reply = trim($reply);

log_audit(current_vendor_id(), 'chatbot_message', mb_substr($message, 0, 200));

json_out(['success' => true, 'reply' => $reply]);
