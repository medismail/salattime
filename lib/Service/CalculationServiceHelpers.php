<?php

namespace OCA\SalatTime\Service;

use DateTime;
use DateTimeZone;
use OCA\SalatTime\AppInfo\Application;
use OCA\SalatTime\Tools\Helper;
use OCP\Accounts\PropertyDoesNotExistException;


trait CalculationServiceHelpers {
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
			$countryName = $this->getCountryName((string)$city_info['address']['country_code']);
			if ($city_name && $countryName !== '') {
				$city_name = $city_name . ', ' . $countryName;
			} elseif ($countryName !== '') {
				$city_name = $countryName;
			}
		}
		return $city_name;
	}

	private function getCountryName(string $countryCode): string {
		$countryCode = strtoupper(trim($countryCode));
		if (!preg_match('/^[A-Z]{2}$/', $countryCode)) {
			return '';
		}

		if (class_exists('\Locale')) {
			$countryName = \Locale::getDisplayRegion('-' . $countryCode, 'en');
			if (is_string($countryName) && $countryName !== '') {
				return $countryName;
			}
		}

		return $countryCode;
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
	private function usePersonalAddress(string $userId): array {
		$account = $this->accountManager->getAccount($this->userManager->get($userId));
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
					'User-Agent' => 'NextcloudSalattime/' . Helper::getVersion($this->appManager) . ' nextcloud.com'
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

	private function normalizeSettings(array $settings, array $fallback): array {
		$latitude = $this->normalizeFloat($settings['latitude'] ?? null, (float)$fallback['latitude'], -90, 90);
		$longitude = $this->normalizeFloat($settings['longitude'] ?? null, (float)$fallback['longitude'], -180, 180);

		return [
			'latitude' => $latitude,
			'longitude' => $longitude,
			'timezone' => $this->normalizeTimezone($settings['timezone'] ?? '', $fallback['timezone'] ?? '+0300'),
			'elevation' => $this->normalizeFloat($settings['elevation'] ?? null, (float)($fallback['elevation'] ?? 0), -500, 10000),
			'method' => $this->normalizeChoice($settings['method'] ?? '', self::METHODS, $fallback['method'] ?? 'MWL'),
			'format_12_24' => $this->normalizeChoice($settings['format_12_24'] ?? '', self::TIME_FORMATS, $fallback['format_12_24'] ?? PrayerTimes::TIME_FORMAT_12H),
			'city' => $this->normalizeText($settings['city'] ?? '', 512),
		];
	}

	private function normalizeAdjustments(array $adjustments): array {
		return [
			'Day' => $this->normalizeInt($adjustments['Day'] ?? 0, -3, 3),
			'Fajr' => $this->normalizeInt($adjustments['Fajr'] ?? 0, -120, 120),
			'Dhuhr' => $this->normalizeInt($adjustments['Dhuhr'] ?? 0, -120, 120),
			'Asr' => $this->normalizeInt($adjustments['Asr'] ?? 0, -120, 120),
			'Maghrib' => $this->normalizeInt($adjustments['Maghrib'] ?? 0, -120, 120),
			'Isha' => $this->normalizeInt($adjustments['Isha'] ?? 0, -120, 120),
			'NMA' => $this->normalizeInt($adjustments['NMA'] ?? 0, -15, 15),
		];
	}

	private function normalizeFloat($value, float $fallback, float $min, float $max): float {
		if (!is_numeric($value)) {
			return $fallback;
		}
		$value = (float)$value;
		if ($value < $min || $value > $max) {
			return $fallback;
		}
		return $value;
	}

	private function normalizeInt($value, int $min, int $max): int {
		if (!is_numeric($value)) {
			return 0;
		}
		return max($min, min($max, (int)$value));
	}

	private function normalizeChoice($value, array $allowed, string $fallback): string {
		$value = is_scalar($value) ? (string)$value : '';
		return in_array($value, $allowed, true) ? $value : $fallback;
	}

	private function normalizeText($value, int $maxLength): string {
		$value = is_scalar($value) ? trim((string)$value) : '';
		return substr($value, 0, $maxLength);
	}

	private function normalizeTimezone($timezone, string $fallback): string {
		$timezone = is_scalar($timezone) ? trim((string)$timezone) : '';
		if ($timezone === '') {
			$timezone = $fallback;
		}
		try {
			new DateTimezone($timezone);
			return $timezone;
		} catch (\Exception $e) {
			try {
				new DateTimezone($fallback);
				return $fallback;
			} catch (\Exception $fallbackException) {
				return '+0300';
			}
		}
	}

	private function runPythonScript(string $scriptPath, array $args, int $expectedLines): ?array {
		$pythonPath = Helper::getPythonBinaryPath($this->cache, $this->getAppDataFolder());
		if ($pythonPath === null) {
			return null;
		}

		$output = [];
		$retval = null;
		$stderr = '';
		try {
			Helper::runPythonScriptProcOpen($scriptPath, $args, $output, $retval, $pythonPath, $stderr);
		} catch (\Throwable $e) {
			$this->logger->warning('Python script failed to start: ' . $e->getMessage(), ['app' => Application::APP_ID]);
			return null;
		}

		if ($retval !== 0 || count($output) < $expectedLines) {
			$this->logger->warning('Python script returned invalid output: ' . $scriptPath, ['app' => Application::APP_ID, 'retval' => $retval, 'stderr' => $stderr]);
			return null;
		}

		return $output;
	}
}
