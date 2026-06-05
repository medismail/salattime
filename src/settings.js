function switchHidden() {
  var x = document.getElementById("divmanuel");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}

function update1224() {
  var checkb = document.querySelector("#checkbox1224");
  const format1224s = document.querySelectorAll('input[name="format_12_24"]');
  format1224s.forEach(function(f1224) {
    if (checkb.checked) {
      f1224.value = "24h";
    } else {
      f1224.value = "12h";
    }
  });
}

function getGeo() {
    const options = {
        enableHighAccuracy: false,
        timeout: 10000,
        maximumAge: 0
    };

    function geoSuccess(position) {
        var lat = position.coords.latitude;
        var lon = position.coords.longitude;
        const latitude = document.querySelectorAll('input[name="latitude"]');
        latitude.forEach(function(lati) {
            lati.value = lat;
        });
        const longitude = document.querySelectorAll('input[name="longitude"]');
        longitude.forEach(function(longi) {
            longi.value = lon;
        });
    }

    function geoError(err) {
        console.log(`ERROR(${err.code}): ${err.message}`);
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(geoSuccess, geoError, options);
    }
}

var button = document.querySelector("#btnmtoggle");
button.addEventListener("click", switchHidden);

var checkbox = document.querySelector("#checkbox1224");
checkbox.addEventListener("click", update1224);

var btngetgeo = document.querySelector("#getgeo");
btngetgeo.addEventListener("click", getGeo);
