<?php
namespace OCA\SalatTime\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Controller;
use OCA\SalatTime\AppInfo\Application;
use OCA\salattime\Service\ConfigService;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCA\SalatTime\CurrentUser;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IUserManager;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IClient;
use OCP\ICacheFactory;
use OCP\ICache;

class PageController extends Controller {
	private $userId;

        /** @var \OCP\IConfig */
        protected $config;

        /** @var \OCA\salattime\Service\ConfigService */
        private $configService;

        /** @var string */
        protected $user;

        /** @var \OCP\IURLGenerator */
        protected $urlGenerator;

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

	public function __construct($AppName, IRequest $request,
                                               IClientService $clientService,
                                               IConfig $config,
                                               ConfigService $configService,
                                               IURLGenerator $urlGenerator,
                                               CurrentUser $currentUser,
                                               IAccountManager $accountManager,
                                               IUserManager $userManager,
                                               ICacheFactory $cacheFactory,
                                               $UserId){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
                $this->config = $config;
                $this->configService = $configService;
                $this->user = (string) $currentUser->getUID();
                $this->urlGenerator = $urlGenerator;
                $this->accountManager = $accountManager;
                $this->userManager = $userManager;
                $this->clientService = $clientService;
                $this->client = $clientService->newClient();
                $this->cache = $cacheFactory->createDistributed('salattime');
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
                $p_settings = explode(":", $this->config->getUserValue($this->userId, Application::APP_ID, 'settings'));
                if (count($p_settings) > 2) {
                    $latitude = $p_settings['0'];
                    $longitude = $p_settings['1'];
                    $timezone = $p_settings['2'];
                    if (isset($p_settings['3'])) {
                        $elevation = $p_settings['3'];
                    } else {
                        $elevation = "";
                    }
                    if (isset($p_settings['4'])) {
                        $method = $p_settings['4'];
                    } else {
                        $method = "";
                    }
                } else {
                    $latitude = "";
                    $longitude = "";
                    $timezone = "";
                    $elevation = "";
                    $method = "";
                }
                $adjustments = explode(",", $this->config->getUserValue($this->userId, Application::APP_ID, 'adjustments'));
                if (count($adjustments) == 6) {
                    $day = $adjustments['0'];
                    $Fajr = $adjustments['1'];
                    $Dhuhr = $adjustments['2'];
                    $Asr = $adjustments['3'];
                    $Maghrib = $adjustments['4'];
                    $Isha = $adjustments['5'];
                } else {
                    $day = 0;
                    $Fajr = 0;
                    $Dhuhr = 0;
                    $Asr = 0;
                    $Maghrib = 0;
                    $Isha = 0;
                }
                $parameters = array('latitude' => $latitude, 'longitude' => $longitude, 'timezone' => $timezone, 'elevation' => $elevation, 'method' => $method,
                                      'day' => $day, 'Fajr' => $Fajr, 'Dhuhr' => $Dhuhr, 'Asr' => $Asr, 'Maghrib' => $Maghrib, 'Isha' => $Isha);
		return new TemplateResponse(Application::APP_ID, 'index', $parameters);  // templates/index.php
	}

         /**
         * @NoAdminRequired
         * @NoCSRFRequired
         */
        public function settings(): TemplateResponse {
                $templateName = 'settings';  // will use templates/settings.php
                $p_settings = explode(":", $this->config->getUserValue($this->userId, Application::APP_ID, 'settings'));
                if (count($p_settings) > 2) {
                    $latitude = $p_settings['0'];
                    $longitude = $p_settings['1'];
                    $timezone = $p_settings['2'];
                    if (isset($p_settings['3'])) {
                        $elevation = $p_settings['3'];
                    } else {
                        $elevation = "";
                    }
                    if (isset($p_settings['4'])) {
                        $method = $p_settings['4'];
                    } else {
                        $method = "";
                    }
                } else {
                    $latitude = "";
                    $longitude = "";
                    $timezone = "";
                    $elevation = "";
                    $method = "";
                }
                $parameters = array('latitude' => $latitude, 'longitude' => $longitude, 'timezone' => $timezone, 'elevation' => $elevation, 'method' => $method);
                return new TemplateResponse($this->appName, $templateName, $parameters);
        }

         /**
         * @NoAdminRequired
         * @NoCSRFRequired
         */
        public function prayertime(): TemplateResponse {
                $p_settings = explode(":", $this->config->getUserValue($this->userId, Application::APP_ID, 'settings'));
                if (count($p_settings) > 2) {
                    $latitude = $p_settings['0'];
                    $longitude = $p_settings['1'];
                    $timezone = $p_settings['2'];
                    if (isset($p_settings['3'])) {
                        $elevation = $p_settings['3'];
                    } else {
                        $elevation = "";
                    }
                    if (isset($p_settings['4'])) {
                        $method = $p_settings['4'];
                    } else {
                        $method = "";
                    }
	                } else {
                    $latitude = "";
                    $longitude = "";
                    $timezone = "";
                    $elevation = "";
                    $method = "";
                }
                $adjustments = explode(",", $this->config->getUserValue($this->userId, Application::APP_ID, 'adjustments'));
                if (count($adjustments) == 6) {
                    $day = $adjustments['0'];
                    $Fajr = $adjustments['1'];
                    $Dhuhr = $adjustments['2'];
                    $Asr = $adjustments['3'];
                    $Maghrib = $adjustments['4'];
                    $Isha = $adjustments['5'];
                } else {
                    $day = 0;
                    $Fajr = 0;
                    $Dhuhr = 0;
                    $Asr = 0;
                    $Maghrib = 0;
                    $Isha = 0;
                }
                $parameters = array('latitude' => $latitude, 'longitude' => $longitude, 'timezone' => $timezone, 'elevation' => $elevation, 'method' => $method,
                                      'day' => $day, 'Fajr' => $Fajr, 'Dhuhr' => $Dhuhr, 'Asr' => $Asr, 'Maghrib' => $Maghrib, 'Isha' => $Isha);
                return new TemplateResponse(Application::APP_ID, 'prayers', $parameters);  // templates/prayers.php
        }
/*RedirectResponse {
                $url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
                return new RedirectResponse($url);
        }*/

         /**
         * @param float $latitude
         * @param float $longitude
         * @param string $timezone
         *
         * @NoAdminRequired
         * @NoCSRFRequired
         */
        public function savesetting(string $address, float $latitude, float $longitude, string $timezone, float $elevation): RedirectResponse {
                /*if ($latitude != "")
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'latitude', $latitude);
                if ($longitude != "")
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'longitude', $longitude);
                if ($timezone != "")
                    $this->config->setUserValue($this->userId, Application::APP_ID, 'timezone', $timezone);*/
                if ($address != "") {
                    $addressInfo = $this->getGeoCode($address);
                    if ((isset($addressInfo['latitude'])) && isset($addressInfo['longitude'])) {
                        $latitude = $addressInfo['latitude'];
                        $longitude = $addressInfo['longitude'];
                        if (isset($addressInfo['elevation']))
                            $elevation = $addressInfo['elevation'];
                        $cTimezone = $this->config->getUserValue($this->userId, 'core', 'timezone');
                        if ($cTimezone != "")
                            $timezone = $cTimezone;
                    }
                }
                $p_settings = $latitude . ':' . $longitude . ':' . $timezone . ':' . $elevation;
                $this->config->setUserValue($this->userId, Application::APP_ID, 'settings', $p_settings);
                $url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
                return new RedirectResponse($url);
        }


         /**
         * @NoAdminRequired
         * @NoCSRFRequired
         */
        public function adjustments(): TemplateResponse {
                $templateName = 'adjustments';  // will use templates/adjustments.php
                $adjustments = explode(",", $this->config->getUserValue($this->userId, Application::APP_ID, 'adjustments'));
                if (count($adjustments) == 6) {
                    $day = $adjustments['0'];
                    $Fajr = $adjustments['1'];
                    $Dhuhr = $adjustments['2'];
                    $Asr = $adjustments['3'];
                    $Maghrib = $adjustments['4'];
                    $Isha = $adjustments['5'];
                } else {
                    $day = 0;
                    $Fajr = 0;
                    $Dhuhr = 0;
                    $Asr = 0;
                    $Maghrib = 0;
                    $Isha = 0;
                }
                $parameters = array('day' => $day, 'Fajr' => $Fajr, 'Dhuhr' => $Dhuhr, 'Asr' => $Asr, 'Maghrib' => $Maghrib, 'Isha' => $Isha);
                return new TemplateResponse($this->appName, $templateName, $parameters);
        }

         /**
         * @param int $day
         * @param int $Fajr
         * @param int $Dhuhr
         * @param int $Asr
         * @param int $Maghrib
         * @param int $Isha
         *
         * @NoAdminRequired
         * @NoCSRFRequired
         */
        public function saveadjustment(int $day, int $Fajr, int $Dhuhr, int $Asr, int $Maghrib, int $Isha): RedirectResponse {
                if ($day == "")
                    $day = 0;
                if ($Fajr == "")
                    $Fajr = 0;
                if ($Dhuhr == "")
                    $Dhuhr = 0;
                if ($Asr == "")
                    $Asr = 0;
                if ($Maghrib == "")
                    $Maghrib = 0;
                if ($Isha == "")
                    $Isha = 0;
                $adjustments = $day . ',' . $Fajr . ',' . $Dhuhr . ',' . $Asr . ',' . $Maghrib . ',' . $Isha;
                $this->config->setUserValue($this->userId, Application::APP_ID, 'adjustments', $adjustments);
                $url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
                return new RedirectResponse($url);
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
        private function getGeoCode(string $address): array {
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
					'User-Agent' => 'NextcloudSalatTime/' . $this->version . ' nextcloud.com'
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
