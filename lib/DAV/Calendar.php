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
use OCP\ICache;
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

	/** @const hijriCalendar */
	private const hijriCalendar = 'hijri-cal';

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

	/** @var ICache */
	private $cache;

	/** @var IL10N */
	private $l10n;

	/**
	 * Calendar constructor.
	 *
	 * @param string $principalUri
	 * @param string $calendarUri
	 * @param CalculationService $calculationService
	 * @param ICache $cache
	 * @param IL10N $l
	 */
	public function __construct(string $principalUri, string $calendarUri, CalculationService $calculationService, ICache $cache, IL10N $l) {
		parent::__construct(Application::APP_ID, $calendarUri);

		$this->principalUri = $principalUri;
		$this->calendarUri = $calendarUri;
		$this->calculationService = $calculationService;
		$this->cache = $cache;
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
		$endDateTime = new \DateTime('next year', new \DateTimezone('UTC'));
		if ($timeRange['start']) {
			$startDateTime->setTimestamp($timeRange['start']->getTimestamp());
		}
		if ($timeRange['end']) {
			$endDateTime->setTimestamp($timeRange['end']->getTimestamp());
		}
		$co = [];
		if ($this->calendarUri == self::prayertimeCalendar) {
			$co = $this->getSTCalendarObjectsFromTimeRange($startDateTime, $endDateTime);
		} elseif ($this->calendarUri == self::hijriCalendar) {
			$co = $this->getHDCalendarObjectsFromTimeRange($startDateTime, $endDateTime);
		}
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
		if ((count($parts) === 2) && ($this->objectData) && (isset($this->objectData[$parts[1]]))) {
			return true;
		} else {
			$cacheKey = $this->principalUri . '/' . $this->calendarUri . '/' . $parts[1];
			$cacheValue = $this->cache->get($cacheKey);
			if ($cacheValue !== null) {
				$this->objectData[$parts[1]] = $cacheValue;
				return true;
			}
		}

		/*$logger = \OC::$server->getLogger();
		$logger->error("Child name={$name}, user={$this->principalUri}, calendar={$this->calendarUri}.", ['app' => 'salattime']);*/
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

	public function getEventData(int $date, string $event): array {
		return $this->objectData[$date][$event];
	}

	private function getSTCalendarObjectsFromTimeRange(\DateTime $startDateTime, \DateTime $endDateTime): array {
		$extendStartDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$extendEndDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$extendStartDateTime->setTimestamp($startDateTime->getTimestamp() - self::HOURS_13_TO_SECONDS);
		$extendEndDateTime->setTimestamp($endDateTime->getTimestamp() + self::HOURS_13_TO_SECONDS);
		$config = $this->getConfigSettings(basename($this->principalUri));
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
						$co[] = $this->fillSTCalendarObjectData($salat, $eDate, $endDate, $config);
					}
				} elseif (($id < $lastId) && (($id > 1) || ($eDate > $startDateTime)) || (($id >= $lastId) && ($eDate < $endDateTime))) {
					$endDate = new \DateTime($dayTime[$salatEndTime[$in]]);
					$co[] = $this->fillSTCalendarObjectData($salat, $eDate, $endDate, $config);
				}
			}
		}
		$this->updateCache();

		return $co;
	}

	private function fillSTCalendarObjectData(string $salat, \DateTime $eDate, \DateTime $endDate, array $config): string {
		$date = $eDate->format('Ymd');
		$tSalat = $this->l10n->t($salat);
		$this->objectData[$date][$salat]['DTStart'] = $eDate->format('Ymd\THis\Z');
		$this->objectData[$date][$salat]['DTStamp'] = $this->objectData[$date][$salat]['DTStart'];
		$this->objectData[$date][$salat]['DTStartValue'] = 'DATE-TIME';
		$this->objectData[$date][$salat]['Summary'] = $this->l10n->t('Salat %s', [$tSalat]);
		$endDate->setTimezone(new \DateTimeZone($config['TimeZone']));
		$eDate->setTimezone(new \DateTimeZone($config['TimeZone']));
		$this->objectData[$date][$salat]['Description'] = $this->l10n->t('The Adhan for salat %s is at %s, the prayer time ends at %s.', [$tSalat, $eDate->format($config['TimeFormat']) . $config['suffixes'][$eDate->format('a')], $endDate->format($config['TimeFormat']) . $config['suffixes'][$endDate->format('a')]]) .chr(0x0D).chr(0x0A). $this->l10n->t('Performing prayers is a duty on the believers at the appointed times.');
		$this->objectData[$date][$salat]['Duration'] = 'PT10M';
		$this->objectData[$date][$salat]['Transp'] = 'OPAQUE';
		$this->objectData[$date][$salat]['Location'] = $config['Location'];
		$this->objectData[$date][$salat]['Geo'] = $config['Geo'];
		return "{$salat}_{$date}.ics";
	}

	private function getHDCalendarObjectsFromTimeRange(\DateTime $startDateTime, \DateTime $endDateTime): array {
		$extendStartDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$extendEndDateTime = new \DateTime('', new \DateTimezone('UTC'));
		$extendStartDateTime->setTimestamp($startDateTime->getTimestamp());
		$extendEndDateTime->setTimestamp($endDateTime->getTimestamp());
		$times = $this->calculationService->getHijriDatesFromDate(basename($this->principalUri), $extendStartDateTime, $extendEndDateTime);
		$co = [];
		foreach ($times as $id => $dayData) {
			$spday = '';
			if ($dayData[6]) {
				$spday = ' (' . $dayData[6] .')';
			}
			$co[] = $this->fillHDCalendarObjectData($dayData, $spday);
		}
		$this->updateCache();

		return $co;
	}

	private function fillHDCalendarObjectData(array $eData, string $spday): string {
		$date = explode('T', $eData[0])[0];
		$this->objectData[$date]['hijri']['DTStart'] = $date;
		$this->objectData[$date]['hijri']['DTStartValue'] = 'DATE';
		$this->objectData[$date]['hijri']['DTStamp'] = $eData[0];
		$this->objectData[$date]['hijri']['Summary'] = $eData[2] . $spday;
		$this->objectData[$date]['hijri']['Description'] = $this->l10n->t('Today is: %s %s %s %s', [$eData[1], $eData[2], $eData[3], $eData[5]]);
		$this->objectData[$date]['hijri']['Duration'] = 'P1D';
		$this->objectData[$date]['hijri']['Transp'] = 'TRANSPARENT';
		$this->objectData[$date]['hijri']['Location'] = '';
		$this->objectData[$date]['hijri']['Geo'] = '';
		return "hijri_{$date}.ics";
	}

	private function getConfigSettings(string $userId): array {
		$pSettings = $this->calculationService->getConfigSettings($userId);
		$timeFormat = $this->getUserTimeFormat($pSettings['format_12_24']);
		$config = [];
		$config['TimeFormat'] = $timeFormat['textFormat_12_24'];
		$config['suffixes'] = $timeFormat['suffixes'];
		$config['TimeZone'] = $pSettings['timezone'];
		$config['Location'] = $pSettings['city'];
		$config['Geo'] = $pSettings['latitude'] . ';' . $pSettings['longitude'];
		return $config;
	}

	public function getUserTimeFormat(string $format_12_24): array {
		if ($format_12_24 == CalculationService::TIME_FORMAT_12H) {
			$timeFormat['suffixes'] = [ 'am' => $this->l10n->t('AM'), 'pm' => $this->l10n->t('PM') ];
			$timeFormat['textFormat_12_24'] = 'g:i';
		} else {
			$timeFormat['suffixes'] = [ 'am' => '', 'pm' => '' ];
			$timeFormat['textFormat_12_24'] = 'G:i';
		}
		return $timeFormat;
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

	private function updateCache() {
		if ($this->objectData) {
			foreach ($this->objectData as $eDate => $eData) {
				$cacheKey = $this->principalUri . '/' . $this->calendarUri . '/' . $eDate;
				$this->cache->set($cacheKey, $eData, 3600);
			}
		}
	}

	private function getCalendarName(string $calendarUri):?string {
		if ($calendarUri == self::prayertimeCalendar) {
			return $this->l10n->t('Prayer Times');
		} elseif ($calendarUri == self::hijriCalendar) {
			return $this->l10n->t('Hijri Date');
		}
		return null;
	}

	private function getCalendarColor(string $calendarUri):?string {
		if ($calendarUri == self::prayertimeCalendar) {
			return '#01834b';
		} elseif ($calendarUri == self::hijriCalendar) {
			return '#6b3600';
		}
		return null;
	}
}
