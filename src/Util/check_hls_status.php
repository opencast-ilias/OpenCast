<?php
/**
 * simple script to check http code of an url
 */
$url = urldecode(filter_input(INPUT_GET, 'url'));
$ch = curl_init($url);
curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
$response = curl_exec($ch);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo (($httpCode == 200) && (strpos($response, 'EXT-X-STREAM-INF') !== false)) ? 'true' : 'false';

curl_close($ch);