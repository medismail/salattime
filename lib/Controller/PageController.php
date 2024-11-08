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

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Controller;
use OCA\SalatTime\AppInfo\Application;
use OCP\IURLGenerator;
use OCA\SalatTime\Tools\CurrentUser;
use OCA\SalatTime\Service\CalculationService;

class PageController extends Controller {
	private $userId;

	/** @var string */
	protected $user;

	/** @var \OCP\IURLGenerator */
	protected $urlGenerator;

	/** @var CalculationService */
	private $calculationService;

	public function __construct($AppName, IRequest $request,
						IURLGenerator $urlGenerator,
						CurrentUser $currentUser,
						CalculationService $calculationService,
						$UserId) {
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->user = (string) $currentUser->getUID();
		$this->urlGenerator = $urlGenerator;
		$this->calculationService = $calculationService;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *	  required and no CSRF check. If you don't know what CSRF is, read
	 *	  it up in the docs or you might create a security hole. This is
	 *	  basically the only required method to add this exemption, don't
	 *	  add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
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
	  * @NoAdminRequired
	  * @NoCSRFRequired
	  */
	public function settings(): TemplateResponse {
		$templateName = 'settings';  // will use templates/settings.php
		$parameters = $this->calculationService->getConfigSettings($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse($this->appName, $templateName, array_merge($parameters, $notification, $calendar));
	}

	 /**
	  * @NoAdminRequired
	  * @NoCSRFRequired
	  */
	public function prayertime(): TemplateResponse {
		$templateName = 'prayers';  // will use templates/prayers.php
		$confSettings = $this->calculationService->getConfigSettings($this->userId);
		$confAdjustments = $this->calculationService->getConfigAdjustments($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($confSettings, $confAdjustments, $notification, $calendar));
	}

	 /**
	  * @param float $latitude
	  * @param float $longitude
	  * @param string $timezone
	  *
	  * @NoAdminRequired
	  * @NoCSRFRequired
	  */
	public function savesetting(string $address, float $latitude, float $longitude, string $timezone, float $elevation, string $method, string $format_12_24): RedirectResponse {
		$p_settings = $latitude . ':' . $longitude . ':' . $timezone . ':' . $elevation . ':' . $method . ':' . $format_12_24 . ':' . $address;
		$this->calculationService->setConfigSettings($this->userId, $p_settings);
		$url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
		return new RedirectResponse($url);
	}


	 /**
	  * @NoAdminRequired
	  * @NoCSRFRequired
	  */
	public function adjustments(): TemplateResponse {
		$templateName = 'adjustments';  // will use templates/adjustments.php
		$parameters = $this->calculationService->getConfigAdjustments($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse($this->appName, $templateName, array_merge($parameters, $notification, $calendar));
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
		if ($day == "") {
			$day = 0;
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
		$adjustments = $day . ',' . $Fajr . ',' . $Dhuhr . ',' . $Asr . ',' . $Maghrib . ',' . $Isha;
		$this->calculationService->setConfigAdjustments($this->userId, $adjustments);
		$url = $this->urlGenerator->getAbsoluteURL('/apps/' . Application::APP_ID . '/');
		return new RedirectResponse($url);
	}
}
