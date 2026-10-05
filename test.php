<?php

$url = 'http://localhost:8000/api/login';

// Testing the master admin login we just seeded
$data = [
    'email'    => 'zeffali7@gmai.com',
    'password' => 'secret123'
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

echo "Testing login endpoint with Master Admin...\n";
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "\n";
echo "Response: " . $response . "\n\n";

// Testing login with Phone Number using Hotel Admin we seeded
$dataPhone = [
    'phone'    => '8800828701',
    'password' => 'secret123'
];

$ch2 = curl_init($url);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($dataPhone));
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

echo "Testing login endpoint with Hotel Admin (Phone)...\n";
$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: " . $httpCode2 . "\n";
echo "Response: " . $response2 . "\n";
