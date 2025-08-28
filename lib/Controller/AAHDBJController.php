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

use OCA\SalatTime\IslamicNetwork\Hijri\HijriBackgroundJob;
use OCP\AppFramework\Controller;
use OCP\Appframework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\BackgroundJob\IJobList;
use OCP\IRequest;

// Auto Adjust Hijri Date Background Job
class AAHDBJController extends Controller {
	private IJobList $jobList;

	public function __construct(string $appName, IRequest $request, IJobList $jobList) {
		parent::__construct($appName, $request);

		$this->jobList = $jobList;
	}

	public function addJob() {
		$this->jobList->add(HijriBackgroundJob::class, null);
	}

	public function removeJob() {
		if (empty($this->calculationService->getAllUserAutoHijriDate())) {
			$this->jobList->remove(HijriBackgroundJob::class, null);
		}
	}
}
