<?php

$token = 'YOUR_HF_TOKEN'; // We'll grep from env
if (file_exists('.env')) {
    $env = file_get_contents('.env');
    preg_match('/HUGGINGFACE_API_KEY=(.*)/', $env, $m);
    if (! empty($m[1])) {
        $token = trim(trim($m[1], "\r\n"));
    }
}

$urls = [
    'https://router.huggingface.co/hf-inference/models/meta-llama/Llama-3.3-70B-Instruct/v1/chat/completions',
];

foreach ($urls as $url) {
    echo "Testing $url\n";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'meta-llama/Llama-3.3-70B-Instruct',
        'messages' => [['role' => 'user', 'content' => 'hello']],
        'max_tokens' => 10,
    ]));
    $res = curl_exec($ch);
    $info = curl_getinfo($ch);
    echo 'Status code: '.$info['http_code']."\nResponse: $res\n\n";
    curl_close($ch);
}
