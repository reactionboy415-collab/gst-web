<?php
// File: api/verify-gst.php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$gstNumber = strtoupper(trim($input['gst_number'] ?? ''));

if (empty($gstNumber)) {
    echo json_encode(['success' => false, 'message' => 'GST number is required']);
    exit;
}

if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstNumber)) {
    echo json_encode(['success' => false, 'message' => 'Invalid GST format']);
    exit;
}

$apiUrl = "https://gst-info.vercel.app/CHIRAGX9/{$gstNumber}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'GST-Sentinel-CA-Platform/2.0');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    echo json_encode([
        'success' => false,
        'message' => 'API connection error: ' . curl_error($ch)
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode([
        'success' => false,
        'message' => 'API returned status code: ' . $httpCode
    ]);
    exit;
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid API response format'
    ]);
    exit;
}

if (isset($data['success']) && $data['success'] === true && isset($data['data'])) {
    echo json_encode([
        'success' => true,
        'data' => $data['data']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $data['message'] ?? 'GST not found or invalid'
    ]);
}
