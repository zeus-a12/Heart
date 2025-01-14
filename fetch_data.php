<?php
header('Content-Type: application/json');

$esp8266_ip = "http://192.168.0.178"; // استبدل بالـ IP الخاص بـ ESP8266
$url = $esp8266_ip . "/data";

$json = @file_get_contents($url);
if ($json === FALSE) {
    echo json_encode(["error" => "تعذر الاتصال بجهاز ESP8266"]);
    exit;
}

$data = json_decode($json, true);
$heartRate = $data['heartRate'];
$bloodOxygen = $data['bloodOxygen'];

$warning = false;
if ($heartRate > 100 || $heartRate < 60 || $bloodOxygen < 90) {
    $warning = true;
}

$response = [
    "heartRate" => $heartRate,
    "bloodOxygen" => $bloodOxygen,
    "warning" => $warning
];

echo json_encode($response);
