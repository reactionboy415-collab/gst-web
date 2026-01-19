<?php
// File: api/verify-gst.php

header('Content-Type: application/json');

// Allow only POST for safety
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$gstNumber = strtoupper(trim($input['gst_number'] ?? ''));

if (empty($gstNumber)) {
    echo json_encode(['success' => false, 'message' => 'GST number is required']);
    exit;
}

// Basic GST format validation
if (!preg_match('/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/', $gstNumber)) {
    echo json_encode(['success' => false, 'message' => 'Invalid GST format']);
    exit;
}

// Call GST API (key stays hidden on server)
$apiUrl = "https://gst-info.vercel.app/CHIRAGX9/{$gstNumber}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'GST-Sentinel-Vercel/1.0');

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

// Return full API data (frontend will show everything)
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
