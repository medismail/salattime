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
	/** @var VCalendar */
	private $calendarObject;
	/**
	 * CalendarObject constructor.
	 *
	 * @param Calendar $calendar
	 * @param string $name
	 * @param CalculationService $calculationService
	 */
	public function __construct(Calendar $calendar, string $name) {
		$this->calendar = $calendar;
		$this->name = $name;
		$this->calendarObject = $this->getCalendarObject();
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
		if ($this->calendarObject) {
			return $this->calendarObject->serialize();
		}
	}

	public function getContentType() {
		return 'text/calendar; charset=utf-8';
	}

	public function getETag() {
		return '"' . md5($this->get()) . '"';
		////return '"' . md5($this->sourceItem->getLastModified()) . '"';
	}

	public function getSize() {
		return mb_strlen($this->calendarObject->serialize());
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
		return time();
		///            return $this->sourceItem->getLastModified();
	}

	private function getCalendarObject(): VCalendar {
		$calendar = new VCalendar();
		$name = $this->getName();
		$Data = $this->extractData($name);
		if ($Data) {
			$eData = $this->calendar->getEventData($Data[1], $Data[0]);
			$event = $calendar->createComponent('VEVENT');
			$event->UID = $name;
			$event->DTSTAMP = $eData['DTStamp'];   //gmdate('Ymd\\THis\\Z');
			$event->DTSTART = $eData['DTStart'];
			$event->DTSTART['VALUE'] = $eData['DTStartValue'];
			$event->SUMMARY = $eData['Summary'];
			$event->DESCRIPTION = $eData['Description'];
			$event->DURATION = $eData['Duration'];
			$event->TRANSP = $eData['Transp'];
			$event->LOCATION = $eData['Location'];
			$event->GEO = $eData['Geo'];
			$calendar->add($event);
		}

		return $calendar;
	}

	private function extractData($name) {
		$parts = explode('_', substr($name, 0, -4));
		if (count($parts) === 2) {
			return $parts;
		}

		return null;
	}
}
