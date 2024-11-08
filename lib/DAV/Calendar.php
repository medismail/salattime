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
use OCA\DAV\CalDAV\Plugin;
use OCP\IL10N;
use Sabre\CalDAV\Xml\Property\SupportedCalendarComponentSet;
use Sabre\DAV\PropPatch;

class Calendar extends ExternalCalendar {
	/** @const TIME_FORMAT_ISO8601 */
	private const TIME_FORMAT_ISO8601 = 'iso8601';

	/** @const HOURS_13_TO_SECONDS */
	private const HOURS_13_TO_SECONDS = 46800;

	/** @const prayertimeCalendar */
	private const prayertimeCalendar = 'prayertime-cal';

	/** @var string */
	private $principalUri;

	/** @var string */
	private $calendarUri;

	/** @var CalculationService */
	private $calculationService;

	/** @var array */
	private $objectData;

	/** @var string */
	private $calendarName;

	/** @var string */
	private $calendarColor;

	/** @var IL10N */
	private $l10n;

	/**
	 * Calendar constructor.
	 *
	 * @param string $principalUri
	 * @param string $calendarUri
	 * @param CalculationService $calculationService
	 * @param IL10N $l
	 */
	public function __construct(string $principalUri, string $calendarUri, CalculationService $calculationService, IL10N $l) {
		parent::__construct(Application::APP_ID, $calendarUri);

		$this->principalUri = $principalUri;
		$this->calendarUri = $calendarUri;
		$this->calculationService = $calculationService;
		$this->l10n = $l;
		$this->calendarName = $this->getCalendarName($calendarUri);
		$this->calendarColor = $this->getCalendarColor($calendarUri);
	}

	public function propPatch(PropPatch $propPatch) {
		// We can just return here and let oc_properties handle everything
	}

	public function getProperties($properties) {
		// A backend should provide at least minimum properties
		return [
			'{DAV:}displayname' => $this->l10n->t('Salat Time') . ': ' . $this->calendarName,
			'{http://apple.com/ns/ical/}calendar-color' => $this->calendarColor,
			'{' . Plugin::NS_CALDAV . '}supported-calendar-component-set' => new SupportedCalendarComponentSet(['VEVENT']),
		];
	}

	public function getSupportedPrivilegeSet() {
		return null;
	}

	public function setACL(array $acl) {
		throw new \Sabre\DAV\Exception\Forbidden('Setting ACL is not supported on this node');
	}

	public function getACL() {
		return [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}write-properties',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-read',
				'protected' => true,
			],
		];
	}

	public function getGroup() {
		return [];
	}

	public function getOwner() {
		return $this->principalUri;
	}

	public function calendarQuery(array $filters) {
		$timeRange = $this->extractTimeRange($filters);
		$startDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$endDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$startDateTime->setTimestamp($timeRange['start']->getTimestamp());
		$endDateTime->setTimestamp($timeRange['end']->getTimestamp());
		$co = $this->getCalendarObjectsFromTimeRange($startDateTime, $endDateTime);
		return $co;
	}

	public function getChildren() {
		// Get the list of calendar entries
		$children = ['salat_00000000.ics'];

		// Obtain the calendar objects for each of them
		//$children = array_map(function ($childName) using ($this) { return $this->getChild($childName); });

		return $children;
	}

	public function getChild($name) {
		if ($this->childExists($name)) {
			return new CalendarObject($this, $name);
		}
	}

	public function childExists($name) {
		//return preg_match('/^salat_\d{4}-\d{2}-\d{2}\.ics$/', $name);
		$parts = explode('_', substr($name, 0, -4));
		if ((count($parts) === 2) && ($this->objectData) && ($this->objectData[$parts[1]])) {
			return true;
		}
		$logger = \OC::$server->getLogger();
		$logger->error("Child name={$name}.", ['app' => 'salattime']);
		return false;
	}

	public function createFile($name, $data = null) {
		return null;
		// return "\"$etag\"";
		//// ('Creating a new entry is not implemented');
	}

	public function getLastModified() {
		return time();
	}

	public function delete() {
		return null;
	}

	public function getEventDate(int $date, string $salat) {
		return $this->objectData[$date][$salat]['DTStart'];
	}

	public function getEventSummary(int $date, string $salat) {
		return $this->objectData[$date][$salat]['Summary'];
	}

	public function getEventDescription(int $date, string $salat) {
		return $this->objectData[$date][$salat]['Description'];
	}

	public function getEventLocation(int $date, string $salat) {
		return $this->objectData[$date][$salat]['Location'];
	}

	private function getCalendarObjectsFromTimeRange(\DateTime $startDateTime, \DateTime $endDateTime) {
		$extendStartDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$extendEndDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$extendStartDateTime->setTimestamp($startDateTime->getTimestamp() - self::HOURS_13_TO_SECONDS);
		$extendEndDateTime->setTimestamp($endDateTime->getTimestamp() + self::HOURS_13_TO_SECONDS);
		$timeZone = $this->calculationService->getUserTimeZone(basename($this->principalUri));
		$times = $this->calculationService->getPrayerTimesFromDate(basename($this->principalUri), $extendStartDateTime, $extendEndDateTime, self::TIME_FORMAT_ISO8601);
		$salawat = array(CalculationService::FAJR, 'Dhuhr', 'Asr', 'Maghrib', 'Isha');
		$salatEndTime = array('Sunrise', 'Asr', 'Maghrib', 'Isha', 'Lastthird');
		$lastId = count($times) - 2;
		$co = [];
		foreach ($times as $id => $dayTime) {
			foreach ($salawat as $in => $salat) {
				$eDate = new \DateTime($dayTime[$salat]);
				if ($lastId < 3) {
					if (($eDate > $startDateTime) && ($eDate < $endDateTime)) {
						$endDate = new \DateTime($dayTime[$salatEndTime[$in]]);
						$co[] = $this->fillCalendarObjectData($salat, $eDate, $endDate, $timeZone);
					}
				} elseif (($id < $lastId) && (($id > 1) || ($eDate > $startDateTime)) || (($id >= $lastId) && ($eDate < $endDateTime))) {
					$endDate = new \DateTime($dayTime[$salatEndTime[$in]]);
					$co[] = $this->fillCalendarObjectData($salat, $eDate, $endDate, $timeZone);
				}
			}
		}

		$logger = \OC::$server->getLogger();
		$logger->error("Extracted Time Range: Start={$startDateTime->format('Y-m-d H:i:s')} and End={$endDateTime->format('Y-m-d H:i:s')}.", ['app' => 'salattime']);
		$logger->error(json_encode($co), ['app' => 'salattime']);

		return $co;
	}

	private function fillCalendarObjectData(string $salat, \DateTime $eDate, \DateTime $endDate, string $timeZone) {
		$date = $eDate->format('Ymd');
		$this->objectData[$date][$salat]['DTStart'] = $eDate->format('Ymd\THis\Z');
		$this->objectData[$date][$salat]['Summary'] = $this->l10n->t('Salat') . ' ' . $this->l10n->t($salat);
		$endDate->setTimezone(new \DateTimeZone($timeZone));
		$eDate->setTimezone(new \DateTimeZone($timeZone));
		$this->objectData[$date][$salat]['Description'] = $this->l10n->t('Adhan for salat') . ' ' . $this->l10n->t($salat) . ' ' . $this->l10n->t('at') . ': ' . $eDate->format('H:i') . ' ' . $this->l10n->t('end Time') . ' ' . $endDate->format('H:i') . ' 
' .  $this->l10n->t('Please do not delay your salat.');
		$this->objectData[$date][$salat]['Location'] = "";
		return "{$salat}_{$date}.ics";
	}

	// https://datatracker.ietf.org/doc/html/rfc4791#section-9.7
	private function extractTimeRange(array $filterArray):?array {
		$timeRange = null;

		// Check if the top-level "time-range" is set and not false
		if (isset($filterArray['time-range']) && $filterArray['time-range'] !== false) {
			// Directly extract time-range from the top level if present
			$timeRange = $filterArray['time-range'];
		} else {
			// Recursively search through the filter array for "time-range"
			$timeRange = $this->recursiveSearch($filterArray);
		}

		// Normalize the timeRange output to include 'tart' and 'end' with expected formats
		if ($timeRange) {
			$normalizedTimeRange = [
				'start' => $timeRange['start'],
				'end' => $timeRange['end'],
			];
			return $normalizedTimeRange;
		} else {
			return null;
		}
	}

	private function recursiveSearch(array $array):?array {
		foreach ($array as $key => $value) {
			if ($key === 'time-range' && $value !== false) {
				return $value;
			}

			if (is_array($value)) {
				$result = $this->recursiveSearch($value);
				if ($result) {
					return $result;
				}
			}

			// Check for "comp-filters" and "prop-filters" as they might contain time-range
			if (in_array($key, ['comp-filters', 'prop-filters'])) {
				foreach ($value as $filter) {
					$result = $this->recursiveSearch($filter);
					if ($result) {
						return $result;
					}
				}
			}
		}
		return null;
	}

	private function getCalendarName(string $calendarUri) {
		if ($calendarUri == self::prayertimeCalendar) {
			return $this->l10n->t('Prayer\'s Time');
		}
		return null;
	}

	private function getCalendarColor(string $calendarUri) {
		if ($calendarUri == self::prayertimeCalendar) {
			return '#01834b';
		}
		return null;
	}
}
