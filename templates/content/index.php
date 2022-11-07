<?php

echo "<div id=\"main-content-div\"><div id=\"prayertime\" class=\"viewcontainer\"><h2 style=\"font-family:Arial;\">", $_['Hijri'], '</h2>';

if ($_['SpecialDay'])
    echo $l->t('Today is'), ": &emsp; <b>", $_['SpecialDay'], '</b><br>';

echo $l->t('City') . ": " . $_['City'] . '<br>';

echo $l->t('Next') . ": <b>" . $l->t($_['Salat']) . "</b> " . $l->t('after') . ": <b>" . $_['Remain'] . '</b><br>';

echo $l->t('Day length') . ": &emsp;", $_['DayLength'], '<br>';

if ( $_['Imsak'] != "") //Ramadhane
    echo $l->t('Imsak') . ":&emsp;&emsp;", $_['Imsak'], '<br>';

echo $l->t('Sunrise'), ": &emsp;", $_['Sunrise'], '<br>';
echo $l->t('Sunset'), ": &emsp; ", $_['Sunset'], '<br>';

if ($_['Moonrise'])
    echo $l->t('Moonrise'), ": &emsp; ", $_['Moonrise'], '<br>';
if ($_['Moonset'])
    echo $l->t('Moonset'), ": &emsp; ", $_['Moonset'], '<br>';

$gback = [
            'Fajr' => "",
            'Dhuhr' => "",
            'Asr' => "",
            'Maghrib' => "",
            'Isha' => "",
        ];
$gback[$_['Salat']]=" style=\"background: gray;\"";

echo "<br><table id=\"salat\">
<thead>
<tr>
<th>", $l->t('Salat') ,"</th>
<th>", $l->t('Time') ,"</th>
</tr>
</thead>
<tbody>
<tr>
<td scope=\"row\"", $gback['Fajr'], ">", $l->t('Fajr'), ":</td>
<td", $gback['Fajr'], ">", $_['Fajr'], "</td>
</tr>";

if (isset($_['Jumaa']))  //Juma'a
    echo "<tr><td scope=\"row\">", $l->t('Juma\'a:'), "</td><td>", $_['Dhuhr'], "</td></tr>";

echo "<tr>
<td scope=\"row\"", $gback['Dhuhr'], ">", $l->t('Dhuhr'), ":</td>
<td", $gback['Dhuhr'], ">", $_['Dhuhr'], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback['Asr'], ">", $l->t('Asr'), ":</td>
<td", $gback['Asr'], ">", $_['Asr'], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback['Maghrib'], ">", $l->t('Maghrib'), ":&nbsp;&nbsp;</td>
<td", $gback['Maghrib'], ">", $_['Maghrib'], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback['Isha'], ">", $l->t('Isha'), ":</td>
<td", $gback['Isha'], ">", $_['Isha'], "</td>
</tr>
</tbody>
</table>
</div>
<div id=\"infos\" class=\"viewcontainer\">
<div id=\"compass\">
<img src=\"",  $_['rurl'], "kiblaibra.png\" id=\"kiblaibra\" style=\"transform: rotate(", $_['QiblaDirection'], "deg);\">";
if ((isset($_['SunAltitude'])) && ($_['SunAltitude'] > 0))
    echo "<img src=\"",  $_['rurl'], "sunibra.png\" id=\"sunibra\" style=\"transform: rotate(", $_['SunAzimuth'], "deg);position: absolute;top: 0;left: 0;\">";
if ((isset($_['MoonAltitude'])) && ($_['MoonAltitude'] > 0) && ($_['IlluminatedFraction'] > 1))
    echo "<img src=\"",  $_['rurl'], "moonibra.png\" id=\"moonibra\" style=\"transform: rotate(", $_['MoonAzimuth'], "deg);position: absolute;top: 0;left: 0;\">";

echo '</div><br>';
if (isset($_['IlluminatedFraction']))
    echo $l->t('Moon\'s illuminated fraction'), ": &emsp; ", $_['IlluminatedFraction'], '%<br>';
if (isset($_['MoonPhase']))
    echo $l->t('Moon Phase'), ": &emsp; ", $_['MoonPhase'], '<br>';
if (isset($_['MoonPhaseAngle'])) {
    echo $l->t('Moon\'s ecliptic phase angle'), ": ", $_['MoonPhaseAngle'], " ", $l->t('degrees'), '<br>';
    echo $l->t('Sun Position'), " ", $l->t('Azimuth'), ": ", $_['SunAzimuth'], ", ", $l->t('Altitude'), ": ", $_['SunAltitude'], '<br>';
    echo $l->t('Moon Position'), " ", $l->t('Azimuth'), ": ", $_['MoonAzimuth'], ", ", $l->t('Altitude'), ": ", $_['MoonAltitude'], '<br>';
}

echo $l->t('Qibla direction'), " ", $l->t('Azimuth'), ": ", $_['QiblaDirection'], "</div></div>";
?>
