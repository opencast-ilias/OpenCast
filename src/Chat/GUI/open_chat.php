<?php

/**
 * this script is the entry point for the chat iframe:
 * it checks the connection to the nodejs server and redirectes accordingly
 */
$port = filter_input(INPUT_GET, 'port');
$token = filter_input(INPUT_GET, 'token');
$protocol = filter_input(INPUT_GET, 'protocol');
$host = filter_input(INPUT_GET, 'host') ?? $_SERVER['SERVER_NAME'];

$chat_base_url = $protocol . '://' . $host . ':' . $port;

$ch = curl_init($chat_base_url . '/srchat/check_connection');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_NOBODY, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_PORT, $port);
$output = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpcode === 200) {
    header('Location: ' . $chat_base_url . '/srchat/open_chat/' . $token);
} else {
    $this_path = dirname($_SERVER['PHP_SELF']);
    echo str_replace(
        '{IMAGES_PATH}',
        $this_path . '/templates/images/',
        file_get_contents(__DIR__ . '/templates/error.html')
    );
}
