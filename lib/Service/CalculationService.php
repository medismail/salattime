<?php
namespace OCA\SalatTime\Service;

require_once __DIR__ . '/../IslamicNetwork/PrayerTimes/PrayerTimes.php';
require_once __DIR__ . '/../IslamicNetwork/PrayerTimes/Method.php';
require_once __DIR__ . '/../IslamicNetwork/PrayerTimes/DMath.php';
require_once __DIR__ . '/../IslamicNetwork/MoonSighting/PrayerTimes.php';
require_once __DIR__ . '/../IslamicNetwork/MoonSighting/Isha.php';
require_once __DIR__ . '/../IslamicNetwork/hijri/hijri_date.php';
require_once __DIR__ . '/../IslamicNetwork/hijri/suncalc.php';

require_once __DIR__ . '/../Service/ConfigService.php';

use OCA\SalatTime\IslamicNetwork\PrayerTimes\PrayerTimes;
use OCA\SalatTime\IslamicNetwork\hijri\HijriDate;
use OCA\SalatTime\IslamicNetwork\hijri\SunCalc;
use OCA\salattime\Service\ConfigService;
use DateTime;
use DateTimezone;

class CalculationService {

    private $configService;

    public function __construct(
            ConfigService $configService
            ) {

            $this->configService = $configService;
    }

    public function getPrayerTimes() {

        $p_settings = $this->configService->getSettingsValue($this->userId);
        $adjustments = $this->configService->getAdjustmentsValue($this->userId);
        // Instantiate the class with your chosen method, Juristic School for Asr and if you want or own Asr factor, make the juristic school null and pass your own Asr shadow factor as the third parameter. Note that all parameters are optional.

        if ($p_settings['latitude'] != "")
            $latitude = $p_settings['latitude'];
        else
            $latitude = 21.3890824;

        if ($p_settings['longitude'] != "")
            $longitude = $p_settings['longitude'];
        else
            $longitude = 39.8579118;

        if ($p_settings['timezone'] != "")
            $timezone = $p_settings['timezone'];
        else
            $timezone = '+0300';

        if ($p_settings['elevation'] != "")
            $elevation = $p_settings['elevation'];
        else
            $elevation = null;

        if ($p_settings['method'] != "")
            $method = $p_settings['method'];
        else
            $method = 'MWL';

        $pt = new PrayerTimes($method); // new PrayerTimes($method, $asrJuristicMethod, $asrShadowFactor);

        $pt->tune($imsak = 0, $fajr = $adjustments['Fajr'], $sunrise = 0, $dhuhr = $adjustments['Dhuhr'], $asr = $adjustments['Asr'], $maghrib = $adjustments['Maghrib'], $sunset = 0, $isha = $adjustments['Isha'], $midnight = 0);
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
        if ($adjustments['day'] != "")
            $hijri->tune($adjustments['day']);

        $times['Hijri'] = $hijri->get_date();
        //$next[PrayerTimes::SALAT]
        //$next[PrayerTimes::REMAIN]
        //$pt->getDayLength($times[PrayerTimes::SUNRISE], $times[PrayerTimes::SUNSET])
        //if ( $hijri->get_month() == 9) //Ramadhane
        //$times[PrayerTimes::IMSAK]

        return $times;
    }

    public function getSunCalc() {
        $p_settings = $this->configService->getSettingsValue($this->userId);
        $date = new DateTime(null, new DateTimezone($p_settings['timezone']));
        $sc = new SunCalc($date, $p_settings['latitude'], $p_settings['longitude']);
        //$sunTimes = $sc->getSunTimes();
        $moonTimes = $sc->getMoonTimes();
        //if ($moonTimes['moonrise'])
        //if ($moonTimes['moonset'])
        return $moonTimes;
    }

    public function gretNames() {
        return [
            'IMSAK' => PrayerTimes::IMSAK,
            'FAJR' => PrayerTimes::FAJR,
            'SUNRISE' => PrayerTimes::SUNRISE,
            'ZHUHR' => PrayerTimes::ZHUHR,
            'ASR' => PrayerTimes::ASR,
            'SUNSET' => PrayerTimes::SUNSET,
            'MAGHRIB' => PrayerTimes::MAGHRIB,
            'ISHA' => PrayerTimes::ISHA,
            'MIDNIGHT' => PrayerTimes::MIDNIGHT,
            'SALAT' => PrayerTimes::SALAT,
            'REMAIN' => PrayerTimes::REMAIN,
            'HIJRI' => 'Hijri'
            ];
    }
}
