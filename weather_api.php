<?php
/**
 * Weather API Proxy
 *
 * This script fetches weather data from WeatherAPI.com
 * You need to get a free API key from: https://www.weatherapi.com/signup.aspx
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// API Configuration
// IMPORTANT: Replace 'YOUR_API_KEY_HERE' with your actual WeatherAPI.com API key
// Get your free API key from: https://www.weatherapi.com/signup.aspx
$API_KEY = '49a50884034b40a1a98111032250511';
$API_BASE_URL = 'http://api.weatherapi.com/v1';

// Get location from query parameter
$location = isset($_GET['location']) ? $_GET['location'] : '';

if (empty($location)) {
    http_response_code(400);
    echo json_encode(['error' => 'Location parameter is required']);
    exit;
}

if ($API_KEY === 'YOUR_API_KEY_HERE') {
    http_response_code(500);
    echo json_encode([
        'error' => 'API key not configured',
        'message' => 'Please sign up at https://www.weatherapi.com/signup.aspx and add your API key to weather_api.php'
    ]);
    exit;
}

// Build API URL for 3-day forecast
$url = $API_BASE_URL . '/forecast.json';
$params = http_build_query([
    'key' => $API_KEY,
    'q' => $location,
    'days' => 3,
    'aqi' => 'no',
    'alerts' => 'no'
]);

$full_url = $url . '?' . $params;

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $full_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

// Execute request
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Check for cURL errors
if ($response === false) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch weather data',
        'message' => $curl_error
    ]);
    exit;
}

// Check HTTP response code
if ($http_code !== 200) {
    http_response_code($http_code);
    echo $response;
    exit;
}

// Return the weather data
echo $response;
?>
