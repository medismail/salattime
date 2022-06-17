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

var button = document.querySelector("#btnmtoggle");
button.addEventListener("click", switchHidden);

var checkbox = document.querySelector("#checkbox1224");
checkbox.addEventListener("click", update1224);
