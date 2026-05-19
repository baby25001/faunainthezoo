<?php
header('Content-Type: application/json');

$name = trim($_GET['name'] ?? '');
if (empty($name)) {
    echo json_encode(['fact' => 'Nama hewan tidak ditemukan.', 'image' => null]);
    exit;
}

// Ganti spasi dengan underscore untuk URL Wikipedia
$query = urlencode(str_replace(' ', '_', $name));
$url   = "https://en.wikipedia.org/api/rest_v1/page/summary/{$query}";

// Set user-agent (Wikipedia wajibkan ini)
$context = stream_context_create([
    'http' => [
        'header'  => "User-Agent: FaunaInTheZoo/1.0 (localhost)\r\n",
        'timeout' => 5
    ]
]);

$response = @file_get_contents($url, false, $context);

if ($response === false) {
    echo json_encode([
        'fact'  => 'Fun fact tidak tersedia saat ini.',
        'image' => null
    ]);
    exit;
}

$data = json_decode($response, true);

echo json_encode([
    'fact'  => $data['extract'] ?? 'Tidak ada deskripsi.',
    'image' => $data['thumbnail']['source'] ?? null,
    'wiki'  => $data['content_urls']['desktop']['page'] ?? null
]);
?>