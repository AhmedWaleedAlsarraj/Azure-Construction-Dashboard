<?php
/**About page - references and attributions*/
$pageTitle = 'About';
include 'includes/header.php';
?>

<h2>About This Project</h2>

<div class="project-detail">
    <h3>Project Overview</h3>
    <p>This construction project dashboard was developed as part of a Cloud Computing module by Ahmed Waleed W22045113. It displays live construction project data including location, weather, and air quality information to help project managers make informed decisions on site.</p>
</div>

<div class="project-detail">
    <h3>Technologies Used</h3>
    <ul class="resources-list">
        <li><strong>PHP 8.2</strong> - Server-side scripting and database queries</li>
        <li><strong>MariaDB</strong> - Relational database for project and resource data</li>
        <li><strong>HTML5 & CSS3</strong> -Frontend structure and styling</li>
        <li><strong>JavaScript</strong> - Map initialisation and dynamic interactions</li>
        <li><strong>Apache</strong> - Web server</li>
        <li><strong>Microsoft Azure</strong> - Cloud hosting platform</li>
    </ul>
</div>

<div class="project-detail">
    <h3>Third Party APIs & Libraries</h3>
    <ul class="resources-list">
        <li>
            <strong>OpenWeather API</strong>
            Used for current weather, air quality, and forecast data. 
            <a href="https://openweathermap.org/api" target="_blank">openweathermap.org/api</a>
        </li>
        <li>
            <strong>Leaflet.js v1.9.4</strong>
            Open source JavaScript library for interactive maps. 
            <a href="https://leafletjs.com" target="_blank">leafletjs.com</a> - 
            Licensed under <a href="https://opensource.org/licenses/BSD-2-Clause" target="_blank">BSD 2-Clause License</a>
        </li>
        <li>
            <strong>OpenStreetMap</strong>
            Map tile data used by Leaflet. 
            <a href="https://www.openstreetmap.org/copyright" target="_blank">openstreetmap.org</a> - 
            © OpenStreetMap contributors, licensed under ODbL.
        </li>
    </ul>
</div>

<div class="project-detail">
    <h3>Sustainability</h3>
    <p>This website has been built with carbon impact in mind. No unnecessary frameworks or libraries have been included. The application is hosted on an Azure VM. CSS and JavaScript are kept minimal and served from CDN where possible to reduce server load.</p>
</div>

<div class="project-detail">
    <h3>Academic Integrity</h3>
    <p>All code in this project was written by the student, AI was only used for problem solving when neccessary. No code frameworks such as React, Vue or Laravel were used. External libraries are limited to Leaflet.js for mapping functionality.</p>
</div>

<?php include 'includes/footer.php'; ?>