<!DOCTYPE html>
<html>
<head>
    <title>Test Map</title>
    <style>
        #map{
            height:500px;
            width:100%;
        }
    </style>
</head>
<body>

<div id="map"></div>

<script>
function initMap(){
    new google.maps.Map(
        document.getElementById("map"),
        {
            center:{lat:17.385044,lng:78.486671},
            zoom:12
        }
    );
}
</script>

<script async defer
src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAChT0X3_XXwJig8rz4bwEFkOgfGAClsDU
&callback=initMap">
</script>

</body>
</html>