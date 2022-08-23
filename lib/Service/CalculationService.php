<?php
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
use OCA\SalatTime\Service\ConfigService;
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

class CalculationService {

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
		$date = new DateTime(null, new DateTimezone($p_settings['timezone']));
		$curtime = strtotime($date->format('d-m-Y H:i:s'));
		if (($next[PrayerTimes::SALAT] == PrayerTimes::FAJR)&&($date->format('H') > 12)) {
			$nextday = new DateTime('today +1 day', new DateTimezone($p_settings['timezone']));
			$times = $pt->getTimes($nextday, $p_settings['latitude'], $p_settings['longitude'], $p_settings['elevation'], $latitudeAdjustmentMethod = PrayerTimes::LATITUDE_ADJUSTMENT_METHOD_ANGLE, $midnightMode = PrayerTimes::MIDNIGHT_MODE_STANDARD, $p_settings['format_12_24']);
			$next = $pt->getNextPrayerFromDate($date, $times, PrayerTimes::FAJR);
			$curtime = strtotime($nextday->format('d-m-Y H:i:s'));
			$date = $nextday;
			$times['DayOffset'] = 90000;
		}

		$hijri = new HijriDate($curtime, $this->l10n);
		if ($adjustments['day'] != "")
			$hijri->tune($adjustments['day']);

		$times['Hijri'] = $hijri->get_day_name() . ' ' . $hijri->get_day() . ' ' . $hijri->get_month_name() . ' ' . $hijri->get_year() . $this->l10n->t('H');
		$times[PrayerTimes::SALAT] = $next[PrayerTimes::SALAT];
		$times[PrayerTimes::REMAIN] = $next[PrayerTimes::REMAIN];
		$times['DayLength'] = $this->getDayLength($times[PrayerTimes::SUNRISE], $times[PrayerTimes::SUNSET]);
		$times['SpecialDay'] = implode(" ", $hijri->get_day_special_name());
		if (date('N',$curtime) == 5)
			$times['Jumaa'] = "Juma'a";
		if ($hijri->get_month() != 9) //Ramadhane
			$times[PrayerTimes::IMSAK] = "";

		return $times;
	}

	/**
	 * get Sun and Moon informations
	 *
	 * @param string UserId
	 * @return array sun and moons informations
	 */
	public function getSunMoonCalc(string $userId, int $dayoffset=0): array {
		$p_settings = $this->configService->getSettingsValue($userId);
		if (!$p_settings['elevation'])
			$p_settings['elevation'] = 0.0;
		if ($p_settings['format_12_24'] == PrayerTimes::TIME_FORMAT_12H)
			$textFormat_12_24 = 'g:i a';
		else
			$textFormat_12_24 = 'G:i';

		$udtz = new DateTimezone($p_settings['timezone']);
		$date = new DateTime(null, $udtz);
		if (Helper::pythonInstalled()) {
			$output=null;
			$retval=null;
			exec( __DIR__ . '/../bin/salattime.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $udtz->getOffset($date)+$dayoffset, $output, $retval);
			$sunMoonTimes['Sunrise'] = $this->timeConversion($output[1], $udtz, $textFormat_12_24);
			$sunMoonTimes['Sunset'] = $this->timeConversion($output[2], $udtz, $textFormat_12_24);
			$sunMoonTimes['Moonrise'] = $this->timeConversion($output[3], $udtz, $textFormat_12_24);
			$sunMoonTimes['Moonset'] = $this->timeConversion($output[4], $udtz, $textFormat_12_24);
			$sunMoonTimes['MoonPhase'] = $output[5];
			$sunMoonTimes['MoonPhaseAngle'] = $output[6];
			$sunMoonTimes['IlluminatedFraction'] = $output[7];
			$sunMoonTimes['SunAzimuth'] = $output[8];
			$sunMoonTimes['SunAltitude'] = $output[9];
			$sunMoonTimes['MoonAzimuth'] = $output[10];
			$sunMoonTimes['MoonAltitude'] = $output[11];
		} else {
			if ($dayoffset)
				$date = new DateTime('today +1 day', $udtz);
			$sc = new SunCalc($date, $p_settings['latitude'], $p_settings['longitude']);
			//$sunTimes = $sc->getSunTimes();
			$moonTimes = $sc->getMoonTimes();
			if ($moonTimes['moonrise'])
				$sunMoonTimes['Moonrise'] = $moonTimes['moonrise']->format($textFormat_12_24);
			else
				$sunMoonTimes['Moonrise'] = "";
			if ($moonTimes['moonset'])
				$sunMoonTimes['Moonset'] = $moonTimes['moonset']->format($textFormat_12_24);
			else
				$sunMoonTimes['Moonset'] = "";
			$moonIl = $sc->getMoonIllumination();
			$sunMoonTimes['MoonPhase'] = number_format($moonIl['phase']*100, 1);
			$sunMoonTimes['IlluminatedFraction'] = number_format($moonIl['fraction']*100, 1);
		}
		$sunMoonTimes['QiblaDirection'] = Calculation::get($p_settings['latitude'], $p_settings['longitude']);
		return $sunMoonTimes;
	}

	public function gretNames(): array {
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
			'HIJRI' => 'Hijri',
			'MOONRISE' => PrayerTimes::MOONRISE,
			'MOONSET' => PrayerTimes::MOONSET,
			'DAYLENGTH' => 'DayLength'
		];
	}

	public function getConfigSettings(string $userId): array {
		return $this->configService->getSettingsValue($userId);
	}

	public function getConfigAdjustments(string $userId): array {
		return $this->configService->getAdjustmentsValue($userId);
	}

	/**
	 * getDayLength Caclulate day length
	 * @param string sunrise
	 * @param string sunset
	 * @return string of php time
	 */
	private function getDayLength(string $sunrise, string $sunset): string	{
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
	private function timeConversion(string $time, DateTimeZone $timezone, string $format): string {
		$ret = "";
		$date = DateTime::createFromFormat('Y-m-d\TH:i:s.u\Z', $time, new DateTimezone('UTC'));
		if ($date)
			$ret = $date->setTimezone($timezone)->format($format);
		return $ret;
	}

	/**
	 * Two digits format
	 *
	 * @param int num
	 * @return string of two digits format
	 */
	private function twoDigitsFormat(int $num): string {
		return ($num <10) ? '0'. $num : $num;
	}

	/**
	 * Ask nominatim information about an unformatted address
	 *
	 * @param string Unformatted address
	 * @return array Full Nominatim result for the given address
	 */
	private function searchForAddress(string $address): array {
		$params = [
			'format' => 'json',
			'addressdetails' => '1',
			'extratags' => '1',
			'namedetails' => '1',
			'limit' => '1',
		];
		$url = 'https://nominatim.openstreetmap.org/search/' . $address;
		$results = $this->requestJSON($url, $params);
		if (count($results) > 0) {
			return $results[0];
		}
		return ['error' => $this->l10n->t('No result.')];
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
	public function getGeoCode(string $address): array {
		$addressInfo = $this->searchForAddress($address);
		if (isset($addressInfo['display_name']) && isset($addressInfo['lat']) && isset($addressInfo['lon'])) {
			/*$formattedAddress = $this->formatOsmAddress($addressInfo);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'address', $formattedAddress);
			$this->config->setUserValue($this->userId, Application::APP_ID, 'lat', strval($addressInfo['lat']));
			$this->config->setUserValue($this->userId, Application::APP_ID, 'lon', strval($addressInfo['lon']));*/
			// get and store altitude
			$altitude = $this->getAltitude(floatval($addressInfo['lat']), floatval($addressInfo['lon']));
			//$this->config->setUserValue($this->userId, Application::APP_ID, 'altitude', strval($altitude));
			return [
				'latitude' => $addressInfo['lat'],
				'longitude' => $addressInfo['lon'],
				'elevation' => $altitude,
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
					'User-Agent' => 'NextcloudSalattime/' . $this->version . ' nextcloud.com'
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
			$this->logger->warning($url . 'API error : ' . $e, ['app' => Application::APP_ID]);
			return ['error' => $e->getMessage()];
		}
	}
}
