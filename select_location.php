<!DOCTYPE html>
<html>
<head>
    <title>Select Location</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body>

<h2>Select Your Location</h2>
<div id="map" style="height:400px;"></div>
<br>
<button onclick="saveLocation()">Save Location</button>

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
