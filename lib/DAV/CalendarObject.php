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

use Sabre\VObject\Component\VCalendar;

class CalendarObject implements \Sabre\CalDAV\ICalendarObject, \Sabre\DAVACL\IACL {
	/** @var Calendar */
	private $calendar;
	/** @var string */
	private $name;
	/** @var array */
	private $eventData;
	/** @var VCalendar|null */
	private $calendarObject = null;
	/** @var string|null */
	private $serializedCalendar = null;
	/** @var string|null */
	private $etag = null;

	/**
	 * CalendarObject constructor.
	 *
	 * Keep construction lightweight because SabreDAV creates one object for
	 * every result returned by calendarQuery(), even when it only needs ETags.
	 *
	 * @param Calendar $calendar
	 * @param string $name
	 */
	public function __construct(Calendar $calendar, string $name) {
		$this->calendar = $calendar;
		$this->name = $name;

		$data = $this->extractData($name);
		if ($data === null) {
			throw new \InvalidArgumentException('Invalid calendar object name');
		}
		$this->eventData = $this->calendar->getEventData($data[1], $data[0]);
	}

	public function getOwner() {
		return null;
	}

	public function getGroup() {
		return null;
	}

	public function getACL() {
		return $this->calendar->getACL();
	}

	public function setACL(array $acl) {
		throw new \Sabre\DAV\Exception\Forbidden('Setting ACL is not supported on this node');
	}

	public function getSupportedPrivilegeSet() {
		return null;
	}

	public function put($data) {
		throw new \Sabre\DAV\Exception\Forbidden('This calendar-object is read-only');
	}

	public function get() {
		if ($this->serializedCalendar === null) {
			$this->serializedCalendar = $this->getCalendarObject()->serialize();
		}
		return $this->serializedCalendar;
	}

	public function getContentType() {
		return 'text/calendar; charset=utf-8';
	}

	public function getETag() {
		if ($this->etag === null) {
			// The event data is already the canonical source used to generate the
			// ICS resource. Hashing it avoids constructing and serializing a
			// VCalendar when a DAV query requests only the ETag.
			$this->etag = '"' . hash('sha256', serialize([$this->name, $this->eventData])) . '"';
		}
		return $this->etag;
	}

	public function getSize() {
		return mb_strlen($this->get());
	}

	public function delete() {
		throw new \Sabre\DAV\Exception\Forbidden('This calendar-object is read-only');
	}

	public function getName() {
		return $this->name;
	}

	public function setName($name) {
		throw new \Sabre\DAV\Exception\Forbidden('This calendar-object is read-only');
	}

	public function getLastModified() {
		return null;
	}

	private function getCalendarObject(): VCalendar {
		if ($this->calendarObject !== null) {
			return $this->calendarObject;
		}

		$calendar = new VCalendar();
		$event = $calendar->createComponent('VEVENT');
		$event->UID = $this->name;
		$event->DTSTAMP = $this->eventData['DTStamp'];
		$event->DTSTART = $this->eventData['DTStart'];
		$event->DTSTART['VALUE'] = $this->eventData['DTStartValue'];
		$event->SUMMARY = $this->eventData['Summary'];
		$event->DESCRIPTION = $this->eventData['Description'];
		$event->DURATION = $this->eventData['Duration'];
		$event->TRANSP = $this->eventData['Transp'];
		$event->LOCATION = $this->eventData['Location'];
		$event->GEO = $this->eventData['Geo'];
		$calendar->add($event);

		$this->calendarObject = $calendar;
		return $this->calendarObject;
	}

	private function extractData(string $name): ?array {
		$parts = explode('_', substr($name, 0, -4));
		if (count($parts) === 2) {
			return $parts;
		}

		return null;
	}
}
