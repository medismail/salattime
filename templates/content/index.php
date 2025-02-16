<div id="main-content-div">
  <div id="prayertime" class="viewcontainer">
    <section id="sheader">
      <h2 style="font-family:Arial;"><span id="hijri-date"><?php echo $_['Hijri']; ?></span></h2>
    </section>
    <section id="day-info">
        <h2><?php echo $l->t('Location'); ?></h2>
        <p><?php echo $l->t('City'); ?>:  &emsp;<span id="city"><?php echo $_['City']; ?></span></p>
        <br>
        <?php if ($_['SpecialDay']) {
        	echo "<p>", $l->t('Today is'), ": &emsp; <b>", $_['SpecialDay'], '</b></p><br>';
        } ?>
        <!-- Prayer Times Table -->
        <h2><?php echo $l->t('Prayer Times'); ?></h2>
        <p><span id="next"><?php echo $l->t('Next') . ": <b>" . $l->t($_['Salat']) . "</b> " . $l->t('after') . ": <b>" . $_['Remain'] . '</b><br>'; ?></span></p>
        <?php $gback = [ 'Fajr' => "", 'Dhuhr' => "", 'Asr' => "", 'Maghrib' => "", 'Isha' => "", ];
        $gback[$_['Salat']] = " style=\"background: gray; font-weight: bold;\""; ?>
        <table>
            <thead>
                <tr>
                    <th><?php echo $l->t('Salat'); ?></th>
                    <th><?php echo $l->t('Time'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr<?php echo $gback['Fajr']; ?>>
                    <td scope="row"><?php echo $l->t('Fajr'); ?></td>
                    <td><?php echo $_['Fajr']; ?></td>
                </tr>
                <?php if (isset($_['Jumaa'])) {
                	echo "
                <tr>
                   <td scope=\"row\">", $l->t('Juma\'a'), "</td>
                   <td>", $_['Dhuhr'], "</td>
                </tr>";
                } ?>
                <tr<?php echo $gback['Dhuhr']; ?>>
                    <td scope="row"><?php echo $l->t('Dhuhr'); ?></td>
                    <td><?php echo $_['Dhuhr']; ?></td>
                </tr>
                <tr<?php echo $gback['Asr']; ?>>
                    <td scope="row"><?php echo $l->t('Asr'); ?></td>
                    <td><?php echo $_['Asr']; ?></td>
                </tr>
                <tr<?php echo $gback['Maghrib']; ?>>
                    <td scope="row"><?php echo $l->t('Maghrib'); ?></td>
                    <td><?php echo $_['Maghrib']; ?></td>
                </tr>
                <tr<?php echo $gback['Isha']; ?>>
                    <td scope="row"><?php echo $l->t('Isha'); ?></td>
                    <td><?php echo $_['Isha']; ?></td>
                </tr>
            </tbody>
        </table>
        <br>
        <h2><?php echo $l->t('Day Information'); ?></h2>
        <p><?php echo $l->t('Day length'); ?>:  &emsp;  <span id="moonset"><?php echo $_['DayLength']; ?></span></p>
        <?php if ($_['Imsak'] != "") {
        	echo '<p>', $l->t('Imsak') . ":&emsp;&emsp;&emsp;&emsp;<span id=\"imsak\">", $_['Imsak'], '</span></p>';
        } ?>
        <p><?php echo $l->t('Sunrise'); ?>:  &emsp;&emsp;&emsp;<span id="sunrise"><?php echo $_['Sunrise']; ?></span></p>
        <p><?php echo $l->t('Sunset'); ?>:  &emsp;&emsp; &emsp;<span id="sunset"><?php echo $_['Sunset']; ?></span></p>
        <?php if ($_['Moonrise']) {
        	echo "<p>", $l->t('Moonrise'), ":  &emsp; &emsp;<span id=\"moonrise\">", $_['Moonrise'], "</span></p>";
        } ?>
        <?php if ($_['Moonset']) {
        	echo "<p>", $l->t('Moonset'), ":  &emsp; &emsp;<span id=\"moonset\">", $_['Moonset'], "</span></p>";
        } ?>
    </section>
  </div>
  <div id="infos" class="viewcontainer">
    <div id="compass">
      <img src="<?php echo $_['rurl'], "kiblaibra.png"; ?>" id="kiblaibra" style="<?php echo "transform: rotate(", $_['QiblaDirection'], "deg\");"; ?>">
      <?php if ((isset($_['SunAltitude'])) && ($_['SunAltitude'] > 0)) {
      	echo "
      <img src=\"",  $_['rurl'], "sunibra.png\" id=\"sunibra\" style=\"transform: rotate(", $_['SunAzimuth'], "deg);position: absolute;top: 0;left: 0;\">";
      }
	  if ((isset($_['MoonAltitude'])) && ($_['MoonAltitude'] > 0) && ($_['IlluminatedFraction'] > 1)) {
	  	echo "
      <img src=\"",  $_['rurl'], "moonibra.png\" id=\"moonibra\" style=\"transform: rotate(", $_['MoonAzimuth'], "deg);position: absolute;top: 0;left: 0;\">";
	  } ?>
    </div>

    <!-- Moon and Sun Section -->
    <section id="moon-sun">
        <h2><?php echo $l->t('Moon and Sun Information'); ?></h2>
        <p><?php echo $l->t('Moon\'s illuminated fraction'); ?>: &emsp;<span id="moon-illuminated-fraction" style="float:right;"><?php echo $_['IlluminatedFraction']; ?>%</span></p>
        <p><?php echo $l->t('Moon Phase'); ?>: &emsp;<span id="moon-phase" style="float:right;"><?php echo $_['MoonPhase']; ?></span></p>
        <p><?php echo $l->t('Moon\'s ecliptic phase angle'); ?>: &emsp;<span id="moon-phase-angle" style="float:right;"><?php echo $_['MoonPhaseAngle']; ?> <?php echo $l->t('degrees'); ?></span></p>
        <p><?php echo $l->t('New moon'); ?>: <span id="new-moon" style="float:right;"><?php echo $_['NewMoon']; ?></span></p>
        <p><?php echo $l->t('Next new moon'); ?>: <span id="new-moon" style="float:right;"><?php echo $_['NextNewMoon']; ?></span></p>
        <p><?php echo $l->t('Sun Position'), "<p> &emsp; ", $l->t('Azimuth'); ?>: &emsp;<span id="sun-azimuth" style="float:right;"><?php echo $_['SunAzimuth']; ?></span></p><p> &emsp;  <?php echo $l->t('Altitude'); ?>: &emsp;<span id="sun-altitude" style="float:right;"><?php echo $_['SunAltitude']; ?></span></p></p>
        <p><?php echo $l->t('Moon Position'), "<p> &emsp; ", $l->t('Azimuth'); ?>: &emsp;<span id="moon-azimuth" style="float:right;"><?php echo $_['MoonAzimuth']; ?></span></p><p> &emsp; <?php echo $l->t('Altitude'); ?>: &emsp;<span id="moon-altitude" style="float:right;"><?php echo $_['MoonAltitude']; ?></span></p></p>
        <p><?php echo $l->t('Qibla direction'), " ", $l->t('Azimuth'); ?>: &emsp;<span id="qibla-direction" style="float:right;"><?php echo $_['QiblaDirection']; ?></span></p>
    </section>
  </div>
</div>
