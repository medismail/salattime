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
require_once __DIR__ . '/CalculationServiceHelpers.php';

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
use OCP\App\IAppManager;
use Psr\Log\LoggerInterface;
use OCP\IL10N;
use DateTime;
use DateTimezone;
use DateInterval;
use DatePeriod;

class CalculationService {
	use CalculationServiceHelpers;

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

	private const METHODS = [
		'MWL',
		'MAKKAH',
		'KARACHI',
		'ISNA',
		'JAFARI',
		'GULF',
		'MOONSIGHTING',
		'TURKEY',
		'TEHRAN',
		'EGYPT',
		'QATAR',
		'KUWAIT',
		'TUNISIA',
		'INDONESIA',
		'MOROCCO',
		'JAKIM',
		'JORDAN',
		'ALGERIA',
		'RUSSIA',
		'FRANCE',
		'PORTUGAL',
		'SINGAPORE',
	];

	private const TIME_FORMATS = [
		PrayerTimes::TIME_FORMAT_12H,
		'24h',
	];

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

	/** @var IAppManager */
	private $appManager;

	/** @var LoggerInterface */
	private $logger;

	/** @var IL10N */
	private $l10n;

	public function __construct(
					   ConfigService $configService,
					   IAccountManager $accountManager,
					   IUserManager $userManager,
					   IClientService $clientService,
					   ICacheFactory $cacheFactory,
					   IAppManager $appManager,
					   LoggerInterface $logger,
					   IL10N $l
				   ) {
		$this->configService = $configService;
		$this->accountManager = $accountManager;
		$this->userManager = $userManager;
		$this->clientService = $clientService;
		$this->client = $clientService->newClient();
		$this->cache = $cacheFactory->createDistributed('salattime');
		$this->appManager = $appManager;
		$this->logger = $logger;
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
		if ($adjustments['Day'] != "") {
			if ($adjustments['NMA'] == '15') {
				$hijri->tune($adjustments['Day'], '0');
			} else {
				$hijri->tune($adjustments['Day'], $adjustments['NMA']);
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
		$scriptPath = __DIR__ . '/../bin/salattime.py';
		$args = [
			(float)$p_settings['latitude'],
			(float)$p_settings['longitude'],
			(float)$p_settings['elevation'],
			(int)$udtz->getOffset($date) + (int)$dayoffset,
		];
		$output = $this->runPythonScript($scriptPath, $args, 13);
		if ($output !== null) {
			$sunMoonTimes['Sunrise'] = $this->timeConversion($output[1], $udtz, $textFormat_12_24);
			$sunMoonTimes['Sunset'] = $this->timeConversion($output[2], $udtz, $textFormat_12_24);
			$sunMoonTimes['Moonrise'] = $this->timeConversion($output[3], $udtz, $textFormat_12_24);
			$sunMoonTimes['Moonset'] = $this->timeConversion($output[4], $udtz, $textFormat_12_24);
			$moonPhaseIndex = max(0, min(16, (int)($output[5] * 10 / 225)));
			$sunMoonTimes['MoonPhase'] = $mphase[$moonPhaseIndex];
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

	public function getNames(): array {
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
		return $this->configService->getAllUserAutoHijriDate();
	}

	public function getAppDataFolder(): string {
		return $this->configService->getSystemValue('datadirectory');
	}

	/**
	 * setConfigSettings set settingss values in database
	 * @param string userId
	 * @param array settings
	 */
	public function setConfigSettings(string $userId, array $settings) {
		$previousSettings = $this->configService->getSettingsValue($userId);
		$settings = $this->normalizeSettings($settings, $previousSettings);

		if ($settings['city'] != "") {
			$addressInfo = $this->getGeoCode($settings['city']);
			if ((isset($addressInfo['latitude'])) && isset($addressInfo['longitude'])) {
				$settings['latitude'] = $addressInfo['latitude'];
				$settings['longitude'] = $addressInfo['longitude'];
				if (isset($addressInfo['elevation'])) {
					$settings['elevation'] = $addressInfo['elevation'];
				}
				$userTimezone = $this->configService->getUserTimeZone($userId);
				$settings['timezone'] = $this->normalizeTimezone($userTimezone, $settings['timezone']);
				$settings['city'] = $addressInfo['city'];
			} else {
				$settings['city'] = $previousSettings['city'];
				$settings['latitude'] = $previousSettings['latitude'];
				$settings['longitude'] = $previousSettings['longitude'];
				$settings['elevation'] = $previousSettings['elevation'];
			}
		} elseif (($settings['latitude'] == "0") && ($settings['longitude'] == "0")) {
			$settings['latitude'] = $previousSettings['latitude'];
			$settings['longitude'] = $previousSettings['longitude'];
			if ($settings['timezone'] == "") {
				$settings['timezone'] = $previousSettings['timezone'];
			}
		} else {
			if (($settings['latitude'] == $previousSettings['latitude']) && ($settings['longitude'] == $previousSettings['longitude'])) {
				$settings['city'] = $previousSettings['city'];
			}
		}
		$this->configService->setUserValue($userId, 'settings', $settings);
	}

	/**
	 * setConfigAdjustments set adjustments values in database
	 * @param string userId
	 * @param array adjustments
	 */
	public function setConfigAdjustments(string $userId, array $adjustments) {
		$adjustments = $this->normalizeAdjustments($adjustments);
		$this->configService->setUserValue($userId, 'adjustments', $adjustments);
		$this->configService->setUserAutoHijriDate($userId, (string)$adjustments['NMA'] !== '0');
	}

	/**
	 * getDayAutoAdjustments get Day Auto Adjustments
	 * @param string userId
	 * @return int adjustments days
	 */
	public function getDayAutoAdjustments(string $userId) {
		$p_settings = $this->configService->getSettingsValue($userId);
		$hijri = new HijriDate(false, $this->l10n);
		$scriptPath = __DIR__ . '/../bin/hijriadjust.py';
		$args = [
			(float)$p_settings['latitude'],
			(float)$p_settings['longitude'],
			(float)$p_settings['elevation'],
			(int)$hijri->get_day(),
		];
		$output = $this->runPythonScript($scriptPath, $args, 1);
		if ($output !== null) {
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

	public function getPrayerRows(string $userId, DateTime $startDate, DateTime $endDate): array {
		$confSettings = $this->configService->getSettingsValue($userId);
		$confAdjustments = $this->configService->getAdjustmentsValue($userId);

		$latitude = $confSettings['latitude'] !== '' ? $confSettings['latitude'] : 21.3890824;
		$longitude = $confSettings['longitude'] !== '' ? $confSettings['longitude'] : 39.8579118;
		$timezone = $confSettings['timezone'] !== '' ? $confSettings['timezone'] : '+0300';
		$elevation = $confSettings['elevation'] !== '' ? $confSettings['elevation'] : null;
		$method = $confSettings['method'] !== '' ? $confSettings['method'] : 'MWL';
		$format = $confSettings['format_12_24'] !== '' ? $confSettings['format_12_24'] : PrayerTimes::TIME_FORMAT_12H;

		$pt = new PrayerTimes($method);
		$pt->tune($imsak = 0, $fajr = $confAdjustments['Fajr'], $sunrise = 0, $dhuhr = $confAdjustments['Dhuhr'], $asr = $confAdjustments['Asr'], $maghrib = $confAdjustments['Maghrib'], $sunset = 0, $isha = $confAdjustments['Isha'], $midnight = 0);

		$interval = DateInterval::createFromDateString('1 day');
		$dateRange = new DatePeriod($startDate, $interval, $endDate, DatePeriod::INCLUDE_END_DATE);
		$today = (new DateTime('today', new DateTimeZone($timezone)))->format('Y-m-d');

		$rows = [];
		foreach ($dateRange as $date) {
			$times = $pt->getTimes($date, $latitude, $longitude, $elevation, $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $format);
			$curtime = strtotime($date->format('d-m-Y H:i:s'));
			$hijri = new HijriDate($curtime, $this->l10n);
			if ($confAdjustments['Day'] != "") {
				$hijri->tune($confAdjustments['Day']);
			}

			$specialDay = $hijri->is_day_special();
			if (is_array($specialDay)) {
				$specialDay = implode(' ', $specialDay);
			}

			$rows[] = [
				'date' => $date->format('Y-m-d'),
				'isToday' => $date->format('Y-m-d') === $today,
				'dayName' => $hijri->get_day_name(),
				'hijriDay' => $hijri->get_day(),
				'hijriMonth' => $hijri->get_month(),
				'hijriMonthName' => $hijri->get_month_name(),
				'hijriYear' => $hijri->get_year(),
				'specialDay' => $specialDay,
				'times' => [
					'Imsak' => $hijri->get_month() == 9 ? $times['Imsak'] : '',
					'Fajr' => $times['Fajr'],
					'Sunrise' => $times['Sunrise'],
					'Dhuhr' => $times['Dhuhr'],
					'Asr' => $times['Asr'],
					'Maghrib' => $times['Maghrib'],
					'Isha' => $times['Isha'],
				],
			];
		}

		return $rows;
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
		if (($adjustments['NMA'] != "") && ($adjustments['NMA'] != "0")) {
			$p_settings = $this->configService->getSettingsValue($userId);
			$hijri = new HijriDate(strtotime($startDate->format('Ymd\THis\Z')), $this->l10n);
			$hijriWeekdays = $hijri->hijriWeekdays();
			$islamicMonths = $hijri->getIslamicMonths();
			$hday = $hijri->get_day();
			$hmonth = $hijri->get_month();
			$hyear = $hijri->get_year();
			$scriptPath = __DIR__ . '/../bin/hijriadjust.py';
			$args = [
				(float)$p_settings['latitude'],
				(float)$p_settings['longitude'],
				(float)$p_settings['elevation'],
				(string)$startDate->format('Y-m-d\TH:i:s.u\Z'),
				(int)$hday,
			];
			$output = $this->runPythonScript($scriptPath, $args, 1);
			$offsetDays = $output !== null ? (int)$output[0] : 0;
			$hday = $hday + $offsetDays;
			if (($hday < 1) || ($hday > 30)) {
				if ($hday < 1) {
					$hday = $hday + 30;
					$hmonth--;
					if ($hmonth < 1) {
						$hmonth = 12;
						$hyear--;
					}
				} else {
					$hday = $hday - 30;
					$hmonth ++;
					if ($hmonth > 12) {
						$hmonth = 1;
						$hyear++;
					}
				}
				$effectstartDate = clone $startDate;
				$effectstartDate->modify("+$offsetDays days");
				$scriptPath = __DIR__ . '/../bin/hijriadjust.py';
				$args = [
					(float)$p_settings['latitude'],
					(float)$p_settings['longitude'],
					(float)$p_settings['elevation'],
					(string)$effectstartDate->format('Y-m-d\TH:i:s.u\Z'),
					(int)$hday,
				];
				$output = $this->runPythonScript($scriptPath, $args, 1);
				$hday = $hday + ($output !== null ? (int)$output[0] : 0);
			}
			foreach ($dateRange as $curDate) {
				$strDate = $curDate->format('Ymd\THis\Z');
				if ($hday > 29) {
					if ($hday == 30) {
						$scriptPath = __DIR__ . '/../bin/hijriadjust.py';
						$args = [
							(float)$p_settings['latitude'],
							(float)$p_settings['longitude'],
							(float)$p_settings['elevation'],
							(string)$curDate->format('Y-m-d\TH:i:s.u\Z'),
							(int)$hday,
						];
						$output = $this->runPythonScript($scriptPath, $args, 1);
						if ($output !== null && (int)$output[0]) {
							$hday = 1;
							$hmonth++;
							if ($hmonth > 12) {
								$hmonth = 1;
								$hyear++;
							}
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
				if ($adjustments['Day'] != "") {
					$hijri->tune($adjustments['Day']);
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
}
