<?php

/**
 *
 * Salat Time APP (Nextcloud)
 *
 * @author Mohamed-Ismail MEJRI <imejri@hotmail.com>
 *
 * @copyright Copyright (c) 2024 Mohamed-Ismail MEJRI
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\SalatTime\Service;

require_once __DIR__ . '/../IslamicNetwork/PrayerTimes/PrayerTimes.php';
require_once __DIR__ . '/../IslamicNetwork/PrayerTimes/Method.php';
require_once __DIR__ . '/../IslamicNetwork/PrayerTimes/DMath.php';
require_once __DIR__ . '/../IslamicNetwork/MoonSighting/PrayerTimes.php';
require_once __DIR__ . '/../IslamicNetwork/MoonSighting/Isha.php';
require_once __DIR__ . '/../IslamicNetwork/Hijri/HijriDate.php';
require_once __DIR__ . '/../IslamicNetwork/SunMoonCalc/SunCalc.php';
require_once __DIR__ . '/../IslamicNetwork/QiblaDirection/Calculation.php';
require_once __DIR__ . '/../Tools/Helper.php';
require_once __DIR__ . '/../Service/ConfigService.php';

use OCA\SalatTime\IslamicNetwork\PrayerTimes\PrayerTimes;
use OCA\SalatTime\IslamicNetwork\Hijri\HijriDate;
use OCA\SalatTime\IslamicNetwork\SunMoonCalc\SunCalc;
use OCA\SalatTime\IslamicNetwork\QiblaDirection\Calculation;
use OCA\SalatTime\Tools\Helper;
use OCA\SalatTime\AppInfo\Application;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IUserManager;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IClient;
use OCP\ICacheFactory;
use OCP\ICache;
use OCP\IL10N;
use DateTime;
use DateTimezone;
use DateInterval;
use DatePeriod;

class CalculationService {
	/** @var IMSAK name */
	public const IMSAK = PrayerTimes::IMSAK;

	/** @var FAJR name */
	public const FAJR = PrayerTimes::FAJR;

	/** @var SUNRISE name */
	public const SUNRISE = PrayerTimes::SUNRISE;

	/** @var ZHUHR name */
	public const ZHUHR = PrayerTimes::ZHUHR;

	/** @var ASR name */
	public const ASR = PrayerTimes::ASR;

	/** @var SUNSET name */
	public const SUNSET = PrayerTimes::SUNSET;

	/** @var MAGHRIB name */
	public const MAGHRIB = PrayerTimes::MAGHRIB;

	/** @var ISHA name */
	public const ISHA = PrayerTimes::ISHA;

	/** @var MOONRISE name */
	public const MOONRISE = PrayerTimes::MOONRISE;

	/** @var MOONSET name */
	public const MOONSET = PrayerTimes::MOONSET;

	/** @const TIME_FORMAT_12H */
	public const TIME_FORMAT_12H = PrayerTimes::TIME_FORMAT_12H;

	/** @var ConfigService */
	private $configService;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IClientService */
	private $clientService;

	/** @var IClient */
	private $client;

	/** @var ICache */
	private $cache;

	/** @var IL10N */
	private $l10n;

	public function __construct(
					   ConfigService $configService,
					   IAccountManager $accountManager,
					   IUserManager $userManager,
					   IClientService $clientService,
					   ICacheFactory $cacheFactory,
					   IL10N $l
				   ) {
		$this->configService = $configService;
		$this->accountManager = $accountManager;
		$this->userManager = $userManager;
		$this->clientService = $clientService;
		$this->client = $clientService->newClient();
		$this->cache = $cacheFactory->createDistributed('salattime');
		$this->l10n = $l;
	}

	/**
	 * get Prayers times and hijri date
	 *
	 * @param string UserId
	 * @return array Full paryers times and hijri date
	 */
	public function getPrayerTimes(string $userId): array {
		$p_settings = $this->configService->getSettingsValue($userId);
		$adjustments = $this->configService->getAdjustmentsValue($userId);
		// Instantiate the class with your chosen method, Juristic School for Asr and if you want or own Asr factor, make the juristic school null and pass your own Asr shadow factor as the third parameter. Note that all parameters are optional.

		$pt = new PrayerTimes($p_settings['method']); // new PrayerTimes($method, $asrJuristicMethod, $asrShadowFactor);

		$pt->tune($imsak = 0, $fajr = $adjustments['Fajr'], $sunrise = 0, $dhuhr = $adjustments['Dhuhr'], $asr = $adjustments['Asr'], $maghrib = $adjustments['Maghrib'], $sunset = 0, $isha = $adjustments['Isha'], $midnight = 0);
		// Then, to get times for today.
		$times = $pt->getTimesForToday($p_settings['latitude'], $p_settings['longitude'], $p_settings['timezone'], $p_settings['elevation'], $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $p_settings['format_12_24']);

		$next = $pt->getNextPrayer($times);
		$times['DayOffset'] = 0;
		$date = new DateTime('', new DateTimezone($p_settings['timezone']));
		$curtime = strtotime($date->format('d-m-Y H:i:s'));
		if (($next[PrayerTimes::SALAT] == PrayerTimes::FAJR) && ($date->format('H') > 12)) {
			$nextday = new DateTime('today +1 day', new DateTimezone($p_settings['timezone']));
			$times = $pt->getTimes($nextday, $p_settings['latitude'], $p_settings['longitude'], $p_settings['elevation'], $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $p_settings['format_12_24']);
			$next = $pt->getNextPrayerFromDate($date, $times, PrayerTimes::FAJR);
			$curtime = strtotime($nextday->format('d-m-Y H:i:s'));
			$date = $nextday;
			$times['DayOffset'] = 90000;
		}

		$hijri = new HijriDate($curtime, $this->l10n);
		if ($adjustments['day'] != "") {
			if ($adjustments['nma'] == '15') {
				$hijri->tune($adjustments['day'], '0');
			} else {
				$hijri->tune($adjustments['day'], $adjustments['nma']);
			}
		}

		$times['Hijri'] = $hijri->get_day_name() . ' ' . $hijri->get_day() . ' ' . $hijri->get_month_name() . ' ' . $hijri->get_year() . $this->l10n->t('H');
		$times[PrayerTimes::SALAT] = $next[PrayerTimes::SALAT];
		$times[PrayerTimes::REMAIN] = $next[PrayerTimes::REMAIN];
		$times['DayLength'] = $this->getDayLength($times[PrayerTimes::SUNRISE], $times[PrayerTimes::SUNSET]);
		$times['SpecialDay'] = implode(" ", $hijri->get_day_special_name());
		if (date('N', $curtime) == 5) {
			$times['Jumaa'] = "Juma'a";
		}
		if ($hijri->get_month() != 9) { //Ramadhane
			$times[PrayerTimes::IMSAK] = "";
		}
		if ($p_settings['city'] != "") {
			$times['City'] = $p_settings['city'];
		} else {
			$times['City'] = $this->getNameFromGeo($p_settings['latitude'], $p_settings['longitude']);
			if ($times['City'] != "") {
				$this->configService->setCityValue($userId, $times['City']);
			} else {
				$times['City'] = $this->l10n->t('Unknown city');
			}
		}

		return $times;
	}

	/**
	 * get Sun and Moon informations
	 *
	 * @param string UserId
	 * @return array sun and moons informations
	 */
	public function getSunMoonCalc(string $userId, int $dayoffset = 0): array {
		$p_settings = $this->configService->getSettingsValue($userId);
		if (!$p_settings['elevation']) {
			$p_settings['elevation'] = 0.0;
		}
		if ($p_settings['format_12_24'] == PrayerTimes::TIME_FORMAT_12H) {
			$textFormat_12_24 = 'g:i a';
		} else {
			$textFormat_12_24 = 'G:i';
		}

		$udtz = new DateTimezone($p_settings['timezone']);
		$date = new DateTime('', $udtz);
		if (Helper::pythonInstalled()) {
			$mphase = [
				0 => $this->l10n->t('New Moon'),
				1 => $this->l10n->t('Waxing Crescent Moon'),
				2 => $this->l10n->t('Waxing Crescent Moon'),
				3 => $this->l10n->t('Waxing Crescent Moon'),
				4 => $this->l10n->t('First Quarter Moon'),
				5 => $this->l10n->t('Waxing Gibbous Moon'),
				6 => $this->l10n->t('Waxing Gibbous Moon'),
				7 => $this->l10n->t('Waxing Gibbous Moon'),
				8 => $this->l10n->t('Full Moon'),
				9 => $this->l10n->t('Waning Gibbous Moon'),
				10 => $this->l10n->t('Waning Gibbous Moon'),
				11 => $this->l10n->t('Waning Gibbous Moon'),
				12 => $this->l10n->t('Third Quarter Moon'),
				13 => $this->l10n->t('Waning Crescent Moon'),
				14 => $this->l10n->t('Waning Crescent Moon'),
				15 => $this->l10n->t('Waning Crescent Moon'),
				16 => $this->l10n->t('New Moon')
			];
			$output = null;
			$retval = null;
			exec('python3 ' . __DIR__ . '/../bin/salattime.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $udtz->getOffset($date) + $dayoffset, $output, $retval);
			$sunMoonTimes['Sunrise'] = $this->timeConversion($output[1], $udtz, $textFormat_12_24);
			$sunMoonTimes['Sunset'] = $this->timeConversion($output[2], $udtz, $textFormat_12_24);
			$sunMoonTimes['Moonrise'] = $this->timeConversion($output[3], $udtz, $textFormat_12_24);
			$sunMoonTimes['Moonset'] = $this->timeConversion($output[4], $udtz, $textFormat_12_24);
			$sunMoonTimes['MoonPhase'] = $mphase[(int)($output[5] * 10 / 225)];
			$sunMoonTimes['MoonPhaseAngle'] = $output[5];
			$sunMoonTimes['IlluminatedFraction'] = $output[6];
			$sunMoonTimes['SunAzimuth'] = $output[7];
			$sunMoonTimes['SunAltitude'] = $output[8];
			$sunMoonTimes['MoonAzimuth'] = $output[9];
			$sunMoonTimes['MoonAltitude'] = $output[10];
			$sunMoonTimes['NewMoon'] = $this->timeConversion($output[11], $udtz, 'Y-m-d ' . $textFormat_12_24);
			$sunMoonTimes['NextNewMoon'] = $this->timeConversion($output[12], $udtz, 'Y-m-d ' . $textFormat_12_24);
		} else {
			if ($dayoffset) {
				$date = new DateTime('today +1 day', $udtz);
			}
			$sc = new SunCalc($date, $p_settings['latitude'], $p_settings['longitude']);
			//$sunTimes = $sc->getSunTimes();
			$moonTimes = $sc->getMoonTimes();
			if ($moonTimes['moonrise']) {
				$sunMoonTimes['Moonrise'] = $moonTimes['moonrise']->format($textFormat_12_24);
			} else {
				$sunMoonTimes['Moonrise'] = "";
			}
			if ($moonTimes['moonset']) {
				$sunMoonTimes['Moonset'] = $moonTimes['moonset']->format($textFormat_12_24);
			} else {
				$sunMoonTimes['Moonset'] = "";
			}
			$moonIl = $sc->getMoonIllumination();
			$sunMoonTimes['MoonPhase'] = number_format($moonIl['phase'] * 100, 1);
			$sunMoonTimes['IlluminatedFraction'] = number_format($moonIl['fraction'] * 100, 1);
		}
		$sunMoonTimes['QiblaDirection'] = Calculation::get($p_settings['latitude'], $p_settings['longitude']);
		return $sunMoonTimes;
	}

	public function gretNames(): array {
		return [
			'IMSAK' => $this->l10n->t(PrayerTimes::IMSAK),
			'FAJR' => $this->l10n->t(PrayerTimes::FAJR),
			'SUNRISE' => $this->l10n->t(PrayerTimes::SUNRISE),
			'ZHUHR' => $this->l10n->t(PrayerTimes::ZHUHR),
			'ASR' => $this->l10n->t(PrayerTimes::ASR),
			'SUNSET' => $this->l10n->t(PrayerTimes::SUNSET),
			'MAGHRIB' => $this->l10n->t(PrayerTimes::MAGHRIB),
			'ISHA' => $this->l10n->t(PrayerTimes::ISHA),
			'MIDNIGHT' => $this->l10n->t(PrayerTimes::MIDNIGHT),
			'SALAT' => PrayerTimes::SALAT,
			'REMAIN' => PrayerTimes::REMAIN,
			'MOONRISE' => $this->l10n->t('Moonrise'),
			'MOONSET' => $this->l10n->t('Moonset'),
			'DAYLENGTH' => $this->l10n->t('DayLength'),
			'PRAYER' => $this->l10n->t('Salat'),
			'TIME' => $this->l10n->t('Time')
		];
	}

	public function getConfigSettings(string $userId): array {
		return $this->configService->getSettingsValue($userId);
	}

	public function getConfigAdjustments(string $userId): array {
		return $this->configService->getAdjustmentsValue($userId);
	}

	public function getUserNotification(string $userId): string {
		return $this->configService->getUserNotification($userId);
	}

	public function getAllUsersNotification(): array {
		return $this->configService->getAllUsersNotification();
	}

	public function getUserCalendar(string $userId): string {
		return $this->configService->getUserCalendar($userId);
	}

	public function getAllUserAutoHijriDate(): array {
		return $this->configService->getUsersWithConfigMatching('adjustments', '*:*:*:*:*:*:!0');
	}

	/**
	 * setConfigSettings set settingss values in database
	 * @param string userId
	 * @param string settings
	 */
	public function setConfigSettings(string $userId, string $settings) {
		$p_settings = explode(":", $settings);
		if ($p_settings['6'] != "") {
			$addressInfo = $this->getGeoCode($p_settings['6']);
			if ((isset($addressInfo['latitude'])) && isset($addressInfo['longitude'])) {
				$p_settings['0'] = $addressInfo['latitude'];
				$p_settings['1'] = $addressInfo['longitude'];
				if (isset($addressInfo['elevation'])) {
					$p_settings['3'] = $addressInfo['elevation'];
				}
				$p_settings['2'] = $this->configService->getUserTimeZone($userId);
				$p_settings['6'] = $addressInfo['city'];
			}
		} elseif (($p_settings['0'] == "0") && ($p_settings['1'] == "0")) {
			$op_settings = $this->configService->getSettingsValue($userId);
			$p_settings['0'] = $op_settings['0'];
			$p_settings['1'] = $op_settings['1'];
			if ($p_settings['2'] == "") {
				$p_settings['2'] = $op_settings['2'];
			}
		}
		$settings = implode(":", $p_settings);
		$this->configService->setUserValue($userId, 'settings', $settings);
	}

	/**
	 * setConfigAdjustments set adjustments values in database
	 * @param string userId
	 * @param string adjustments
	 */
	public function setConfigAdjustments(string $userId, string $adjustments) {
		$this->configService->setUserValue($userId, 'adjustments', $adjustments);
	}

	/**
	 * getDayAutoAdjustments get Day Auto Adjustments
	 * @param string userId
	 * @return int adjustments days
	 */
	public function getDayAutoAdjustments(string $userId) {
		if (Helper::pythonInstalled()) {
			$p_settings = $this->configService->getSettingsValue($userId);
			$hijri = new HijriDate(false, $this->l10n);
			$output = null;
			$retval = null;
			exec('python3 ' . __DIR__ . '/../bin/hijriadjust.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $hijri->get_day(), $output, $retval);
			return (int)$output[0];
		}
		return 0;
	}

	/**
	 * get Prayers times from known date
	 *
	 * @param string userId
	 * @param DateTime startDate
	 * @param DateTime endDate
	 * @return array Full paryers times for multidays in specific date
	 */
	public function getPrayerTimesFromDate(string $userId, DateTime $startDate, DateTime $endDate, string $dateFormat = null): array {
		$p_settings = $this->configService->getSettingsValue($userId);
		$adjustments = $this->configService->getAdjustmentsValue($userId);

		// Instantiate the class with your chosen method, Juristic School for Asr and if you want or own Asr factor, make the juristic school null and pass your own Asr shadow factor as the third parameter. Note that all parameters are optional.
		$pt = new PrayerTimes($p_settings['method']); // new PrayerTimes($method, $asrJuristicMethod, $asrShadowFactor);
		$pt->tune($imsak = 0, $fajr = $adjustments['Fajr'], $sunrise = 0, $dhuhr = $adjustments['Dhuhr'], $asr = $adjustments['Asr'], $maghrib = $adjustments['Maghrib'], $sunset = 0, $isha = $adjustments['Isha'], $midnight = 0);

		$interval = DateInterval::createFromDateString('1 day');
		$dateRange = new DatePeriod($startDate, $interval, $endDate, DatePeriod::INCLUDE_END_DATE);

		if ($dateFormat == null) {
			$dateFormat = $p_settings['format_12_24'];
		}
		$times = [];
		foreach ($dateRange as $curDate) {
			$curTime = $pt->getTimes($curDate, $p_settings['latitude'], $p_settings['longitude'], $p_settings['elevation'], $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $dateFormat);
			$times[] = $curTime;
		}
		return $times;
	}

	/**
	 * get Prayers times from known date by number of days
	 *
	 * @param string userId
	 * @param DateTime startDate
	 * @param int days
	 * @return array Full paryers times for multidays in specific date
	 */
	public function getPrayerTimesFromDateByDays(string $userId, DateTime $startDate, int $days): array {
		if ($days == -1) {
			$p_settings = $this->configService->getSettingsValue($userId);
			$adjustments = $this->configService->getAdjustmentsValue($userId);

			$pt = new PrayerTimes($p_settings['method']);
			$pt->tune($imsak = 0, $fajr = $adjustments['Fajr'], $sunrise = 0, $dhuhr = $adjustments['Dhuhr'], $asr = $adjustments['Asr'], $maghrib = $adjustments['Maghrib'], $sunset = 0, $isha = $adjustments['Isha'], $midnight = 0);
			$curTimes = $pt->getTimes($startDate, $p_settings['latitude'], $p_settings['longitude'], $p_settings['elevation'], $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $p_settings['format_12_24']);

			//$curTimes['DayLength'] = $this->getDayLength($curTimes[PrayerTimes::SUNRISE], $curTimes[PrayerTimes::SUNSET]);
			$next = $pt->getNextPrayer($curTimes);
			$curTimes[PrayerTimes::SALAT] = $next[PrayerTimes::SALAT];
			$curTimes[PrayerTimes::REMAIN] = $next[PrayerTimes::REMAIN];
			$times[] = $curTimes;
		} else {
			$endDate = clone $startDate;
			$endDate->modify("+$days days");
			$times = $this->getPrayerTimesFromDate($userId, $startDate, $endDate);
		}
		return $times;
	}

	/**
	 * get Hijri dates from known date range
	 *
	 * @param string userId
	 * @param DateTime startDate
	 * @param DateTime endDate
	 * @return array Full Hijri dates for multidays in specific date
	 */
	public function getHijriDatesFromDate(string $userId, DateTime $startDate, DateTime $endDate): array {
		$adjustments = $this->configService->getAdjustmentsValue($userId);
		$interval = DateInterval::createFromDateString('1 day');
		$dateRange = new DatePeriod($startDate, $interval, $endDate, DatePeriod::INCLUDE_END_DATE);

		$times = [];
		if (($adjustments['nma'] != "") && ($adjustments['nma'] != "0")) {
			$p_settings = $this->configService->getSettingsValue($userId);
			$hijri = new HijriDate(strtotime($startDate->format('Ymd\THis\Z')), $this->l10n);
			$hijriWeekdays = $hijri->hijriWeekdays();
			$islamicMonths = $hijri->getIslamicMonths();
			$hday = $hijri->get_day();
			$hmonth = $hijri->get_month();
			$hyear = $hijri->get_year();
			$output = null;
			$retval = null;
			exec('python3 ' . __DIR__ . '/../bin/hijriadjust.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $startDate->format('Y-m-d\TH:i:s.u\Z') . ' ' . $hday, $output, $retval);
			$offsetDays = (int)$output[0];
			$hday = $hday + $offsetDays;
			if (($hday < 1) || ($hday > 30)) {
				if ($hday < 1) {
					$hday + 30;
					$hmonth--;
					if ($hmonth < 1) {
						$hmonth = 12;
						$hyear--;
					}
				} else {
					$hday - 30;
					$hmonth ++;
					if ($hmonth > 12) {
						$hmonth = 1;
						$hyear++;
					}
				}
				$output = null;
				$retval = null;
				$effectstartDate = clone $startDate;
				$effectstartDate->modify("+$offsetDays days");
				exec('python3 ' . __DIR__ . '/../bin/hijriadjust.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $effectstartDate->format('Y-m-d\TH:i:s.u\Z') . ' ' . $hday, $output, $retval);
				$hday = $hday + (int)$output[0];
			}
			foreach ($dateRange as $curDate) {
				$strDate = $curDate->format('Ymd\THis\Z');
				if ($hday > 29) {
					if ($hday == 30) {
						$output = null;
					}
					$retval = null;
					exec('python3 ' . __DIR__ . '/../bin/hijriadjust.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $curDate->format('Y-m-d\TH:i:s.u\Z') . ' ' . $hday, $output, $retval);
					if ((int)$output[0]) {
						$hday = 1;
						$hmonth++;
						if ($hmonth > 12) {
							$hmonth = 1;
							$hyear++;
						}
					} else {
						$hday = 1;
						$hmonth++;
						if ($hmonth > 12) {
							$hmonth = 1;
							$hyear++;
						}
					}
				}
				$curTime = [$strDate, $hijriWeekdays[date('l', strtotime($strDate))]['tx'], $hday, $islamicMonths[$hmonth]['tx'], $hmonth, $hyear, $hijri->isSpecialDays($hday, $hmonth)];
				$times[] = $curTime;
				$hday++;
			}
		} else {
			foreach ($dateRange as $curDate) {
				//$curDate->format('d-m-Y H:i:s');
				$strDate = $curDate->format('Ymd\THis\Z');
				$hijri = new HijriDate(strtotime($strDate), $this->l10n);
				if ($adjustments['day'] != "") {
					$hijri->tune($adjustments['day']);
				}
				$curTime = [$strDate, $hijri->get_day_name(), $hijri->get_day(), $hijri->get_month_name(), $hijri->get_month(), $hijri->get_year(), $hijri->is_day_special()];
				$times[] = $curTime;
			}
		}

		return $times;
	}

	/**
	 * getDayLength Caclulate day length
	 * @param string sunrise
	 * @param string sunset
	 * @return string of php time
	 */
	private function getDayLength(string $sunrise, string $sunset): string {
		$daylength = strtotime($sunset) - strtotime($sunrise);
		$minutes = $this->twoDigitsFormat((int)(($daylength) / 60) % 60);
		$hours = $this->twoDigitsFormat((int)(($daylength) / 3600));
		return $hours . ":" . $minutes;
	}

	/**
	 * Time Conversion from python to php
	 *
	 * @param string time
	 * @param string timezone
	 * @param string format
	 * @return string of php time
	 */
	private function timeConversion(string $time = null, DateTimeZone $timezone, string $format): string {
		$ret = "";
		if ($time) {
			$date = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $time, new DateTimezone('UTC'));
			if ($date) {
				$ret = $date->setTimezone($timezone)->format($format);
			}
		}
		return $ret;
	}

	/**
	 * Two digits format
	 *
	 * @param int num
	 * @return string of two digits format
	 */
	private function twoDigitsFormat(int $num): string {
		return ($num < 10) ? '0'. $num : $num;
	}

	/**
	 * Ask nominatim information about an unformatted address
	 *
	 * @param string Unformatted address
	 * @return array Full Nominatim result for the given address
	 */
	private function searchForAddress(string $address): array {
		$params = [
			'q' => $address,
			'format' => 'json',
			'addressdetails' => '1',
			'extratags' => '1',
			'namedetails' => '1',
			'limit' => '1',
		];
		$url = 'https://nominatim.openstreetmap.org/search';
		$results = $this->requestJSON($url, $params);
		if (count($results) > 0) {
			return $results[0];
		}
		return ['error' => $this->l10n->t('No result.')];
	}


	private function getNameFromGeo(string $lat, string $lon):?string {
		$city_name = null;
		$opts = array(
			'http' => array(
				'method' => "GET",
				'header' =>
					"User-agent: NextcloudWeather\r\n".
					"Accept: */*\r\n".
					"Accept-language: en\r\n".
					"Connection: close\r\n",
			)
		);
		$city_info = json_decode(file_get_contents("https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=14&lat=".$lat."&lon=".$lon, false, stream_context_create($opts)), true);
		if ((isset($city_info['osm_type'])) && (isset($city_info['osm_id']))) {
			$osm_types = ['node' => 'N', 'relation' => 'R', 'way' => 'W'];
			$city_detail = json_decode(file_get_contents("https://nominatim.openstreetmap.org/details.php?osmtype=".$osm_types[$city_info['osm_type']]."&osmid=".$city_info['osm_id']."&addressdetails=1&hierarchy=0&group_hierarchy=1&format=json", false, stream_context_create($opts)), true);
			if (isset($city_detail['city_name']['names']['name:en'])) {
				$city_name = $city_detail['city_name']['names']['name:en'];
				if (isset($city_detail['city_name']['addresstags']['state'])) {
					$city_name = $city_name . ", " . $city_detail['city_name']['addresstags']['state'];
				} elseif (isset($city_info['address']['state'])) {
					$city_name = $city_name . ", " . $city_info['address']['state'];
				}
			}
		}
		if (!$city_name) {
			if (isset($city_info['address']['suburb'])) {
				$city_name = $city_info['address']['suburb'];
			} elseif (isset($city_info['address']['city_district'])) {
				$city_name = $city_info['address']['city_district'];
			} elseif (isset($city_info['address']['town'])) {
				$city_name = $city_info['address']['town'];
			} elseif (isset($city_info['address']['village'])) {
				$city_name = $city_info['address']['village'];
			} elseif (isset($city_info['address']['city'])) {
				$city_name = $city_info['address']['city'];
			}
			if (isset($city_info['address']['county'])) {
				if ($city_name) {
					$city_name = $city_name . ", " . $city_info['address']['county'];
				} else {
					$city_name = $city_info['address']['county'];
				}
			} elseif (isset($city_info['address']['state'])) {
				if ($city_name) {
					$city_name = $city_name . ", " . $city_info['address']['state'];
				} else {
					$city_name = $city_info['address']['state'];
				}
			}
		}

		if (isset($city_info['address']['country_code'])) {
			$countryList = array('AF' => 'Afghanistan','AX' => 'Aland Islands','AL' => 'Albania','DZ' => 'Algeria','AS' => 'American Samoa','AD' => 'Andorra','AO' => 'Angola','AI' => 'Anguilla','AQ' => 'Antarctica','AG' => 'Antigua and Barbuda','AR' => 'Argentina','AM' => 'Armenia','AW' => 'Aruba','AU' => 'Australia','AT' => 'Austria','AZ' => 'Azerbaijan','BS' => 'Bahamas the','BH' => 'Bahrain','BD' => 'Bangladesh','BB' => 'Barbados','BY' => 'Belarus','BE' => 'Belgium','BZ' => 'Belize','BJ' => 'Benin','BM' => 'Bermuda','BT' => 'Bhutan','BO' => 'Bolivia','BA' => 'Bosnia and Herzegovina','BW' => 'Botswana','BV' => 'Bouvet Island (Bouvetoya)','BR' => 'Brazil','IO' => 'British Indian Ocean Territory (Chagos Archipelago)','VG' => 'British Virgin Islands','BN' => 'Brunei Darussalam','BG' => 'Bulgaria','BF' => 'Burkina Faso','BI' => 'Burundi','KH' => 'Cambodia','CM' => 'Cameroon','CA' => 'Canada','CV' => 'Cape Verde','KY' => 'Cayman Islands','CF' => 'Central African Republic','TD' => 'Chad','CL' => 'Chile','CN' => 'China','CX' => 'Christmas Island','CC' => 'Cocos (Keeling) Islands','CO' => 'Colombia','KM' => 'Comoros the','CD' => 'Congo','CG' => 'Congo the','CK' => 'Cook Islands','CR' => 'Costa Rica','CI' => 'Cote d\'Ivoire','HR' => 'Croatia','CU' => 'Cuba','CY' => 'Cyprus','CZ' => 'Czech Republic','DK' => 'Denmark','DJ' => 'Djibouti','DM' => 'Dominica','DO' => 'Dominican Republic','EC' => 'Ecuador','EG' => 'Egypt','SV' => 'El Salvador','GQ' => 'Equatorial Guinea','ER' => 'Eritrea','EE' => 'Estonia','ET' => 'Ethiopia','FO' => 'Faroe Islands','FK' => 'Falkland Islands (Malvinas)','FJ' => 'Fiji the Fiji Islands','FI' => 'Finland','FR' => 'France','GF' => 'French Guiana','PF' => 'French Polynesia','TF' => 'French Southern Territories','GA' => 'Gabon','GM' => 'Gambia the','GE' => 'Georgia','DE' => 'Germany','GH' => 'Ghana','GI' => 'Gibraltar','GR' => 'Greece','GL' => 'Greenland','GD' => 'Grenada','GP' => 'Guadeloupe','GU' => 'Guam','GT' => 'Guatemala','GG' => 'Guernsey','GN' => 'Guinea','GW' => 'Guinea-Bissau','GY' => 'Guyana','HT' => 'Haiti','HM' => 'Heard Island and McDonald Islands','VA' => 'Holy See (Vatican City State)','HN' => 'Honduras','HK' => 'Hong Kong','HU' => 'Hungary','IS' => 'Iceland','IN' => 'India','ID' => 'Indonesia','IR' => 'Iran','IQ' => 'Iraq','IE' => 'Ireland','IM' => 'Isle of Man','IT' => 'Italy','JM' => 'Jamaica','JP' => 'Japan','JE' => 'Jersey','JO' => 'Jordan','KZ' => 'Kazakhstan','KE' => 'Kenya','KI' => 'Kiribati','KP' => 'Korea','KR' => 'Korea','KW' => 'Kuwait','KG' => 'Kyrgyz Republic','LA' => 'Lao','LV' => 'Latvia','LB' => 'Lebanon','LS' => 'Lesotho','LR' => 'Liberia','LY' => 'Libyan Arab Jamahiriya','LI' => 'Liechtenstein','LT' => 'Lithuania','LU' => 'Luxembourg','MO' => 'Macao','MK' => 'Macedonia','MG' => 'Madagascar','MW' => 'Malawi','MY' => 'Malaysia','MV' => 'Maldives','ML' => 'Mali','MT' => 'Malta','MH' => 'Marshall Islands','MQ' => 'Martinique','MR' => 'Mauritania','MU' => 'Mauritius','YT' => 'Mayotte','MX' => 'Mexico','FM' => 'Micronesia','MD' => 'Moldova','MC' => 'Monaco','MN' => 'Mongolia','ME' => 'Montenegro','MS' => 'Montserrat','MA' => 'Morocco','MZ' => 'Mozambique','MM' => 'Myanmar','NA' => 'Namibia','NR' => 'Nauru','NP' => 'Nepal','AN' => 'Netherlands Antilles','NL' => 'Netherlands the','NC' => 'New Caledonia','NZ' => 'New Zealand','NI' => 'Nicaragua','NE' => 'Niger','NG' => 'Nigeria','NU' => 'Niue','NF' => 'Norfolk Island','MP' => 'Northern Mariana Islands','NO' => 'Norway','OM' => 'Oman','PK' => 'Pakistan','PW' => 'Palau','PS' => 'Palestinian Territory','PA' => 'Panama','PG' => 'Papua New Guinea','PY' => 'Paraguay','PE' => 'Peru','PH' => 'Philippines','PN' => 'Pitcairn Islands','PL' => 'Poland','PT' => 'Portugal, Portuguese Republic','PR' => 'Puerto Rico','QA' => 'Qatar','RE' => 'Reunion','RO' => 'Romania','RU' => 'Russian Federation','RW' => 'Rwanda','BL' => 'Saint Barthelemy','SH' => 'Saint Helena','KN' => 'Saint Kitts and Nevis','LC' => 'Saint Lucia','MF' => 'Saint Martin','PM' => 'Saint Pierre and Miquelon','VC' => 'Saint Vincent and the Grenadines','WS' => 'Samoa','SM' => 'San Marino','ST' => 'Sao Tome and Principe','SA' => 'Saudi Arabia','SN' => 'Senegal','RS' => 'Serbia','SC' => 'Seychelles','SL' => 'Sierra Leone','SG' => 'Singapore','SK' => 'Slovakia (Slovak Republic)','SI' => 'Slovenia','SB' => 'Solomon Islands','SO' => 'Somalia, Somali Republic','ZA' => 'South Africa','GS' => 'South Georgia and the South Sandwich Islands','ES' => 'Spain','LK' => 'Sri Lanka','SD' => 'Sudan','SR' => 'Suriname','SJ' => 'Svalbard & Jan Mayen Islands','SZ' => 'Swaziland','SE' => 'Sweden','CH' => 'Switzerland, Swiss Confederation','SY' => 'Syrian Arab Republic','TW' => 'Taiwan','TJ' => 'Tajikistan','TZ' => 'Tanzania','TH' => 'Thailand','TL' => 'Timor-Leste','TG' => 'Togo','TK' => 'Tokelau','TO' => 'Tonga','TT' => 'Trinidad and Tobago','TN' => 'Tunisia','TR' => 'Turkey','TM' => 'Turkmenistan','TC' => 'Turks and Caicos Islands','TV' => 'Tuvalu','UG' => 'Uganda','UA' => 'Ukraine','AE' => 'United Arab Emirates','GB' => 'United Kingdom','US' => 'United States of America','UM' => 'United States Minor Outlying Islands','VI' => 'United States Virgin Islands','UY' => 'Uruguay, Eastern Republic of','UZ' => 'Uzbekistan','VU' => 'Vanuatu','VE' => 'Venezuela','VN' => 'Vietnam','WF' => 'Wallis and Futuna','EH' => 'Western Sahara','YE' => 'Yemen','ZM' => 'Zambia','ZW' => 'Zimbabwe');
			if ($city_name) {
				$city_name = $city_name . ", " . $countryList[strtoupper($city_info['address']['country_code'])];
			} else {
				$city_name = $countryList[strtoupper($city_info['address']['country_code'])];
			}
		}
		return $city_name;
	}

	/**
	 * Get altitude from coordinates
	 *
	 * @param float $lat Latitude in decimal degree format
	 * @param float $lon Longitude in decimal degree format
	 * @return float altitude in meter
	 */
	private function getAltitude(float $lat, float $lon): float {
		$params = [
			'locations' => $lat . ',' . $lon,
		];
		$url = 'https://api.opentopodata.org/v1/srtm30m';
		$result = $this->requestJSON($url, $params);
		$altitude = 0;
		if (isset($result['results']) && is_array($result['results']) && count($result['results']) > 0
			&& is_array($result['results'][0]) && isset($result['results'][0]['elevation'])) {
			$altitude = floatval($result['results'][0]['elevation']);
		}
		return $altitude;
	}

	/**
	 * Get address and resolve it to get coordinates
	 *
	 * @param string $address Any approximative or exact address
	 * @return array with success state and address information (coordinates and formatted address)
	 */
	private function getGeoCode(string $address): array {
		$addressInfo = $this->searchForAddress($address);
		if (isset($addressInfo['display_name']) && isset($addressInfo['lat']) && isset($addressInfo['lon'])) {
			// get altitude
			$altitude = $this->getAltitude(floatval($addressInfo['lat']), floatval($addressInfo['lon']));
			return [
				'latitude' => $addressInfo['lat'],
				'longitude' => $addressInfo['lon'],
				'elevation' => $altitude,
				'city' => $addressInfo['display_name'],
			];
		} else {
			return ['success' => false];
		}
	}

	/**
	 * Try to use the address set in user personal settings as weather location
	 *
	 * @return array with success state and address information
	 */
	private function usePersonalAddress(): array {
		$account = $this->accountManager->getAccount($this->userManager->get($this->userId));
		try {
			$address = $account->getProperty('address')->getValue();
		} catch (PropertyDoesNotExistException $e) {
			return ['success' => false];
		}
		if ($address === '') {
			return ['success' => false];
		}
		return $this->getGeoCode($address);
	}

	/**
	 * Make a HTTP GET request and parse JSON result.
	 * Request results are cached until the 'Expires' response header says so
	 *
	 * @param string $url Base URL to query
	 * @param array $params GET parameters
	 * @return array which contains the error message or the parsed JSON result
	 */
	private function requestJSON(string $url, array $params = []): array {
		$cacheKey = $url . '|' . implode(',', $params) . '|' . implode(',', array_keys($params));
		$cacheValue = $this->cache->get($cacheKey);
		if ($cacheValue !== null) {
			return $cacheValue;
		}

		try {
			$options = [
				'headers' => [
					'User-Agent' => 'NextcloudSalattime/' . Helper::getVersion() . ' nextcloud.com'
				],
			];

			$reqUrl = $url;
			if (count($params) > 0) {
				$paramsContent = http_build_query($params);
				$reqUrl = $url . '?' . $paramsContent;
			}

			$response = $this->client->get($reqUrl, $options);
			$body = $response->getBody();
			$headers = $response->getHeaders();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Error')];
			} else {
				$json = json_decode($body, true);

				// default cache duration is one hour
				$cacheDuration = 60 * 60;
				if (isset($headers['Expires']) && count($headers['Expires']) > 0) {
					// if the Expires response header is set, use it to define cache duration
					$expireTs = (new \Datetime($headers['Expires'][0]))->getTimestamp();
					$nowTs = (new \Datetime())->getTimestamp();
					$duration = $expireTs - $nowTs;
					if ($duration > $cacheDuration) {
						$cacheDuration = $duration;
					}
				}
				$this->cache->set($cacheKey, $json, $cacheDuration);

				return $json;
			}
		} catch (\Exception $e) {
			$logger = \OC::$server->getLogger();
			$logger->warning($url . 'API error : ' . $e, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
