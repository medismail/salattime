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

namespace OCA\SalatTime\DAV;

use OCA\SalatTime\AppInfo\Application;
use OCA\SalatTime\Service\CalculationService;
use OCA\DAV\CalDAV\Integration\ExternalCalendar;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use OCP\ICacheFactory;
use OCP\IL10N;

class CalendarPlugin implements ICalendarProvider {
	/** @const prayertimeCalendar */
	private const prayertimeCalendar = 'prayertime-cal';

	/** @var CalculationService */
	protected $calculationService;

	/** @var ICache */
	private $cache;

	/** @var IL10N */
	private $l10n;

	public function __construct(CalculationService $calculationService, ICacheFactory $cacheFactory, IL10N $l) {
		$this->calculationService = $calculationService;
		$this->cache = $cacheFactory->createLocal(self::prayertimeCalendar);
		$this->l10n = $l;
	}

	public function getAppId(): string {
		return Application::APP_ID;
	}

	public function fetchAllForCalendarHome(string $principalUri): array {
		if ($this->calculationService->getUserCalendar(basename($principalUri)) == "true") {
			return [
				new Calendar($principalUri, self::prayertimeCalendar, $this->calculationService, $this->cache, $this->l10n),
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
			return new Calendar($principalUri, $calendarUri, $this->calculationService, $this->cache, $this->l10n);
		}

		return null;
	}
}
