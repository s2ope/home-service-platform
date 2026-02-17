<!DOCTYPE html>
<html>
<head>
    <title>Select Location</title>
    <link rel="stylesheet" href="assets/css/main.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="is-preload">

<div id="wrapper">
    <div id="main">
        <div class="inner">
            <header id="header">
                <a href="welcome.php" class="logo"><strong>home service</strong></a>
            </header>

            <br>
            <h2 id="elements">Service Booking</h2>
            <hr class="major" />
            <div class="row gtr-200">
                <div class="col-12 col-12-medium">

<label>Select Your Location</label>
<div id="map" style="height:400px;"></div>
<br>
<button onclick="saveLocation()">Save Location</button>
</div>
            </div>
        </div>
    </div>
<script>
var map = L.map('map').setView([27.7172, 85.3240], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

var marker;
var selectedLat;
var selectedLng;

map.on('click', function(e){
    if(marker) map.removeLayer(marker);
    marker = L.marker(e.latlng).addTo(map);
    selectedLat = e.latlng.lat;
    selectedLng = e.latlng.lng;
});

function saveLocation(){
    if(selectedLat === undefined || selectedLng === undefined){
        alert("Please click on map to select location!");
        return;
    }

    // Send location back to service_booking.php
    window.opener.document.getElementById("user_lat").value = selectedLat;
    window.opener.document.getElementById("user_lng").value = selectedLng;

    window.opener.document.getElementById("location_display").value =
        selectedLat + ", " + selectedLng;

    window.close();
}
</script>

</body>
</html>
