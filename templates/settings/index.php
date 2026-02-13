<?php
// Existing content of index.php

function display_geolocation_button() {
    echo '<button id="getAddressButton">Get Address</button>';
    echo '<div id="addressDisplay"></div>';
    echo <<<'SCRIPT'
    <script>
        document.getElementById("getAddressButton").addEventListener("click", function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    var lat = position.coords.latitude;
                    var lon = position.coords.longitude;
                    fetch(`https://api.example.com/getAddress?lat=${lat}&lon=${lon}`) // replace with actual API
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById("addressDisplay").innerText = data.address;
                        })
                        .catch(error => console.error('Error fetching address:', error));
                });
            } else {
                alert("Geolocation is not supported by this browser.");
            }
        });
    </script>
    SCRIPT;
}

// Call the function where appropriate
// display_geolocation_button();
