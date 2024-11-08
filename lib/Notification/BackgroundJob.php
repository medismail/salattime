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

declare(strict_types=1);

namespace OCA\SalatTime\Notification;

use OCA\SalatTime\Service\CalculationService;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Notification\IManager;

class BackgroundJob extends TimedJob {
	/** @var IManager */
	protected $notificationManager;

	/** @var CalculationService */
	protected $calculationService;

	public function __construct(ITimeFactory $timeFactory,
								IManager $notificationManager,
								CalculationService $calculationService) {
		parent::__construct($timeFactory);
		// Run once a day
		$this->setInterval(60 * 60 * 24);

		$this->notificationManager = $notificationManager;
		$this->calculationService = $calculationService;
	}

	protected function run($arguments) {
		$this->updateSalatTime();
	}

	/**
	 * update salat time Adhan
	 */
	protected function updateSalatTime() {
		$uids = $this->calculationService->getAllUsersNotification();
		foreach ($uids as $uid) {
			$this->clearOldNotifications($uid);
			$this->sendSalatNotifications($uid);
		}
	}

	/**
	 * Send a daily salat time notification
	 * @param string $uid
	 */
	protected function sendSalatNotifications($uid) {
		$PrayerTime = new \DateTime();
		$salawat = array(CalculationService::FAJR, 'Dhuhr', 'Asr', 'Maghrib', 'Isha');
		$offset = [CalculationService::FAJR => 0, 'Dhuhr' => 0, 'Asr' => 0, 'Maghrib' => 0, 'Isha' => 0];
		$times = $this->calculationService->getPrayerTimesFromDateByDays($uid, $PrayerTime, -1)[0];
		if ($times['Salat'] == CalculationService::FAJR) {
			$Fajrdate = new \DateTime();
			$Fajrdate->setTimestamp(strtotime($times[CalculationService::FAJR]));
			if ($PrayerTime > $Fajrdate) {
				$times = $this->calculationService->getPrayerTimesFromDateByDays($uid, $PrayerTime->modify('+1 day'), 0)[0];
				$offset = [CalculationService::FAJR => 1, 'Dhuhr' => 1, 'Asr' => 1, 'Maghrib' => 1, 'Isha' => 1];
			}
		} else {
			$tomorowTimes = $this->calculationService->getPrayerTimesFromDateByDays($uid, $PrayerTime->modify('+1 day'), 0)[0];
			$salawat_id = [CalculationService::FAJR => 0, 'Dhuhr' => 1, 'Asr' => 2, 'Maghrib' => 3, 'Isha' => 4];
			$id = $salawat_id[$times['Salat']];
			while ($id > 0) {
				$id = $id - 1;
				$times[$salawat[$id]] = $tomorowTimes[$salawat[$id]];
				$offset[$salawat[$id]] = 1;
			}
		}
		foreach ($salawat as $salat) {
			$notification = $this->notificationManager->createNotification();
			if ($offset[$salat] == 1) {
				$PrayerTime->setTimestamp(strtotime('+1 day', strtotime($times[$salat])));
			} else {
				$PrayerTime->setTimestamp(strtotime($times[$salat]));
			}
			try {
				$notification->setApp('salattime')
					->setDateTime($PrayerTime)
					->setObject('Adhan', $salat)
					->setSubject('Adhan for salat');
				$notification->setUser($uid);
				$this->notificationManager->notify($notification);
			} catch (\InvalidArgumentException $e) {
				return;
			}
		}
	}

	/**
	 * Remove old notifications
	 * @param string $uid
	 */
	protected function clearOldNotifications($uid) {
		$salawat = array(CalculationService::FAJR, 'Dhuhr', 'Asr', 'Maghrib', 'Isha');
		foreach ($salawat as $salat) {
			$notification = $this->notificationManager->createNotification();
			try {
				$notification->setApp('salattime')
					->setSubject('Adhan for salat')
					->setObject('Adhan', $salat);
				$notification->setUser($uid);
			} catch (\InvalidArgumentException $e) {
				return;
			}
			$this->notificationManager->markProcessed($notification);
		}
	}
}
