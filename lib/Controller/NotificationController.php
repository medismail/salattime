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

use OCA\SalatTime\Notification\BackgroundJob;
use OCP\AppFramework\Controller;
use OCP\Appframework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\BackgroundJob\IJobList;
use OCP\Notification\IManager;
use OCA\SalatTime\Service\ConfigService;
use OCP\IRequest;

class NotificationController extends Controller {
	private IJobList $jobList;

	/** @var IManager */
	private IManager $notificationManager;

	/** @var ConfigService */
	private ConfigService $config;

	/** @var UserId */
	private $UserId;

	public function __construct(string $appName, IRequest $request, IJobList $jobList, IManager $notificationManager, ConfigService $configService, $UserId) {
		parent::__construct($appName, $request);

		$this->jobList = $jobList;
		$this->notificationManager = $notificationManager;
		$this->config = $configService;
		$this->userId = $UserId;
	}

	/**
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function addJob() {
		$this->config->setUserNotification($this->userId);
		$this->jobList->add(BackgroundJob::class, null);
		/*if ($this->jobList->has(BackgroundJob::class, null) == false) {
			$this->jobList->add(BackgroundJob::class, null);
		} else {
			$job = $this->jobList->getJobs(BackgroundJob::class, )
			$this->jobList->resetBackgroundJob($job);
		}*/
	}

	/**
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function removeJob() {
		$this->config->unsetUserNotification($this->userId);
		$this->clearOldNotifications();
		if (empty($this->config->getAllUsersNotification())) {
			$this->jobList->remove(BackgroundJob::class, null);
		}
	}

	/**
	 * Remove old notifications
	 */
	private function clearOldNotifications() {
		$salawat = array('Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha');
		foreach ($salawat as $salat) {
			$notification = $this->notificationManager->createNotification();
			try {
				$notification->setApp('salattime')
					->setSubject('Adhan for salat')
					->setObject('Adhan', $salat);
				$notification->setUser($this->userId);
			} catch (\InvalidArgumentException $e) {
				return;
			}
			$this->notificationManager->markProcessed($notification);
		}
	}
}
