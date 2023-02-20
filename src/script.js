function updateNotification() {
  var checkb = document.querySelector("#salatnotification");
  if (checkb.checked) {
    fetch('notification/addjob', {
      method: 'POST',
    }).then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok.')
      }
    }).catch(console.error)
  } else {
    fetch('notification/removejob', {
      method: 'POST',
    }).then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok.')
      }
    }).catch(console.error)
  }
}

var notificationcheckbox = document.querySelector("#salatnotification");
notificationcheckbox.addEventListener("click", updateNotification);
