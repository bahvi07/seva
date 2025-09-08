<?php
/**
 * Simple Geocoding Proxy - Fixed Version
 * Place this file in your root directory: /seva/geocode-proxy.php
 */

// Prevent any HTML output before JSON
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header first
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Get and validate parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;

// Simple validation
if (!$lat || !$lon || $lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
    echo json_encode([
        'error' => 'Invalid coordinates',
        'display_name' => 'Location: ' . $lat . ', ' . $lon
    ]);
    exit;
}

try {
    // Build URL for OpenStreetMap Nominatim
    $url = "https://nominatim.openstreetmap.org/reverse";
    $params = [
        'format' => 'json',
        'lat' => $lat,
        'lon' => $lon,
        'addressdetails' => 1,
        'limit' => 1
    ];
    
    $url .= '?' . http_build_query($params);
    
    // Create context with proper headers
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: SevaConnect/1.0 (contact@seva.com)',
                'Accept: application/json'
            ],
            'timeout' => 5
        ]
    ]);
    
    // Get the response
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch location data');
    }
    
    $data = json_decode($response, true);
    
    if ($data && isset($data['display_name'])) {
        // Return successful response
        echo json_encode([
            'display_name' => $data['display_name'],
            'address' => $data['address'] ?? []
        ]);
    } else {
        throw new Exception('No location data found');
    }
    
} catch (Exception $e) {
    // Create fallback response with regional info
    $region = 'Unknown Location';
    
    // Simple region detection for India
    if ($lat >= 29.5 && $lat <= 32.5 && $lon >= 73.0 && $lon <= 76.5) {
        $region = 'Punjab, India';
    } elseif ($lat >= 28.0 && $lat <= 29.0 && $lon >= 76.0 && $lon <= 78.0) {
        $region = 'Delhi NCR, India';
    } elseif ($lat >= 18.0 && $lat <= 20.0 && $lon >= 72.0 && $lon <= 73.0) {
        $region = 'Mumbai, Maharashtra, India';
    } elseif ($lat >= 12.0 && $lat <= 13.0 && $lon >= 77.0 && $lon <= 78.0) {
        $region = 'Bangalore, Karnataka, India';
    } elseif ($lat >= 8.0 && $lat <= 37.0 && $lon >= 68.0 && $lon <= 97.0) {
        $region = 'India';
    }
    
    echo json_encode([
        'display_name' => $region . ' (' . number_format($lat, 4) . ', ' . number_format($lon, 4) . ')',
        'address' => [
            'country' => 'India'
        ],
        'fallback' => true
    ]);
}
?>