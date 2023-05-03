<?php
/**
 * simple script to check http code of an url
 */


function fetch(string $url): array
{
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch,CURLOPT_TIMEOUT,2);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    $effective_url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return [
        'body' => $body,
        'httpCode' => $httpCode,
        'headers' => $headers,
        'effective_url' => $effective_url
    ];
}

function parsePlaylist(string $ts): array
{
    // process the string
    $pieces = explode("\n", $ts); // make an array out of curl return value
    $pieces = array_map('trim', $pieces); // remove unnecessary space
    $chunklists = array_filter($pieces, function (string $piece) { // pluck out ts urls
       return strtolower(substr($piece, -3)) === '.ts';
    });
    return $chunklists;
}

$url = urldecode(filter_input(INPUT_GET, 'url'));
$response = fetch($url);
$url = $response['effective_url'] ?? $url;
$base_url = substr($url, 0, strrpos($url, '/') + 1);
// check playlist
if (($response['httpCode'] !== 200) || (strpos($response['body'], 'EXT-X-MEDIA-SEQUENCE') === false)) {
    echo 'false';
    exit;
}

// check chunklists in m3u8 playlist (only one has to be accessible)
foreach (parsePlaylist($response['body']) as $chunklist_url) {
    $url = (strpos($chunklist_url, 'http') === 0) ? $chunklist_url : ($base_url . $chunklist_url);
    $response = fetch($url);
    if ($response['httpCode'] === 200) {
        echo 'true';
        exit;
    }
}

echo 'false';
exit;
