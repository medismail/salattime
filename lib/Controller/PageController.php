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

namespace OCA\SalatTime\Controller;

require_once __DIR__ . '/../Service/CalculationService.php';

use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeZone;
use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\Appframework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Controller;
use OCA\SalatTime\AppInfo\Application;
use OCP\IURLGenerator;
use OCP\IL10N;
use OCA\SalatTime\Tools\CurrentUser;
use OCA\SalatTime\Service\CalculationService;
use OCA\SalatTime\IslamicNetwork\Hijri\HijriDate;
use OCA\SalatTime\IslamicNetwork\PrayerTimes\PrayerTimes;

class PageController extends Controller {
	private $userId;

	/** @var string */
	protected $user;

	/** @var \OCP\IURLGenerator */
	protected $urlGenerator;

	/** @var CalculationService */
	private $calculationService;

	/** @var IL10N */
	private $l10n;

	public function __construct($AppName, IRequest $request,
						IURLGenerator $urlGenerator,
						CurrentUser $currentUser,
						CalculationService $calculationService,
						IL10N $l10n,
						$userId) {
		parent::__construct($AppName, $request);
		$this->userId = $userId;
		$this->user = (string) $currentUser->getUID();
		$this->urlGenerator = $urlGenerator;
		$this->calculationService = $calculationService;
		$this->l10n = $l10n;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *	  required and no CSRF check. If you don't know what CSRF is, read
	 *	  it up in the docs or you might create a security hole. This is
	 *	  basically the only required method to add this exemption, don't
	 *	  add it to any other method if you don't exactly know what it does
	 *
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index() : TemplateResponse {
		$templateName = 'index';  // will use templates/index.php
		$times = $this->calculationService->getPrayerTimes($this->userId);
		$sunmoon = $this->calculationService->getSunMoonCalc($this->userId, $times['DayOffset']);
		$relative_url = ['rurl' => $this->urlGenerator->imagePath(Application::APP_ID, '')];
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($times, $sunmoon, $relative_url, $notification, $calendar));
	}

	 /**
	  */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function settings(): TemplateResponse {
		$templateName = 'settings';  // will use templates/settings.php
		$parameters = $this->calculationService->getConfigSettings($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($parameters, $notification, $calendar));
	}

	 /**
	  */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function prayertime(): TemplateResponse {
		$templateName = 'prayers';  // will use templates/prayers.php
		$confSettings = $this->calculationService->getConfigSettings($this->userId);
		$confAdjustments = $this->calculationService->getConfigAdjustments($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		$prayers = ['prayers' => $this->getPrayerRows($confSettings, $confAdjustments)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($notification, $calendar, $prayers));
	}

	 /**
	  * @param float $latitude
	  * @param float $longitude
	  * @param string $timezone
	  *
	  */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function savesetting(string $address, float $latitude, float $longitude, string $timezone, float $elevation, string $method, string $format_12_24): RedirectResponse {
		$p_settings = [
			'latitude' => $latitude,
			'longitude' => $longitude,
			'timezone' => $timezone,
			'elevation' => $elevation,
			'method' => $method,
			'format_12_24' => $format_12_24,
			'city' => $address
		];
		$this->calculationService->setConfigSettings($this->userId, $p_settings);
		$url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
		return new RedirectResponse($url);
	}


	 /**
	  */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function adjustments(): TemplateResponse {
		$templateName = 'adjustments';  // will use templates/adjustments.php
		$parameters = $this->calculationService->getConfigAdjustments($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($parameters, $notification, $calendar));
	}

	 /**
	  * @param int $Day
	  * @param int $Fajr
	  * @param int $Dhuhr
	  * @param int $Asr
	  * @param int $Maghrib
	  * @param int $Isha
	  * @param int $NMA
	  *
	  */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function saveadjustment(int $Day, int $Fajr, int $Dhuhr, int $Asr, int $Maghrib, int $Isha, int $NMA): RedirectResponse {
		if ($Day == "") {
			$Day = 0;
		}
		if ($Fajr == "") {
			$Fajr = 0;
		}
		if ($Dhuhr == "") {
			$Dhuhr = 0;
		}
		if ($Asr == "") {
			$Asr = 0;
		}
		if ($Maghrib == "") {
			$Maghrib = 0;
		}
		if ($Isha == "") {
			$Isha = 0;
		}
		if ($NMA == "") {
			$NMA = 0;
		}
		if ($NMA) {
			$Day = $this->calculationService->getDayAutoAdjustments($this->userId);
		}
		$adjustments = [
			'Day' => $Day,
			'Fajr' => $Fajr,
			'Dhuhr' => $Dhuhr,
			'Asr' => $Asr,
			'Maghrib' => $Maghrib,
			'Isha' => $Isha,
			'NMA' => $NMA
		];
		$this->calculationService->setConfigAdjustments($this->userId, $adjustments);
		$url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
		return new RedirectResponse($url);
	}

	private function getPrayerRows(array $confSettings, array $confAdjustments): array {
		$latitude = $confSettings['latitude'] !== '' ? $confSettings['latitude'] : 21.3890824;
		$longitude = $confSettings['longitude'] !== '' ? $confSettings['longitude'] : 39.8579118;
		$timezone = $confSettings['timezone'] !== '' ? $confSettings['timezone'] : '+0300';
		$elevation = $confSettings['elevation'] !== '' ? $confSettings['elevation'] : null;
		$method = $confSettings['method'] !== '' ? $confSettings['method'] : 'MWL';
		$format = $confSettings['format_12_24'] !== '' ? $confSettings['format_12_24'] : PrayerTimes::TIME_FORMAT_12H;

		$pt = new PrayerTimes($method);
		$pt->tune($imsak = 0, $fajr = $confAdjustments['Fajr'], $sunrise = 0, $dhuhr = $confAdjustments['Dhuhr'], $asr = $confAdjustments['Asr'], $maghrib = $confAdjustments['Maghrib'], $sunset = 0, $isha = $confAdjustments['Isha'], $midnight = 0);

		$startDate = new DateTime('today -3 day', new DateTimeZone($timezone));
		$endDate = new DateTime('today +12 day', new DateTimeZone($timezone));
		$interval = DateInterval::createFromDateString('1 day');
		$dateRange = new DatePeriod($startDate, $interval, $endDate);
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
}
