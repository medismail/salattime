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

function updateCalendar() {
  var checkb = document.querySelector("#salatcalendar");
  if (checkb.checked) {
    fetch('calendar/addcalendar', {
      method: 'POST',
    }).then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok.')
      }
    }).catch(console.error)
  } else {
    fetch('calendar/removecalendar', {
      method: 'POST',
    }).then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok.')
      }
    }).catch(console.error)
  }
}

var notificationcheckbox = document.querySelector("#salatnotification");
var calendarcheckbox = document.querySelector("#salatcalendar");
notificationcheckbox.addEventListener("click", updateNotification);
calendarcheckbox.addEventListener("click", updateCalendar);
