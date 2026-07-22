<?php
/**Project detail page
 * Displays project info, resources, map, weather and air quality data*/

require_once 'php/get_project.php';
require_once 'php/get_weather.php';

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

if ($project_id === 0) {
    header('Location: index.php');
    exit;
}

// Fetch project from database
$project = getProjectById($project_id);

if (!$project) {
    header('Location: index.php');
    exit;
}

// Fetch weather and air quality data
$weather = getCurrentWeather($project['latitude'], $project['longitude']);
$airQuality = getAirQuality($project['latitude'], $project['longitude']);

// Get recommendations
$weatherRecs = ($weather) ? getWeatherRecommendations($weather, $project['resources']) : [];
$aqiValue = $airQuality['list'][0]['main']['aqi'] ?? null;
$aqRecs = ($aqiValue) ? getAirQualityRecommendations($aqiValue, $project['resources']) : [];

$pageTitle = htmlspecialchars($project['project_name']);
include 'includes/header.php';
?>

<!-- Back link -->
<a href="index.php" style="display:inline-block;margin-bottom:20px;color:#e94560;text-decoration:none;">← Back to Projects</a>

<!-- Project Details -->
<div class="project-detail">
    <h2><?php echo htmlspecialchars($project['project_name']); ?></h2>
    <p class="project-meta">📍 <?php echo htmlspecialchars($project['location']); ?></p>
    <p class="project-meta">👤 Manager: <?php echo htmlspecialchars($project['manager']); ?></p>
    <p style="margin-top:12px;"><?php echo htmlspecialchars($project['description']); ?></p>

    <!-- Resources -->
    <h3 style="margin-top:24px;margin-bottom:12px;">Allocated Resources</h3>
    <?php if (!empty($project['resources'])): ?>
        <ul class="resources-list">
            <?php foreach ($project['resources'] as $resource): ?>
                <li>
                    <strong><?php echo htmlspecialchars($resource['resource_type']); ?></strong>
                    <?php echo htmlspecialchars($resource['conditions_of_use']); ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No resources allocated to this project.</p>
    <?php endif; ?>
</div>

<!-- Map -->
<div class="panel">
    <h3>Project Location</h3>
    <div id="map"></div>
</div>

<!-- Weather -->
<div class="panel">
    <h3>Current Weather</h3>
    <?php if ($weather): ?>
        <div class="weather-grid">
            <div class="weather-stat">
                <span class="label">Condition</span>
                <span class="value"><?php echo htmlspecialchars(ucfirst($weather['weather'][0]['description'])); ?></span>
            </div>
            <div class="weather-stat">
                <span class="label">Temperature</span>
                <span class="value"><?php echo round($weather['main']['temp'], 1); ?>°C</span>
            </div>
            <div class="weather-stat">
                <span class="label">Feels Like</span>
                <span class="value"><?php echo round($weather['main']['feels_like'], 1); ?>°C</span>
            </div>
            <div class="weather-stat">
                <span class="label">Humidity</span>
                <span class="value"><?php echo $weather['main']['humidity']; ?>%</span>
            </div>
            <div class="weather-stat">
                <span class="label">Wind Speed</span>
                <span class="value"><?php echo round($weather['wind']['speed'] * 2.237, 1); ?> mph</span>
            </div>
            <div class="weather-stat">
                <span class="label">Pressure</span>
                <span class="value"><?php echo $weather['main']['pressure']; ?> hPa</span>
            </div>
        </div>

        <?php if (!empty($weatherRecs)): ?>
            <h4 style="margin-bottom:10px;">Recommendations</h4>
            <?php foreach ($weatherRecs as $rec): ?>
                <div class="alert <?php echo $rec['type']; ?>">
                    <?php echo htmlspecialchars($rec['message']); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-success">✅ Weather conditions are suitable for all works.</div>
        <?php endif; ?>

    <?php else: ?>
        <p>Weather data unavailable.</p>
    <?php endif; ?>
</div>

<!-- Air Quality -->
<div class="panel">
    <h3>Air Quality</h3>
    <?php if ($aqiValue): ?>
        <div class="weather-grid">
            <div class="weather-stat">
                <span class="label">AQI</span>
                <span class="value"><?php echo $aqiValue; ?> - <?php echo getAQILabel($aqiValue); ?></span>
            </div>
            <?php $components = $airQuality['list'][0]['components']; ?>
            <div class="weather-stat">
                <span class="label">PM2.5</span>
                <span class="value"><?php echo round($components['pm2_5'], 1); ?> µg/m³</span>
            </div>
            <div class="weather-stat">
                <span class="label">PM10</span>
                <span class="value"><?php echo round($components['pm10'], 1); ?> µg/m³</span>
            </div>
            <div class="weather-stat">
                <span class="label">NO₂</span>
                <span class="value"><?php echo round($components['no2'], 1); ?> µg/m³</span>
            </div>
            <div class="weather-stat">
                <span class="label">CO</span>
                <span class="value"><?php echo round($components['co'], 1); ?> µg/m³</span>
            </div>
            <div class="weather-stat">
                <span class="label">O₃</span>
                <span class="value"><?php echo round($components['o3'], 1); ?> µg/m³</span>
            </div>
        </div>

        <?php foreach ($aqRecs as $rec): ?>
            <div class="alert <?php echo $rec['type']; ?>">
                <?php echo htmlspecialchars($rec['message']); ?>
            </div>
        <?php endforeach; ?>

    <?php else: ?>
        <p>Air quality data unavailable.</p>
    <?php endif; ?>
</div>

<!-- Forecast & Historical Data -->
<div class="panel">
    <h3>Weather Forecast & Historical Data</h3>
    <p style="margin-bottom:16px;font-size:0.9rem;color:#555;">
        Select a date to view weather and air quality forecast data. Forecast is available for the next 5 days.
        Historical data requires a paid API subscription — a message will be shown if the date is unavailable.
    </p>
    <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin-bottom:20px;">
        <input type="date" id="datePicker" style="padding:8px 12px;border:1px solid #ccc;border-radius:6px;font-size:0.95rem;">
        <button onclick="fetchDateWeather()" style="padding:8px 20px;background:#e94560;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:0.95rem;">Get Weather</button>
    </div>
    <div id="dateWeatherResult"></div>
    <div id="dateAQResult" style="margin-top:16px;"></div>
</div>

<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

    // Leaflet Map

    const lat = <?php echo $project['latitude']; ?>;
    const lon = <?php echo $project['longitude']; ?>;
    const projectName = <?php echo json_encode($project['project_name']); ?>;

    const map = L.map('map').setView([lat, lon], 16);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    L.marker([lat, lon])
        .addTo(map)
        .bindPopup('<strong>' + projectName + '</strong>')
        .openPopup();

    // Forecast Date Picker

    const today = new Date();
    const datePicker = document.getElementById('datePicker');
    datePicker.value = today.toISOString().split('T')[0];

    const projectLat = <?php echo $project['latitude']; ?>;
    const projectLon = <?php echo $project['longitude']; ?>;

    /**Fetches weather and air quality data for the selected date*/
    function fetchDateWeather() {
        const selectedDate = datePicker.value;
        const resultDiv = document.getElementById('dateWeatherResult');
        const aqResultDiv = document.getElementById('dateAQResult');

        if (!selectedDate) {
            resultDiv.innerHTML = '<div class="alert alert-warning">Please select a date.</div>';
            aqResultDiv.innerHTML = '';
            return;
        }

        const selected = new Date(selectedDate);
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        const maxForecast = new Date();
        maxForecast.setDate(now.getDate() + 5);

        // Past date
        if (selected < now) {
            resultDiv.innerHTML = '<div class="alert alert-warning">⚠️ Historical weather data is not available on the free API plan. Please select today or a future date within the next 5 days.</div>';
            aqResultDiv.innerHTML = '<div class="alert alert-warning">⚠️ Historical air quality data is not available on the free API plan.</div>';
            return;
        }

        // Beyond 5 day limit
        if (selected > maxForecast) {
            resultDiv.innerHTML = '<div class="alert alert-warning">⚠️ Weather forecast is only available for the next 5 days on the free API plan. The brief requires 8 days but this is an API plan limitation.</div>';
            aqResultDiv.innerHTML = '<div class="alert alert-warning">⚠️ Air quality forecast beyond 5 days is not available on the free API plan.</div>';
            return;
        }

        resultDiv.innerHTML = '<p style="color:#888;">Loading weather...</p>';
        aqResultDiv.innerHTML = '<p style="color:#888;">Loading air quality...</p>';

        // Fetch weather forecast
        fetch(`/Construction-dashboard/php/get_weather.php?action=forecast&lat=${projectLat}&lon=${projectLon}&date=${selectedDate}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    resultDiv.innerHTML = `<div class="alert alert-warning">⚠️ ${data.error}</div>`;
                    return;
                }
                let html = '<h4 style="margin-bottom:12px;">Weather Forecast</h4><div class="weather-grid">';
                data.forEach(entry => {
                    html += `
                        <div class="weather-stat">
                            <span class="label">${entry.time}</span>
                            <span class="value">${entry.temp}°C</span>
                            <span class="label">${entry.description}</span>
                            <span class="label">💨 ${entry.wind} mph</span>
                            <span class="label">💧 ${entry.humidity}%</span>
                        </div>`;
                });
                html += '</div>';
                resultDiv.innerHTML = html;
            })
            .catch(() => {
                resultDiv.innerHTML = '<div class="alert alert-danger">Failed to retrieve weather data. Please try again.</div>';
            });

        // Air quality — only available for today and 5 days upfront on free plan
        const todayStr = today.toISOString().split('T')[0];
        if (selectedDate === todayStr) {
            fetch(`/Construction-dashboard/php/get_airquality.php?lat=${projectLat}&lon=${projectLon}`)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        aqResultDiv.innerHTML = `<div class="alert alert-warning">⚠️ ${data.error}</div>`;
                        return;
                    }
                    const aqi = data.aqi;
                    const label = data.label;
                    const alertClass = aqi <= 2 ? 'alert-success' : aqi === 3 ? 'alert-warning' : 'alert-danger';
                    let html = '<h4 style="margin-bottom:12px;">Air Quality (Today)</h4>';
                    html += `<div class="weather-grid">
                        <div class="weather-stat"><span class="label">AQI</span><span class="value">${aqi} - ${label}</span></div>
                        <div class="weather-stat"><span class="label">PM2.5</span><span class="value">${data.pm2_5} µg/m³</span></div>
                        <div class="weather-stat"><span class="label">PM10</span><span class="value">${data.pm10} µg/m³</span></div>
                        <div class="weather-stat"><span class="label">NO₂</span><span class="value">${data.no2} µg/m³</span></div>
                    </div>`;
                    html += `<div class="alert ${alertClass}" style="margin-top:12px;">${data.recommendation}</div>`;
                    aqResultDiv.innerHTML = html;
                })
                .catch(() => {
                    aqResultDiv.innerHTML = '<div class="alert alert-danger">Failed to retrieve air quality data.</div>';
                });
        } else {
            aqResultDiv.innerHTML = '<div class="alert alert-warning">⚠️ Air quality forecast for future dates is not available on the free API plan. Current air quality is shown in the Air Quality section above.</div>';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>