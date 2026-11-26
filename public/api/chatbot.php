<?php
/**
 * AI assistant endpoint (Google Gemini).
 *
 * Fixes over the original:
 *  - API key comes from the GEMINI_API_KEY environment variable (it was hard-coded).
 *  - No `Access-Control-Allow-Origin: *`: only same-origin pages can call it.
 *  - POST + CSRF token required (enforced in bootstrap.php).
 *  - Per-session rate limit and message length cap, so the key can't be drained.
 *  - Raw upstream responses are logged server-side, never returned to the browser.
 *  - The key is sent in a header instead of the URL (URLs end up in logs).
 */
require_once __DIR__ . '/../../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(string $text, int $status = 200): never
{
    http_response_code($status);
    exit(json_encode(['reply' => $text], JSON_UNESCAPED_UNICODE));
}

if (!is_post()) {
    reply('Method not allowed.', 405);
}

$data    = json_decode((string) file_get_contents('php://input'), true);
$message = is_array($data) && is_string($data['message'] ?? null) ? trim($data['message']) : '';

if ($message === '') {
    reply('Please say something!', 422);
}
if (mb_strlen($message) > CHAT_MAX_LENGTH) {
    reply('Please keep your message under ' . CHAT_MAX_LENGTH . ' characters.', 422);
}

// --- Rate limit (sliding window, per session) ---
$now  = time();
$hits = array_filter($_SESSION['chat_hits'] ?? [], fn ($t) => is_int($t) && $t > $now - CHAT_WINDOW_SECONDS);
if (count($hits) >= CHAT_MAX_MESSAGES) {
    reply("You're sending messages quickly. Please wait a few minutes and try again.", 429);
}
$hits[] = $now;
$_SESSION['chat_hits'] = array_values($hits);
session_write_close();   // don't hold the session lock while waiting for the AI

if (GEMINI_API_KEY === '') {
    reply('The assistant is not configured yet. Please ask the site administrator to set GEMINI_API_KEY.', 503);
}

$me       = current_user();
$roleHint = $me ? 'The user is logged in as: ' . (ROLE_LABELS[$me['role']] ?? $me['role']) . '.' : 'The user is not logged in.';

$systemPrompt = <<<TXT
You are Aatman's virtual assistant: warm, kind and concise. Aatman is a social-impact platform that connects
NGOs, donors, volunteers and people in need. Help users understand how to sign up, post or find help requests,
create or join campaigns, update their profile, and use the language selector at the top of the page.
Reply in the same language the user writes in. Never ask for passwords, OTPs, bank or card details.
If someone describes an emergency, tell them to contact local emergency services (112 in India) immediately.
You cannot see or change account data; direct users to the relevant dashboard tab instead.
$roleHint
TXT;

$payload = json_encode([
    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
    'contents'          => [['role' => 'user', 'parts' => [['text' => $message]]]],
    'generationConfig'  => ['maxOutputTokens' => 512, 'temperature' => 0.6],
], JSON_UNESCAPED_UNICODE);

$url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode(GEMINI_MODEL) . ':generateContent';
$ch  = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'x-goog-api-key: ' . GEMINI_API_KEY],
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 20,
]);
$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) {
    error_log('[Aatman chatbot] cURL error: ' . $curlErr);
    reply('The assistant is unavailable right now. Please try again shortly.', 502);
}

$result = json_decode((string) $response, true);
$text   = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

if ($httpCode !== 200 || !is_string($text)) {
    error_log('[Aatman chatbot] Gemini HTTP ' . $httpCode . ': ' . substr((string) $response, 0, 1000));
    reply("Sorry, I couldn't answer that right now. Please try again.", 502);
}

reply(trim($text));
