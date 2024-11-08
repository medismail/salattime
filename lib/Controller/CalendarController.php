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

use OCP\AppFramework\Controller;
use OCA\SalatTime\Service\ConfigService;
use OCP\IRequest;

class CalendarController extends Controller {
	/** @var ConfigService */
	private ConfigService $config;

	/** @var UserId */
	private $UserId;

	public function __construct(string $appName, IRequest $request, ConfigService $configService, $UserId) {
		parent::__construct($appName, $request);

		$this->config = $configService;
		$this->userId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function addCalendar() {
		$this->config->setUserCalendar($this->userId);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function removeCalendar() {
		$this->config->unsetUserCalendar($this->userId);
	}
}
