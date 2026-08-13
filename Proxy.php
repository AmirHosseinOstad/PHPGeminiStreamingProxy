<?php
while (ob_get_level() > 0) {
    ob_end_flush();
}
header('Content-Type: text/plain; charset=utf-8');
header('X-Accel-Buffering: no');
header('Cache-Control: no-cache');

// Load API keys securely from environment variables or a non-public config file
$apiKeys = explode(',', getenv('GEMINI_API_KEYS') ?: 'your_Api_Key_1,your_Api_Key_2');

function getNextApiKey(array $keys): string {
    $count = count($keys);
    if ($count === 0) return '';
    if ($count === 1) return trim($keys[0]);

    $indexFile = __DIR__ . '/api_key_index.txt';
    $fp = fopen($indexFile, 'c+');
    if (!$fp) {
        return trim($keys[array_rand($keys)]);
    }

    if (flock($fp, LOCK_EX)) {
        $content   = trim((string) stream_get_contents($fp));
        $lastIndex = ($content !== '' && is_numeric($content)) ? (int)$content : -1;
        $nextIndex = ($lastIndex + 1) % $count;

        rewind($fp);
        ftruncate($fp, 0);
        fwrite($fp, (string)$nextIndex);
        fflush($fp);
        flock($fp, LOCK_UN);
    } else {
        $nextIndex = array_rand($keys);
    }

    fclose($fp);
    return trim($keys[$nextIndex]);
}

$apiKey = getNextApiKey($apiKeys);

$defaultModel  = 'gemini-3.5-flash-lite';    // chat
$advancedModel = 'gemma-4-26b-a4b-it';       // think/search
$titleModel    = 'gemini-3.1-flash-lite';    // title

$systemInstruction = '';

const ERR_MARKER = "|| error: ";

$raw   = file_get_contents('php://input');
$input = json_decode($raw, true);

if (!is_array($input)) {
    echo ERR_MARKER . 'Invalid JSON input.';
    exit;
}

$think    = !empty($input['think']);
$search   = !empty($input['search']);
$hasImage = !empty($input['hasImage']);
$forTitle = !empty($input['forTitle']);

// Select model
if ($forTitle) {
    $model = $titleModel;
} else {
    $model = ($think || $search || $hasImage) ? $advancedModel : $defaultModel;
}

$contents = [];

if (isset($input['messages']) && is_array($input['messages']) && count($input['messages']) > 0) {
    foreach ($input['messages'] as $m) {
        $role = (isset($m['role']) && $m['role'] === 'model') ? 'model' : 'user';
        $text = isset($m['text']) ? trim($m['text']) : '';
        if ($text === '') continue;

        $contents[] = [
            'role'  => $role,
            'parts' => [['text' => $text]]
        ];
    }
} elseif (isset($input['text'])) {
    $text = trim($input['text']);
    if ($text !== '') {
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $text]]
        ];
    }
}

if (empty($contents)) {
    echo ERR_MARKER . 'No message found.';
    exit;
}

if ($hasImage && !empty($input['image']['data'])) {
    $imgMime = $input['image']['mimeType'] ?? 'image/jpeg';
    $imgData = $input['image']['data'];

    $lastIndex = count($contents) - 1;
    $contents[$lastIndex]['parts'][] = [
        'inline_data' => [
            'mime_type' => $imgMime,
            'data'      => $imgData
        ]
    ];
}

$url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse";

$body = [
    'system_instruction' => [
        'parts' => [['text' => $systemInstruction]]
    ],
    'contents' => $contents,
];

if ($search) {
    $body['tools'] = [
        ['googleSearch' => new stdClass()]
    ];
}

if ($model === $advancedModel) {
    $body['generationConfig'] = [
        'thinkingConfig' => [
            'thinkingLevel' => $think ? 'HIGH' : 'MINIMAL'
        ]
    ];
}

$sseBuffer = '';
$rawBuffer = '';
$gotAnyText = false;
$httpCodeHolder = null;
$promptFeedbackHolder = null;
$finishReasonHolder = null;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-goog-api-key: ' . $apiKey,
        'Accept: text/event-stream',
    ],
    CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
    CURLOPT_TIMEOUT        => 120,
    CURLOPT_WRITEFUNCTION  => function ($curlHandle, $chunk) use (&$sseBuffer, &$gotAnyText, &$rawBuffer, &$promptFeedbackHolder, &$finishReasonHolder) {
        $rawBuffer .= $chunk;
        $sseBuffer .= $chunk;

        while (($newlinePos = strpos($sseBuffer, "\n")) !== false) {
            $line = trim(substr($sseBuffer, 0, $newlinePos));
            $sseBuffer = substr($sseBuffer, $newlinePos + 1);

            if ($line === '' || strpos($line, 'data:') !== 0) continue;

            $jsonPart = trim(substr($line, 5));
            if ($jsonPart === '' || $jsonPart === '[DONE]') continue;

            $data = json_decode($jsonPart, true);
            if (!is_array($data)) continue;

            if (isset($data['error'])) {
                $msg = $data['error']['message'] ?? 'Gemini Error';
                echo ERR_MARKER . $msg;
                @ob_flush(); @flush();
                continue;
            }

            if (isset($data['promptFeedback']) && !empty($data['promptFeedback']['blockReason'])) {
                $promptFeedbackHolder = $data['promptFeedback'];
            }

            if (isset($data['candidates'][0]['finishReason']) && $data['candidates'][0]['finishReason'] !== 'STOP') {
                $finishReasonHolder = $data['candidates'][0]['finishReason'];
            }

            $parts = $data['candidates'][0]['content']['parts'] ?? [];

            foreach ($parts as $part) {
                $isThought = $part['thought'] ?? false;
                $textPiece = $part['text'] ?? null;

                if (!$isThought && $textPiece !== null && $textPiece !== '') {
                    $gotAnyText = true;
                    echo $textPiece;
                    @ob_flush(); @flush();
                }
            }
        }

        return strlen($chunk);
    },
]);

curl_exec($ch);

$curlErrNo = curl_errno($ch);
$curlErr   = curl_error($ch);
$httpCodeHolder = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($curlErrNo) {
    echo ERR_MARKER . 'Error:' . $curlErr;
    exit;
}

if (!$gotAnyText) {
    $detail = '';

    if ($promptFeedbackHolder && !empty($promptFeedbackHolder['blockReason'])) {
        $detail = ' - Input blocked by Google filter (' . $promptFeedbackHolder['blockReason'] . ').';
    } elseif (!empty($finishReasonHolder)) {
        $detail = ' - Model response was incomplete or blocked (Reason: ' . $finishReasonHolder . ')';
    } elseif ($httpCodeHolder !== 200) {
        $maybeJson = json_decode(trim($rawBuffer), true);
        if (is_array($maybeJson) && isset($maybeJson['error']['message'])) {
            $detail = ' - ' . $maybeJson['error']['message'];
        }
    } else {
        $detail = ' - No response received from the model';
    }

    echo ERR_MARKER . 'Error receiving response from model (HTTP ' . $httpCodeHolder . ')' . $detail;
}