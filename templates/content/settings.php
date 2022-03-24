<div>
<form action="savesetting" method="get">
Location:<br>
Address: <input type="text" name="address"><br>
<input type="text" name="latitude" style="display: none;" value<?php echo "\"" . $_['latitude'] . "\""; ?>>
<input type="text" name="longitude" style="display: none;" value<?php echo "\"" . $_['longitude'] . "\""; ?>>
<input type="text" name="timezone" style="display: none;" value<?php echo "\"" . $_['timezone'] . "\""; ?>>
<input type="text" name="elevation" style="display: none;" value<?php echo "\"" . $_['elevation'] . "\""; ?>>
<input type="submit">
</form>
<br>
<button id="btnmtoggle">Click for manuel settings</button>
<div id="divmanuel" style="display: none;">
Manuel settings: <br>
<form action="savesetting" method="get">
<input type="text" name="address" style="display: none;" value"">
Latitude: <input type="text" name="latitude" value=<?php echo "\"" . $_['latitude'] . "\""; ?>><br>
Longitude: <input type="text" name="longitude" value=<?php echo "\"" . $_['longitude'] . "\""; ?>><br>
Timezone: <input list="timezones" name="timezone" id="timezone" value=<?php echo "\"" . $_['timezone'] . "\""; ?>><br>
<datalist id="timezones">
  <option value="UTC">
  <option value="+0100">
  <option value="+0200">
  <option value="+0300">
  <option value="+0400">
  <option value="+0500">
  <option value="+0600">
  <option value="+0700">
  <option value="-0100">
</datalist>
Altitude: <input type="text" name="elevation" value=<?php echo "\"" . $_['elevation'] . "\""; ?>><br>
<input type="submit">
</form>
</div>
</div>
