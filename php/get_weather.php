<?php
/**Fetches current weather and air quality data from OpenWeather API*/

ini_set('display_errors', 0);
error_reporting(0);

function loadEnvFile($path) {
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $name = trim($parts[0]);
        $value = trim($parts[1]);

        if ((strlen($value) >= 2 && $value[0] === '"' && substr($value, -1) === '"') ||
            (strlen($value) >= 2 && $value[0] === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }

        if (!getenv($name)) {
            putenv($name . '=' . $value);
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

loadEnvFile(__DIR__ . '/../.env');

$openWeatherApiKey = getenv('OPENWEATHER_API_KEY') ?: ($_ENV['OPENWEATHER_API_KEY'] ?? '');
$openWeatherBaseUrl = getenv('OPENWEATHER_BASE_URL') ?: 'https://api.openweathermap.org/data/2.5/';
$openWeatherAqUrl = getenv('OPENWEATHER_AQ_URL') ?: 'https://api.openweathermap.org/data/2.5/air_pollution';

define('OPENWEATHER_API_KEY', $openWeatherApiKey);
define('OPENWEATHER_BASE_URL', $openWeatherBaseUrl);
define('OPENWEATHER_AQ_URL', $openWeatherAqUrl);

/**
 * Makes a cURL request to a given URL and returns decoded JSON
 * @param string $url
 * @return array|null
 */
function fetchFromAPI($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return null;
    }

    return json_decode($response, true);
}

/**
 * Fetches current weather for given coordinates
 * @param float $lat
 * @param float $lon
 * @return array|null
 */
function getCurrentWeather($lat, $lon) {
    $url = OPENWEATHER_BASE_URL . 'weather?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY . '&units=metric';
    return fetchFromAPI($url);
}

/**
 * Fetches current air quality for given coordinates
 * @param float $lat
 * @param float $lon
 * @return array|null
 */
function getAirQuality($lat, $lon) {
    $url = OPENWEATHER_AQ_URL . '?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY;
    return fetchFromAPI($url);
}

/**
 * Fetches 5 day weather forecast for given coordinates
 * @param float $lat
 * @param float $lon
 * @return array|null
 */
function getWeatherForecast($lat, $lon) {
    $url = OPENWEATHER_BASE_URL . 'forecast?lat=' . $lat . '&lon=' . $lon . '&appid=' . OPENWEATHER_API_KEY . '&units=metric';
    return fetchFromAPI($url);
}

/**
 * Converts OpenWeather AQI integer to a human readable label
 * @param int $aqi
 * @return string
 */
function getAQILabel($aqi) {
    $labels = [
        1 => 'Good',
        2 => 'Fair',
        3 => 'Moderate',
        4 => 'Poor',
        5 => 'Very Poor'
    ];
    return $labels[$aqi] ?? 'Unknown';
}

/**
 * Returns CSS alert class based on AQI level
 * @param int $aqi
 * @return string
 */
function getAQIAlertClass($aqi) {
    if ($aqi <= 2) return 'alert-success';
    if ($aqi == 3) return 'alert-warning';
    return 'alert-danger';
}

/**
 * Checks weather conditions against project resources and returns recommendations
 * @param array $weather
 * @param array $resources
 * @return array
 */
function getWeatherRecommendations($weather, $resources) {
    $recommendations = [];

    $resourceTypes = array_map(fn($r) => strtolower($r['resource_type']), $resources);

    // Wind speed in m/s from API, convert to mph
    $windSpeedMph = $weather['wind']['speed'] * 2.237;

    $weatherId = $weather['weather'][0]['id'];

    // Rule 1: Wind > 20mph and project has a crane
    if ($windSpeedMph > 20 && in_array('crane', $resourceTypes)) {
        $recommendations[] = [
            'type' => 'alert-danger',
            'message' => '⚠️ Wind speed is ' . round($windSpeedMph, 1) . 'mph. Works requiring the crane should not be carried out.'
        ];
    }

    // Rule 2: Heavy rain and project has diggers or dumper trucks
    // OpenWeather IDs: 502=heavy intensity, 503=very heavy, 504=extreme, 522=heavy shower, 531=ragged shower
    $heavyRainIds = [502, 503, 504, 522, 531];
    $hasEarthMoving = in_array('digger', $resourceTypes) || in_array('dumper truck', $resourceTypes);

    if (in_array($weatherId, $heavyRainIds) && $hasEarthMoving) {
        $recommendations[] = [
            'type' => 'alert-danger',
            'message' => '⚠️ Heavy rainfall detected. Works involving diggers and dumper trucks may be delayed.'
        ];
    }

    return $recommendations;
}

/**
 * Checks air quality against project resources and returns recommendations
 * @param int $aqi
 * @param array $resources
 * @return array
 */
function getAirQualityRecommendations($aqi, $resources) {
    $resourceTypes = array_map(fn($r) => strtolower($r['resource_type']), $resources);
    $hasEarthMoving = in_array('digger', $resourceTypes) ||
                      in_array('dumper truck', $resourceTypes) ||
                      in_array('loader', $resourceTypes);

    $recommendations = [];

    if (!$hasEarthMoving) {
        return $recommendations;
    }

    $label = getAQILabel($aqi);

    if ($aqi <= 2) {
        $recommendations[] = [
            'type' => 'alert-success',
            'message' => '✅ Air quality is ' . $label . '. Earth moving equipment (diggers, dumper trucks, loaders) can operate.'
        ];
    } else {
        $recommendations[] = [
            'type' => 'alert-danger',
            'message' => '⚠️ Air quality is ' . $label . '. Works involving earth moving equipment should not be carried out.'
        ];
    }

    return $recommendations;
}

// Handle direct AJAX requests for forecast by date
if (isset($_GET['action']) && $_GET['action'] === 'forecast') {
    header('Content-Type: application/json');

    $lat = isset($_GET['lat']) ? floatval($_GET['lat']) : 0;
    $lon = isset($_GET['lon']) ? floatval($_GET['lon']) : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';

    if (!$lat || !$lon || !$date) {
        echo json_encode(['error' => 'Invalid parameters.']);
        exit;
    }

    // Validate date format
    $parsedDate = DateTime::createFromFormat('Y-m-d', $date);
    if (!$parsedDate) {
        echo json_encode(['error' => 'Invalid date format.']);
        exit;
    }

    $forecast = getWeatherForecast($lat, $lon);

    if (!$forecast) {
        echo json_encode(['error' => 'Could not retrieve forecast data.']);
        exit;
    }

    // Filter forecast entries matching the selected date
    $results = [];
    foreach ($forecast['list'] as $entry) {
        $entryDate = date('Y-m-d', $entry['dt']);
        if ($entryDate === $date) {
            $results[] = [
                'time'        => date('H:i', $entry['dt']),
                'temp'        => round($entry['main']['temp'], 1),
                'description' => ucfirst($entry['weather'][0]['description']),
                'wind'        => round($entry['wind']['speed'] * 2.237, 1),
                'humidity'    => $entry['main']['humidity']
            ];
        }
    }

    if (empty($results)) {
        echo json_encode(['error' => 'No forecast data available for this date.']);
        exit;
    }

    echo json_encode($results);
    exit;
}
?>