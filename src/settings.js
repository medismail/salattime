function switchHidden() {
  var x = document.getElementById("divmanuel");
  if (x.style.display === "none") {
    x.style.display = "block";
  } else {
    x.style.display = "none";
  }
}

var button = document.querySelector("#btnmtoggle");
button.addEventListener("click", switchHidden);
