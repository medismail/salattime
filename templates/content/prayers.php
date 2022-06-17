<?php

require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/PrayerTimes.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/Method.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/PrayerTimes/DMath.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/MoonSighting/PrayerTimes.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/MoonSighting/Isha.php';
require_once __DIR__ . '/../../lib/IslamicNetwork/hijri/hijri_date.php';

use OCA\SalatTime\IslamicNetwork\PrayerTimes\PrayerTimes;
use OCA\SalatTime\IslamicNetwork\hijri\HijriDate;

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
if ($_['format_12_24'] != "")
    $format_12_24  = $_['format_12_24'];
else
    $format_12_24 = $PrayerTimes::TIME_FORMAT_12H;

$pt = new PrayerTimes($method); // new PrayerTimes($method, $asrJuristicMethod, $asrShadowFactor);

$pt->tune($imsak = 0, $fajr = $_['Fajr'], $sunrise = 0, $dhuhr = $_['Dhuhr'], $asr = $_['Asr'], $maghrib = $_['Maghrib'], $sunset = 0, $isha = $_['Isha'], $midnight = 0);

$date = new DateTime(null, new DateTimezone($timezone));
$curtime = strtotime($date->format('d-m-Y H:i:s'));
$hijri = new HijriDate($curtime);
if ($_['day'] != "")
   $hijri->tune($_['day']);

echo "<div id=\"prayertime\" class=\"viewcontainer\"><h2 style=\"font-family:Arial;\">", $hijri->get_date(), '</h2>';

//echo "Sunrise: &emsp;", $times['Sunrise'], '<br>';

echo "<br><table id=\"salat\">
<thead>
<tr>
<th>Day</th>";
if ( $hijri->get_month() == 9) //Ramadhane
    echo "<th>Imsak</th>";
echo "<th>Fajr</th>
<th>Sunrise</th>
<th>Dhuhr</th>
<th>Asr</th>
<th>Maghrib</th>
<th>Isha</th>
</tr>
</thead>
<tbody>";

$start_date = new DateTime('today -3 day', new DateTimezone($timezone)); //date_create("2022-03-17");
$end_date   = new DateTime('today +12 day', new DateTimezone($timezone)); //date_create("2022-03-29"); // If you want to include this date, add 1 day

$interval = DateInterval::createFromDateString('1 day');
$daterange = new DatePeriod($start_date, $interval ,$end_date);

foreach($daterange as $date1){
   //$date1->setTimezone(new DateTimezone($timezone));
   $times = $pt->getTimes($date1, $latitude, $longitude, $elevation, $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $format_12_24);
   $curtime = strtotime($date1->format('d-m-Y H:i:s'));
   $hijri = new HijriDate($curtime);
   if ($_['day'] != "")
       $hijri->tune($_['day']);
   echo "<tr><td scope=\"row\">", $hijri->get_year(), "-", $hijri->get_month(), "-", $hijri->get_day(), "</td>";
   if ( $hijri->get_month() == 9)
       echo "<td>", $times['Imsak'], "</td>";
   echo "<td>", $times['Fajr'], "</td>
         <td>", $times['Sunrise'], "</td>
         <td>", $times['Dhuhr'], "</td>
         <td>", $times['Asr'], "</td>
         <td>", $times['Maghrib'], "</td>
         <td>", $times['Isha'], "</td></tr>";
}
echo "</tbody>
</table>
</div>";

//echo "latitude: ", $_['latitude'], " ",$_['longitude'], " ",$_['timezone'], " ",$_['day'];
//echo "Date", $date->format('d-m-Y H:i:s');

?>
