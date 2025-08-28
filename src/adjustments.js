function updatenma() {
  var checkb = document.querySelector("#checkboxnma");
  var vday = document.querySelector('input[name="vday"]');
  var nma = document.querySelector('input[name="nma"]');
  if (checkb.checked) {
    nma.value = "15";
    vday.disabled = true;
  } else {
    nma.value = "0";
    vday.disabled = false;
  }
}

var checkbox = document.querySelector("#checkboxnma");
checkbox.addEventListener("click", updatenma);

var daychange = document.querySelector('input[name="vday"]');
daychange.addEventListener('change', function() {
    var day = document.querySelector('input[name="day"]');
    day.value = this.value;
  });
