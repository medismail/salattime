<?php

require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/PrayerTimes.php';
use OCA\SalatTime\IslamicNetwork\PrayerTimes\PrayerTimes;

echo "<div id=\"main-content-div\"><div id=\"prayertime\" class=\"viewcontainer\"><h2 style=\"font-family:Arial;\">", $_['Hijri'], '</h2>';

echo $l->t('Next') . ": <b>" . $_[PrayerTimes::SALAT] . "</b> " . $l->t('after') . ": <b>" . $_[PrayerTimes::REMAIN] . '</b><br>';

echo $l->t('Day length') . ": &emsp;", $_['DayLength'], '<br>';

if ( $_[PrayerTimes::IMSAK] != "") //Ramadhane
    echo $l->t('Imsak') . ":&emsp;&emsp;", $_[PrayerTimes::IMSAK], '<br>';

echo $l->t('Sunrise'), ": &emsp;", $_[PrayerTimes::SUNRISE], '<br>';
echo $l->t('Sunset'), ": &emsp; ", $_[PrayerTimes::SUNSET], '<br>';

if ($_['Moonrise'])
    echo $l->t('Moonrise'), ": &emsp; ", $_['Moonrise'], '<br>';
if ($_['Moonset'])
    echo $l->t('Moonset'), ": &emsp; ", $_['Moonset'], '<br>';

$gback = [
            PrayerTimes::FAJR => "",
            PrayerTimes::ZHUHR => "",
            PrayerTimes::ASR => "",
            PrayerTimes::MAGHRIB => "",
            PrayerTimes::ISHA => "",
        ];
$gback[$_[PrayerTimes::SALAT]]=" style=\"background: gray;\"";

echo "<br><table id=\"salat\">
<thead>
<tr>
<th>", $l->t('Salat') ,"</th>
<th>", $l->t('Time') ,"</th>
</tr>
</thead>
<tbody>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::FAJR], ">", $l->t(PrayerTimes::FAJR), ":</td>
<td", $gback[PrayerTimes::FAJR], ">", $_[PrayerTimes::FAJR], "</td>
</tr>";

if (isset($_['Jumaa']))  //Juma'a
    echo "<tr><td scope=\"row\">", $l->t('Juma\'a:'), "</td><td>", $_[PrayerTimes::ZHUHR], "</td></tr>";

echo "<tr>
<td scope=\"row\"", $gback[PrayerTimes::ZHUHR], ">", $l->t(PrayerTimes::ZHUHR), ":</td>
<td", $gback[PrayerTimes::ZHUHR], ">", $_[PrayerTimes::ZHUHR], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::ASR], ">", $l->t(PrayerTimes::ASR), ":</td>
<td", $gback[PrayerTimes::ASR], ">", $_[PrayerTimes::ASR], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::MAGHRIB], ">", $l->t(PrayerTimes::MAGHRIB), ":&nbsp;&nbsp;</td>
<td", $gback[PrayerTimes::MAGHRIB], ">", $_[PrayerTimes::MAGHRIB], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::ISHA], ">", $l->t(PrayerTimes::ISHA), ":</td>
<td", $gback[PrayerTimes::ISHA], ">", $_[PrayerTimes::ISHA], "</td>
</tr>
</tbody>
</table>
</div>
<div id=\"infos\" class=\"viewcontainer\">
<div id=\"compass\">
<img src=\"img/kiblaibra.png\" id=\"kiblaibra\" style=\"transform: rotate(", $_['QiblaDirection'], "deg);\">";
if ((isset($_['SunAltitude'])) && ($_['SunAltitude'] > 0))
    echo "<img src=\"img/sunibra.png\" id=\"sunibra\" style=\"transform: rotate(", $_['SunAzimuth'], "deg);position: absolute;top: 0;left: 0;\">";
if ((isset($_['MoonAltitude'])) && ($_['MoonAltitude'] > 0) && ($_['IlluminatedFraction'] > 1))
    echo "<img src=\"img/moonibra.png\" id=\"moonibra\" style=\"transform: rotate(", $_['MoonAzimuth'], "deg);position: absolute;top: 0;left: 0;\">";

echo '</div><br>';
if (isset($_['IlluminatedFraction']))
    echo $l->t('Moon\'s illuminated fraction'), ": ", $_['IlluminatedFraction'], '%<br>';
if (isset($_['MoonPhase']))
    echo $l->t('Moon Phase'), ": &emsp; ", $_['MoonPhase'], '<br>';
if (isset($_['MoonPhaseAngle'])) {
    echo $l->t('Moon\'s ecliptic phase angle'), ": ", $_['MoonPhaseAngle'], " ", $l->t('degrees'), '<br>';
    echo $l->t('Sun Position '), " ", $l->t('Azimuth'), ": ", $_['SunAzimuth'], ", ", $l->t('Altitude'), ": ", $_['SunAltitude'], '<br>';
    echo $l->t('Moon Position '), " ", $l->t('Azimuth'), ": ", $_['MoonAzimuth'], ", ", $l->t('Altitude'), ": ", $_['MoonAltitude'], '<br>';
}

echo $l->t('Qibla direction'), " ", $l->t('Azimuth'), ": ", $_['QiblaDirection'], "</div></div>";
?>
