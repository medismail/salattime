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

namespace OCA\salattime\Controller;

require_once __DIR__ . '/../Service/CalculationService.php';

use OCA\SalatTime\Service\CalculationService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class WidgetController extends OCSController {
	private $calculationService;
	private $userId;

	public function __construct(
		$AppName,
		IRequest $request,
		CalculationService $calculationService,
		$UserId
		) {
		parent::__construct($AppName, $request);

		$this->calculationService = $calculationService;
		$this->userId = $UserId;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
	public function getWidgetContent(): DataResponse {
		$times = $this->calculationService->getPrayerTimes($this->userId);
		$name = $this->calculationService->gretNames();
		return new DataResponse([
			'content' => "\n ## " . $times['Hijri'] . " \n" .
				"| ". $name['PRAYER'] ." | &nbsp;&nbsp;" . $name['TIME'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | \n| ----------- | :-----------: | " .
				"\n| " . $name['FAJR'] . " | " . $times['Fajr'] . " | " .
				"\n| " . $name['SUNRISE'] . " | " . $times['Sunrise'] . " | " .
				"\n| " . $name['ZHUHR'] . " | " . $times['Dhuhr'] . " | " .
				"\n| " . $name['ASR'] . " | " . $times['Asr'] . " | " .
				"\n| " . $name['MAGHRIB'] . " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;| " . $times['Maghrib'] . " | " .
				"\n| " . $name['ISHA'] . " | " . $times['Isha'] . " |",
		]);
		//return new DataResponse(array('msg' => 'not found!'), Http::STATUS_NOT_FOUND);
	}
}
