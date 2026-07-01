<?php
session_start();
require_once '../../middleware/auth.php';
checkRole('donor');

$conn = new mysqli("localhost", "root", "", "f_destiny");

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$email = $_SESSION['user'];

$user = $conn->query("SELECT * FROM users WHERE email='$email'");
$donor = $user->fetch_assoc();

$pageTitle = "Donation Map";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        #map {
            height: 65vh;
            width: 100%;
        }
        .leaflet-bar {
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            border-radius: 8px !important;
            overflow: hidden;
        }
        
        /* Satelite Contrast Marker Pulse Animations */
        @keyframes pulse-blue {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.9); }
            70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(59, 130, 246, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(59, 130, 246, 0); }
        }
        @keyframes pulse-purple {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(168, 85, 247, 0.9); }
            70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(168, 85, 247, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(168, 85, 247, 0); }
        }
        @keyframes pulse-green {
            0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.9); }
            70% { transform: scale(1); box-shadow: 0 0 0 14px rgba(34, 197, 94, 0); }
            100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }
        
        .glow-marker-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: visible !important;
        }
        .marker-label {
            background: rgba(255, 255, 255, 0.95);
            color: #1e293b;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 6px;
            white-space: nowrap;
            box-shadow: 0 4px 10px rgba(0,0,0,0.25);
            border: 1px solid rgba(0,0,0,0.1);
            transform: translateY(-24px);
            pointer-events: none;
        }
        .donor-glow-core { width: 14px; height: 14px; background: #3b82f6; border: 2px solid white; border-radius: 50%; animation: pulse-blue 2s infinite; }
        .volunteer-glow-core { width: 12px; height: 12px; background: #a855f7; border: 2px solid white; border-radius: 50%; animation: pulse-purple 2s infinite; }
        .search-glow-core { width: 14px; height: 14px; background: #22c55e; border: 2px solid white; border-radius: 50%; animation: pulse-green 2s infinite; }

        /* CRITICAL FIXES FOR THE GOOGLE SUGGESTIONS CONTAINER */
        .pac-container {
            background-color: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1) !important;
            font-family: ui-sans-serif, system-ui, sans-serif !important;
            padding: 6px 0 !important;
            margin-top: 4px !important;
            /* Keeps the autocomplete listing above Leaflet layers and overlays */
            z-index: 99999 !important; 
        }
        .pac-item {
            padding: 12px 16px !important;
            font-size: 13px !important;
            color: #475569 !important;
            cursor: pointer;
            border-top: 1px solid #f1f5f9 !important;
            display: flex;
            align-items: center;
        }
        .pac-item:first-child { border-top: none !important; }
        .pac-item:hover, .pac-item:active { background-color: #f8fafc !important; color: #059669 !important; }
        .pac-item-query { font-size: 13px !important; color: #0f172a !important; padding-right: 4px; }
        .pac-matched { color: #10b981 !important; font-weight: 700; }
        .hdg-recent { padding: 8px 12px; font-size: 11px; font-weight: 700; color: #94a3b8; background-color: #f8fafc; letter-spacing: 0.05em; }
        .pac-logo:after { display: none !important; } /* Cleans up default footer if unwanted */
    </style>
</head>

<body class="bg-gray-50 min-h-screen text-slate-800 font-sans antialiased">

    <header class="glass-nav sticky top-0 z-50 border-b border-slate-200/60 shadow-sm px-6 py-3.5 flex justify-between items-center">
        <div class="flex items-center gap-3 select-none shrink-0">
            <span class="text-2xl drop-shadow-sm transform transition-transform duration-300 hover:rotate-12 inline-block">🍱</span>
            <span class="text-xl font-black text-emerald-600 tracking-tight uppercase bg-clip-text">F-Destiny</span>
        </div>
        
        <div class="hidden md:block text-center px-4">
            <h1 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest font-mono">Donations Interface</h1>
            <p class="text-sm text-slate-700 font-semibold mt-0.5">Map </p>
        </div>

        <a href="index.php" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-emerald-700 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/20 backdrop-blur-md transition-all duration-200 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 shrink-0">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard</span>
        </a>
    </header>

    <main class="max-w-7xl mx-auto p-4 md:p-6 space-y-4">

    <div class="relative bg-white border border-slate-200 rounded-2xl p-4 shadow-sm z-50">
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.604 10.604z" /></svg>
            </div>
            <input
                id="searchBox"
                type="text"
                placeholder="Search drop locations or specific addresses via Google Maps..."
                autocomplete="off"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:bg-white focus:ring-4 focus:ring-emerald-500/10 transition-all"
            >
        </div>
        
        <div id="historyResults" class="absolute left-4 right-4 mt-2 bg-white border border-slate-200 rounded-xl shadow-xl hidden max-h-60 overflow-y-auto z-50 divide-y divide-slate-100"></div>
    </div>

    <div class="relative rounded-2xl overflow-hidden border border-slate-200 shadow-md bg-slate-200 z-30">
        <div id="map" style="height: 500px; width: 100%;"></div>
        
        <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-sm border border-slate-200 px-4 py-3 rounded-xl z-[1000] text-xs space-y-2 font-semibold tracking-wide shadow-md text-slate-700">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block ring-4 ring-blue-500/20"></span>
                <span>Donor Location</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-purple-500 inline-block ring-4 ring-purple-500/20"></span>
                <span>Active Volunteers</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-green-500 inline-block ring-4 ring-green-500/20"></span>
                <span>Searched Destination</span>
            </div>
        </div>
    </div>

</main>

<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAChT0X3_XXwJig8rz4bwEFkOgfGAClsDU&libraries=places"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Donor address from PHP (replace with actual donor address)
    const donorAddress = "<?= htmlspecialchars($row['address'] ?? 'New York, NY') ?>";
    const donorLat = <?= isset($donorLat) ? $donorLat : 'null' ?>;
    const donorLng = <?= isset($donorLng) ? $donorLng : 'null' ?>;
    
    let map;
    let marker;
    let infoWindow;
    let geocoder;
    let searchBox;

    // Initialize the map with satellite view
    function initMap() {
        // Default center (will be updated with donor location)
        const defaultCenter = { lat: 40.7128, lng: -74.0060 }; // New York as default
        
        map = new google.maps.Map(document.getElementById('map'), {
            center: defaultCenter,
            zoom: 15,
            mapTypeId: 'satellite', // Set to satellite view
            tilt: 0,
            mapTypeControl: true,
            mapTypeControlOptions: {
                style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
                position: google.maps.ControlPosition.TOP_RIGHT
            },
            streetViewControl: true,
            streetViewControlOptions: {
                position: google.maps.ControlPosition.RIGHT_CENTER
            },
            fullscreenControl: true,
            fullscreenControlOptions: {
                position: google.maps.ControlPosition.RIGHT_TOP
            },
            zoomControl: true,
            zoomControlOptions: {
                position: google.maps.ControlPosition.RIGHT_BOTTOM
            }
        });

        geocoder = new google.maps.Geocoder();
        infoWindow = new google.maps.InfoWindow();

        // Create custom marker icon
        const markerIcon = {
            url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
            scaledSize: new google.maps.Size(40, 40),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(20, 40)
        };

        // If we have donor coordinates, add marker directly
        if (donorLat && donorLng) {
            const donorLocation = { lat: parseFloat(donorLat), lng: parseFloat(donorLng) };
            addDonorMarker(donorLocation);
            map.setCenter(donorLocation);
        } 
        // Otherwise, geocode the address
        else if (donorAddress) {
            geocodeAddress(donorAddress);
        }

        // Setup search box
        setupSearchBox();
        
        // Add click event listener to map
        map.addListener('click', function(e) {
            addMarkerAtClick(e.latLng);
        });

        // Try HTML5 geolocation for user's location
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const pos = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Add user location marker (small blue dot)
                    const userMarker = new google.maps.Marker({
                        position: pos,
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            fillColor: '#3B82F6',
                            fillOpacity: 1,
                            strokeColor: '#FFFFFF',
                            strokeWeight: 3,
                            scale: 10
                        },
                        title: 'Your Location'
                    });
                    
                    // Add info window for user location
                    const userInfo = new google.maps.InfoWindow({
                        content: '<div style="padding: 8px; font-size: 12px; font-weight: 600; color: #1e293b;">📍 Your Current Location</div>'
                    });
                    
                    userMarker.addListener('click', function() {
                        userInfo.open(map, userMarker);
                    });
                    
                    // Center map on user location if no donor location set
                    if (!donorLat && !donorLng && !donorAddress) {
                        map.setCenter(pos);
                    }
                },
                function() {
                    // Geolocation failed, keep default center
                    console.log('Geolocation failed or not supported');
                }
            );
        }

        // Add volunteer markers (example - replace with actual volunteer data)
        addVolunteerMarkers();
    }

    // Geocode address to get coordinates
    function geocodeAddress(address) {
        geocoder.geocode({ address: address }, function(results, status) {
            if (status === 'OK') {
                const location = results[0].geometry.location;
                addDonorMarker(location);
                map.setCenter(location);
                
                // Also show the address in a info window
                setTimeout(() => {
                    infoWindow.open(map, marker);
                }, 500);
            } else {
                console.error('Geocode was not successful for the following reason: ' + status);
                // Try to show approximate location
                showApproximateLocation();
            }
        });
    }

    // Add donor marker
    function addDonorMarker(location) {
        // Create custom donor marker
        const markerIcon = {
            url: 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
            scaledSize: new google.maps.Size(45, 45),
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(22, 45)
        };

        marker = new google.maps.Marker({
            position: location,
            map: map,
            icon: markerIcon,
            title: 'Donor Location',
            animation: google.maps.Animation.DROP
        });

        // Create info window content with donor details
        const content = `
            <div style="padding: 12px; max-width: 250px; font-family: system-ui, -apple-system, sans-serif;">
                <div style="font-weight: 700; font-size: 14px; color: #0f172a; margin-bottom: 4px;">
                    🏠 Donor Location
                </div>
                <div style="font-size: 12px; color: #475569; margin-bottom: 4px;">
                    <strong>Address:</strong> ${donorAddress}
                </div>
                <div style="font-size: 12px; color: #475569;">
                    <strong>Coordinates:</strong> ${location.lat().toFixed(6)}, ${location.lng().toFixed(6)}
                </div>
                <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #e2e8f0; display: flex; gap: 6px; flex-wrap: wrap;">
                    <button onclick="window.open('https://www.google.com/maps/dir/?api=1&destination=${location.lat()},${location.lng()}', '_blank')" 
                            style="background: #10b981; color: white; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">
                        🗺️ Get Directions
                    </button>
                    <button onclick="toggleSatellite()" 
                            style="background: #6366f1; color: white; border: none; padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 600; cursor: pointer;">
                        🛰️ Satellite View
                    </button>
                </div>
            </div>
        `;

        infoWindow.setContent(content);
        infoWindow.open(map, marker);
        
        // Add click listener to marker
        marker.addListener('click', function() {
            infoWindow.open(map, marker);
        });

        // Add a circle around the location for better visibility
        const circle = new google.maps.Circle({
            strokeColor: '#EF4444',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#EF4444',
            fillOpacity: 0.15,
            map: map,
            center: location,
            radius: 100 // 100 meters
        });

        // Zoom in slightly after marker is placed
        setTimeout(() => {
            map.setZoom(17);
        }, 300);
    }

    // Show approximate location if geocoding fails
    function showApproximateLocation() {
        // Try to get location from the address string using a fallback
        const fallbackLocation = { lat: 40.7128, lng: -74.0060 };
        addDonorMarker(fallbackLocation);
        map.setCenter(fallbackLocation);
        
        infoWindow.setContent(`
            <div style="padding: 12px;">
                <div style="font-weight: 700; color: #ef4444;">⚠️ Approximate Location</div>
                <div style="font-size: 12px; color: #475569;">Could not find exact address. Showing approximate location.</div>
            </div>
        `);
    }

    // Add volunteer markers (example data)
    function addVolunteerMarkers() {
        // Example volunteer locations - replace with actual data from database
        const volunteerLocations = [
            { lat: 40.7148, lng: -74.0060, name: 'Volunteer 1' },
            { lat: 40.7108, lng: -74.0080, name: 'Volunteer 2' },
            { lat: 40.7168, lng: -74.0020, name: 'Volunteer 3' }
        ];

        volunteerLocations.forEach((loc, index) => {
            const volunteerMarker = new google.maps.Marker({
                position: { lat: loc.lat, lng: loc.lng },
                map: map,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '#8B5CF6',
                    fillOpacity: 0.9,
                    strokeColor: '#FFFFFF',
                    strokeWeight: 2,
                    scale: 8
                },
                title: loc.name,
                animation: google.maps.Animation.BOUNCE
            });

            const volunteerInfo = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; font-weight: 600; color: #1e293b;">
                        🚴 ${loc.name}
                    </div>
                `
            });

            volunteerMarker.addListener('click', function() {
                volunteerInfo.open(map, volunteerMarker);
            });
        });
    }

    // Setup search box for places
    function setupSearchBox() {
        const input = document.getElementById('searchBox');
        searchBox = new google.maps.places.SearchBox(input);
        
        map.addListener('bounds_changed', function() {
            searchBox.setBounds(map.getBounds());
        });

        searchBox.addListener('places_changed', function() {
            const places = searchBox.getPlaces();
            
            if (places.length === 0) {
                return;
            }

            const place = places[0];
            if (!place.geometry || !place.geometry.location) {
                console.log('Returned place contains no geometry');
                return;
            }

            // Center map on selected place
            map.setCenter(place.geometry.location);
            map.setZoom(16);

            // Add marker for searched location
            const searchMarker = new google.maps.Marker({
                map: map,
                position: place.geometry.location,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: '#22C55E',
                    fillOpacity: 1,
                    strokeColor: '#FFFFFF',
                    strokeWeight: 3,
                    scale: 10
                },
                title: place.name || 'Searched Location',
                animation: google.maps.Animation.DROP
            });

            const searchInfo = new google.maps.InfoWindow({
                content: `
                    <div style="padding: 8px; max-width: 200px;">
                        <div style="font-weight: 700; color: #0f172a;">📍 ${place.name || 'Location'}</div>
                        <div style="font-size: 11px; color: #64748b;">${place.formatted_address || ''}</div>
                    </div>
                `
            });

            searchMarker.addListener('click', function() {
                searchInfo.open(map, searchMarker);
            });

            searchInfo.open(map, searchMarker);

            // Update search history
            updateSearchHistory(place.name || place.formatted_address);
        });
    }

    // Add marker on map click
    function addMarkerAtClick(latLng) {
        const clickMarker = new google.maps.Marker({
            position: latLng,
            map: map,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                fillColor: '#F59E0B',
                fillOpacity: 0.8,
                strokeColor: '#FFFFFF',
                strokeWeight: 2,
                scale: 8
            }
        });

        // Reverse geocode to get address
        geocoder.geocode({ location: latLng }, function(results, status) {
            if (status === 'OK' && results[0]) {
                const clickInfo = new google.maps.InfoWindow({
                    content: `
                        <div style="padding: 8px; max-width: 200px;">
                            <div style="font-weight: 600; color: #0f172a;">📍 Selected Location</div>
                            <div style="font-size: 11px; color: #64748b;">${results[0].formatted_address}</div>
                        </div>
                    `
                });
                clickMarker.addListener('click', function() {
                    clickInfo.open(map, clickMarker);
                });
            }
        });
    }

    // Toggle between satellite and roadmap view
    window.toggleSatellite = function() {
        const currentType = map.getMapTypeId();
        if (currentType === 'satellite') {
            map.setMapTypeId('roadmap');
            document.querySelector('[onclick="toggleSatellite()"]').textContent = '🌍 Map View';
        } else {
            map.setMapTypeId('satellite');
            document.querySelector('[onclick="toggleSatellite()"]').textContent = '🛰️ Satellite View';
        }
    };

    // Update search history
    function updateSearchHistory(location) {
        const historyContainer = document.getElementById('historyResults');
        const historyItem = document.createElement('div');
        historyItem.className = 'p-3 hover:bg-slate-50 cursor-pointer text-sm text-slate-700 flex items-center gap-2';
        historyItem.innerHTML = `
            <span class="text-slate-400">🔍</span>
            <span>${location}</span>
        `;
        
        historyItem.addEventListener('click', function() {
            // Trigger search again
            document.getElementById('searchBox').value = location;
            historyContainer.classList.add('hidden');
        });
        
        historyContainer.prepend(historyItem);
        historyContainer.classList.remove('hidden');
        
        // Show history on input focus
        document.getElementById('searchBox').addEventListener('focus', function() {
            if (historyContainer.children.length > 0) {
                historyContainer.classList.remove('hidden');
            }
        });
        
        // Hide history on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#searchBox') && !e.target.closest('#historyResults')) {
                historyContainer.classList.add('hidden');
            }
        });
    }

    // Load the map
    if (typeof google !== 'undefined') {
        initMap();
    } else {
        console.error('Google Maps API not loaded');
        // Fallback: show a message
        document.getElementById('map').innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #f1f5f9; color: #64748b; font-family: system-ui;">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 40px; margin-bottom: 10px;">🗺️</div>
                    <div style="font-weight: 600;">Map is loading...</div>
                    <div style="font-size: 12px; margin-top: 4px;">Please check your internet connection</div>
                </div>
            </div>
        `;
    }
});
</script>

<style>
    /* Map container styles */
    #map {
        height: 500px;
        width: 100%;
    }
    
    /* Custom scrollbar for history */
    #historyResults::-webkit-scrollbar {
        width: 6px;
    }
    
    #historyResults::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 10px;
    }
    
    #historyResults::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    
    #historyResults::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    
    /* Animation for marker drop */
    @keyframes drop {
        0% {
            transform: translateY(-100px);
            opacity: 0;
        }
        100% {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    .gm-style-iw {
        padding: 0 !important;
    }
</style>
    <script>
    let map;
    let volunteerMarkers = [];
    let searchMarker = null;
    let donorMarker = null;

    const defaultLat = 17.385044;
    const defaultLng = 78.486671;

    function initMap() {
        map = L.map('map', { zoomControl: true }).setView([defaultLat, defaultLng], 12);

        L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Tiles &copy; Esri &mdash; Source: Esri Satellite',
            maxZoom: 18
        }).addTo(map);

        map.zoomControl.setPosition('topright');

        setupGoogleAutocomplete();
        loadVolunteers();
        setInterval(loadVolunteers, 5000);

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(pos) {
                let donorLat = pos.coords.latitude;
                let donorLng = pos.coords.longitude;

                if (donorMarker) map.removeLayer(donorMarker);

                let customDonorHtml = `
                    <div class="glow-marker-container">
                        <div class="marker-label">You</div>
                        <div class="donor-glow-core"></div>
                    </div>
                `;

                let pulseIcon = L.divIcon({
                    html: customDonorHtml,
                    className: '',
                    iconSize: [100, 40],
                    iconAnchor: [50, 20]
                });

                donorMarker = L.marker([donorLat, donorLng], { icon: pulseIcon }).addTo(map);
                map.setView([donorLat, donorLng], 15);
            }, function(err) {
                console.log("Geolocation turned off or rejected.");
            });
        }
    }

    function setupGoogleAutocomplete() {
        const input = document.getElementById("searchBox");
        const historyDiv = document.getElementById("historyResults");

        // Set options to link explicitly with the search text input
        const options = {
            fields: ["geometry", "name", "formatted_address"],
            types: ["geocode", "establishment"]
        };

        const autocomplete = new google.maps.places.Autocomplete(input, options);

        // Fix positioning context dynamically so it handles changes cleanly
        google.maps.event.addDomListener(input, 'keydown', function(e) {
            if (e.keyCode === 13) { 
                e.preventDefault(); // Block unexpected form submittals on Enter keypress
            }
        });

        function getHistory() {
            let history = localStorage.getItem("google_searches");
            return history ? JSON.parse(history) : [];
        }

        function saveToHistory(name, lat, lon) {
            let history = getHistory();
            history = history.filter(item => item.name !== name);
            history.unshift({ name, lat, lon });
            if (history.length > 5) history.pop();
            localStorage.setItem("google_searches", JSON.stringify(history));
        }

        function showHistory() {
            let history = getHistory();
            if (history.length === 0) {
                historyDiv.classList.add("hidden");
                return;
            }
            historyDiv.innerHTML = '<div class="hdg-recent">RECENT SEARCHES</div>';
            
            history.forEach(item => {
                const row = document.createElement("div");
                row.className = "p-3 hover:bg-slate-50 cursor-pointer text-xs text-slate-700 hover:text-emerald-600 transition-colors font-medium border-b border-slate-100 last:border-0 flex items-center gap-2";
                row.innerHTML = `<span>🕒</span> <span class="truncate">${item.name}</span>`;
                
                row.addEventListener("mousedown", (e) => {
                    e.preventDefault(); 
                    navigateToLocation(item.lat, item.lon, item.name);
                    input.value = item.name;
                    historyDiv.classList.add("hidden");
                });
                historyDiv.appendChild(row);
            });
            historyDiv.classList.remove("hidden");
        }

        function navigateToLocation(lat, lon, displayName) {
            if (searchMarker) map.removeLayer(searchMarker);

            let briefName = displayName.split(',')[0];

            let customSearchHtml = `
                <div class="glow-marker-container">
                    <div class="marker-label" style="border-color: #22c55e;">${briefName}</div>
                    <div class="search-glow-core"></div>
                </div>
            `;

            let searchIcon = L.divIcon({
                html: customSearchHtml,
                className: '',
                iconSize: [140, 40],
                iconAnchor: [70, 20]
            });

            searchMarker = L.marker([lat, lon], { icon: searchIcon }).addTo(map);

            map.flyTo([lat, lon], 16, {
                animate: true,
                duration: 1.2
            });
        }

        input.addEventListener("focus", function() {
            if (input.value.trim().length === 0) {
                showHistory();
            }
        });

        input.addEventListener("input", function() {
            if (input.value.trim().length === 0) {
                showHistory();
            } else {
                historyDiv.classList.add("hidden");
            }
        });

        // Safe dismiss wrapper that doesn't intercept Google selection maps logic
        document.addEventListener("click", function(e) {
            if (e.target !== input && !historyDiv.contains(e.target)) {
                historyDiv.classList.add("hidden");
            }
        });

        // Fired when the user selects a location recommendation from Google's dropdown list
        autocomplete.addListener("place_changed", function () {
            const place = autocomplete.getPlace();

            if (!place.geometry || !place.geometry.location) {
                console.warn("Returned Google Place contains no valid geometries.");
                return;
            }

            const lat = place.geometry.location.lat();
            const lon = place.geometry.location.lng();
            const targetName = place.formatted_address || place.name;

            navigateToLocation(lat, lon, targetName);
            saveToHistory(targetName, lat, lon);
        });
    }

    function loadVolunteers() {
        fetch("get_volunteers.php")
        .then(response => response.json())
        .then(data => {
            volunteerMarkers.forEach(marker => map.removeLayer(marker));
            volunteerMarkers = [];

            data.forEach(v => {
                let lat = parseFloat(v.latitude);
                let lng = parseFloat(v.longitude);

                if (!isNaN(lat) && !isNaN(lng)) {
                    let customVolunteerHtml = `
                        <div class="glow-marker-container">
                            <div class="marker-label" style="border-color: #a855f7;">${v.name}</div>
                            <div class="volunteer-glow-core"></div>
                        </div>
                    `;

                    let volunteerIcon = L.divIcon({
                        html: customVolunteerHtml,
                        className: '',
                        iconSize: [100, 40],
                        iconAnchor: [50, 20]
                    });

                    let marker = L.marker([lat, lng], { icon: volunteerIcon }).addTo(map);
                    volunteerMarkers.push(marker);
                }
            });
        })
        .catch(err => console.error("Could not sync telemetry positions:", err));
    }

    document.addEventListener("DOMContentLoaded", initMap);
    </script>
</body>
</html>