<?php

header('Content-Type: application/json');

$name = trim($_GET['name'] ?? '');
$species = trim($_GET['species'] ?? '');

if ($name === '' && $species === '') {
    echo json_encode([
        'fact' => 'Nama hewan tidak ditemukan.',
        'image' => null,
        'wiki' => null
    ]);
    exit;
}

function fetchWikipediaSummary($keyword)
{
    if (empty($keyword)) {
        return null;
    }

    $keyword = trim($keyword);

    $query = urlencode(str_replace(' ', '_', $keyword));

    $url = "https://en.wikipedia.org/api/rest_v1/page/summary/{$query}";

    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: FaunaInTheZoo/1.0 (localhost)\r\n",
            'timeout' => 5
        ]
    ]);

    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);

    if (empty($data)) {
        return null;
    }

    if (
        isset($data['type']) &&
        str_contains($data['type'], 'not_found')
    ) {
        return null;
    }

    if (empty($data['extract']) && empty($data['thumbnail']['source'])) {
        return null;
    }

    return [
        'fact' => $data['extract'] ?? 'Fun fact tidak tersedia.',
        'image' => $data['thumbnail']['source'] ?? null,
        'wiki' => $data['content_urls']['desktop']['page'] ?? null
    ];
}

/*
    Urutan pencarian:
    1. Species / nama ilmiah
    2. Animal name / nama umum
*/
$result = fetchWikipediaSummary($species);

if ($result === null) {
    $result = fetchWikipediaSummary($name);
}

if ($result === null) {
    echo json_encode([
        'fact' => 'Fun fact tidak tersedia.',
        'image' => null,
        'wiki' => null
    ]);
    exit;
}

echo json_encode($result);