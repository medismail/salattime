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
 */

namespace OCA\SalatTime\Controller;

require_once __DIR__ . '/../Service/CalculationService.php';

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

class PageController extends Controller {
	private const MAX_PRAYER_RANGE_DAYS = 31;

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

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function index() : TemplateResponse {
		$templateName = 'index';
		$times = $this->calculationService->getPrayerTimes($this->userId);
		$sunmoon = $this->calculationService->getSunMoonCalc($this->userId, $times['DayOffset']);
		$relative_url = ['rurl' => $this->urlGenerator->imagePath(Application::APP_ID, '')];
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($times, $sunmoon, $relative_url, $notification, $calendar));
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function settings(): TemplateResponse {
		$templateName = 'settings';
		$parameters = $this->calculationService->getConfigSettings($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($parameters, $notification, $calendar));
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function prayertime(): TemplateResponse {
		$templateName = 'prayers';
		$range = $this->getPrayerDateRange($confSettings['timezone'] ?? '+0300');
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		$prayers = [
			'prayers' => $this->calculationService->getPrayerRows($this->userId, $range['start'], $range['end']),
			'prayerStartDate' => $range['start']->format('Y-m-d'),
			'prayerEndDate' => $range['end']->format('Y-m-d'),
			'prayerRangeError' => $range['message'],
			'maxPrayerRangeDays' => self::MAX_PRAYER_RANGE_DAYS,
		];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($notification, $calendar, $prayers));
	}

	/**
	 * @param float $latitude
	 * @param float $longitude
	 * @param string $timezone
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

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function adjustments(): TemplateResponse {
		$templateName = 'adjustments';
		$parameters = $this->calculationService->getConfigAdjustments($this->userId);
		$notification = ['notification' => $this->calculationService->getUserNotification($this->userId)];
		$calendar = ['calendar' => $this->calculationService->getUserCalendar($this->userId)];
		return new TemplateResponse(Application::APP_ID, $templateName, array_merge($parameters, $notification, $calendar));
	}

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

	private function getPrayerDateRange(string $timezone): array {
		try {
			$timeZone = new DateTimeZone($timezone !== '' ? $timezone : '+0300');
		} catch (\Exception $e) {
			$timeZone = new DateTimeZone('+0300');
		}

		$defaultStart = new DateTime('today -3 day', $timeZone);
		$defaultEnd = new DateTime('today +11 day', $timeZone);
		$message = '';

		$startDate = $this->parseDateParameter($this->request->getParam('start', ''), $timeZone) ?: $defaultStart;
		$endDate = $this->parseDateParameter($this->request->getParam('end', ''), $timeZone) ?: $defaultEnd;

		if ($endDate < $startDate) {
			$endDate = clone $startDate;
			$message = $this->l10n->t('End date was adjusted because it was before the start date.');
		}

		$days = (int) $startDate->diff($endDate)->days + 1;
		if ($days > self::MAX_PRAYER_RANGE_DAYS) {
			$endDate = clone $startDate;
			$endDate->modify('+' . (self::MAX_PRAYER_RANGE_DAYS - 1) . ' days');
			$message = $this->l10n->t('The selected range is limited to 31 days.');
		}

		return [
			'start' => $startDate,
			'end' => $endDate,
			'message' => $message,
		];
	}

	private function parseDateParameter($value, DateTimeZone $timeZone): ?DateTime {
		if (!is_string($value) || $value === '') {
			return null;
		}

		$date = DateTime::createFromFormat('!Y-m-d', $value, $timeZone);
		if ($date === false || $date->format('Y-m-d') !== $value) {
			return null;
		}

		return $date;
	}
}
