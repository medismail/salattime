<?php

require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/PrayerTimes.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/Method.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/DMath.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/MoonSighting/PrayerTimes.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/MoonSighting/Isha.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/hijri/hijri_date.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/hijri/suncalc.php';

use OCA\SalatTime\IslamicNetwork\PrayerTimes\PrayerTimes;
use OCA\SalatTime\IslamicNetwork\hijri\HijriDate;
use OCA\SalatTime\IslamicNetwork\hijri\SunCalc;

// Instantiate the class with your chosen method, Juristic School for Asr and if you want or own Asr factor, make the juristic school null and pass your own Asr shadow factor as the third parameter. Note that all parameters are optional.

if ($_['latitude'] != "")
    $latitude = $_['latitude'];
else
    $latitude = 21.3890824;
if ($_['longitude'] != "")
    $longitude = $_['longitude'];
else
    $longitude = 39.8579118;
if ($_['timezone'] != "")
    $timezone = $_['timezone'];
else
    $timezone = '+0300';
if ($_['elevation'] != "")
    $elevation = $_['elevation'];
else
    $elevation = null;
if ($_['method'] != "")
    $method = $_['method'];
else
    $method = 'MWL';

$pt = new PrayerTimes($method); // new PrayerTimes($method, $asrJuristicMethod, $asrShadowFactor);

$pt->tune($imsak = 0, $fajr = $_['Fajr'], $sunrise = 0, $dhuhr = $_['Dhuhr'], $asr = $_['Asr'], $maghrib = $_['Maghrib'], $sunset = 0, $isha = $_['Isha'], $midnight = 0);
// Then, to get times for today.
$times = $pt->getTimesForToday($latitude, $longitude, $timezone, $elevation, $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $format = PrayerTimes::TIME_FORMAT_12H);

$next = $pt->getNextPrayer();

$date = new DateTime(null, new DateTimezone($timezone));
$curtime = strtotime($date->format('d-m-Y H:i:s'));
if (($next[PrayerTimes::SALAT] == PrayerTimes::FAJR)&&($date->format('H') > 12)) {
    $nextday = new DateTime('today +1 day', new DateTimezone($timezone));
    $times = $pt->getTimes($nextday, $latitude, $longitude, $elevation, $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $format = PrayerTimes::TIME_FORMAT_12H);
    $next = $pt->getNextPrayerFromDate($date, PrayerTimes::FAJR);
    $curtime = strtotime($nextday->format('d-m-Y H:i:s'));
    $date = $nextday;
}

$hijri = new HijriDate($curtime);
if ($_['day'] != "")
   $hijri->tune($_['day']);

echo "<div id=\"prayertime\" class=\"viewcontainer\"><h2 style=\"font-family:Arial;\">", $hijri->get_date(), '</h2>';

echo "Next: <b>" . $next[PrayerTimes::SALAT] . "</b> after: <b>" . $next[PrayerTimes::REMAIN] . '</b><br>';

echo "Day length: &emsp;", $pt->getDayLength($times[PrayerTimes::SUNRISE], $times[PrayerTimes::SUNSET]), '<br>';

if ( $hijri->get_month() == 9) //Ramadhane
    echo "Imsak:&emsp;&emsp;", $times[PrayerTimes::IMSAK], '<br>';

echo "Sunrise: &emsp;", $times[PrayerTimes::SUNRISE], '<br>';
echo "Sunset: &emsp; ", $times[PrayerTimes::SUNSET], '<br>';

$sc = new SunCalc($date, $latitude, $longitude);
/*$sunTimes = $sc->getSunTimes();
echo "Sunrise: &emsp;", $sunTimes['sunrise']->format('G:i'), '<br>';
echo "Sunset: &emsp; ", $sunTimes['sunset']->format('G:i'), '<br>'*/;

$moonTimes = $sc->getMoonTimes();
if ($moonTimes['moonrise'])
    echo "Moonrise: &emsp; ", $moonTimes['moonrise']->format('g:i a'), '<br>';
if ($moonTimes['moonset'])
    echo "Moonset: &emsp; ", $moonTimes['moonset']->format('g:i a'), '<br>';


$gback = [
            PrayerTimes::FAJR => "",
            PrayerTimes::ZHUHR => "",
            PrayerTimes::ASR => "",
            PrayerTimes::MAGHRIB => "",
            PrayerTimes::ISHA => "",
        ];
$gback[$next[PrayerTimes::SALAT]]=" style=\"background: gray;\"";

echo "<br><table id=\"salat\">
<thead>
<tr>
<th>Salat</th>
<th>Time</th>
</tr>
</thead>
<tbody>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::FAJR], ">", PrayerTimes::FAJR, ":</td>
<td", $gback[PrayerTimes::FAJR], ">", $times[PrayerTimes::FAJR], "</td>
</tr>
<tr>";
if ( date('N',$curtime) == 5)  //Juma'a
    echo "<td scope=\"row\">Juma'a:</td>
<td>", $times[PrayerTimes::ZHUHR], "</td>
</tr>
<tr>";
echo "<td scope=\"row\"", $gback[PrayerTimes::ZHUHR], ">", PrayerTimes::ZHUHR, ":</td>
<td", $gback[PrayerTimes::ZHUHR], ">", $times[PrayerTimes::ZHUHR], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::ASR], ">", PrayerTimes::ASR, ":</td>
<td", $gback[PrayerTimes::ASR], ">", $times[PrayerTimes::ASR], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::MAGHRIB], ">", PrayerTimes::MAGHRIB, ":&nbsp;&nbsp;</td>
<td", $gback[PrayerTimes::MAGHRIB], ">", $times[PrayerTimes::MAGHRIB], "</td>
</tr>
<tr>
<td scope=\"row\"", $gback[PrayerTimes::ISHA], ">", PrayerTimes::ISHA, ":</td>
<td", $gback[PrayerTimes::ISHA], ">", $times[PrayerTimes::ISHA], "</td>
</tr>
</tbody>
</table>
</div>";

//echo "latitude: ", $_['latitude'], " ",$_['longitude'], " ",$_['timezone'], " ",$_['day'];
//echo "Date", $date->format('d-m-Y H:i:s');

?>
