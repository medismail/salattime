<?php

namespace OCA\SalatTime\DAV;

use OCA\SalatTime\AppInfo\Application;
use OCA\SalatTime\Service\CalculationService;
use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use OCP\IL10N;

class CalendarPlugin implements ICalendarProvider {
	/** @const prayertimeCalendar */
	private const prayertimeCalendar = 'prayertime-cal';

	/** @var CalculationService */
	protected $calculationService;

	/** @var IL10N */
	private $l10n;

	public function __construct(CalculationService $calculationService, IL10N $l) {
		$this->calculationService = $calculationService;
		$this->l10n = $l;
	}

	public function getAppId(): string {
		return Application::APP_ID;
	}

	public function fetchAllForCalendarHome(string $principalUri): array {
		if ($this->calculationService->getUserCalendar(basename($principalUri)) == "true") {
			return [
				new Calendar($principalUri, self::prayertimeCalendar, $this->calculationService, $this->l10n),
			];
		}

		return [];
	}

	public function hasCalendarInCalendarHome(string $principalUri, string $calendarUri): bool {
		if ($this->calculationService->getUserCalendar(basename($principalUri)) == "true") {
			return $calendarUri === self::prayertimeCalendar;
		}

		return false;
	}

	public function getCalendarInCalendarHome(string $principalUri, string $calendarUri): ?ExternalCalendar {
		if ($this->hasCalendarInCalendarHome($principalUri, $calendarUri)) {
			return new Calendar($principalUri, $calendarUri, $this->calculationService, $this->l10n);
		}

		return null;
	}
}
