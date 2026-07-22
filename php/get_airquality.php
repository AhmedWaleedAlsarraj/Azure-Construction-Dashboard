<?php
/**Air quality API endpoint
 * Returns current air quality data as JSON for a given location*/
require_once 'get_weather.php';

header('Content-Type: application/json');

// Validate parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : 0;

if (!$lat || !$lon) {
    echo json_encode(['error' => 'Invalid coordinates provided.']);
    exit;
}

$airQuality = getAirQuality($lat, $lon);

if (!$airQuality) {
    echo json_encode(['error' => 'Could not retrieve air quality data.']);
    exit;
}

$aqi = $airQuality['list'][0]['main']['aqi'];
$components = $airQuality['list'][0]['components'];
$label = getAQILabel($aqi);

// Build recommendation message
if ($aqi <= 2) {
    $recommendation = '✅ Air quality is ' . $label . '. Earth moving equipment can operate.';
} else {
    $recommendation = '⚠️ Air quality is ' . $label . '. Works involving earth moving equipment should not be carried out.';
}

echo json_encode([
    'aqi'            => $aqi,
    'label'          => $label,
    'recommendation' => $recommendation,
    'pm2_5'          => round($components['pm2_5'], 1),
    'pm10'           => round($components['pm10'], 1),
    'no2'            => round($components['no2'], 1),
    'co'             => round($components['co'], 1),
    'o3'             => round($components['o3'], 1)
]);
exit;
?>