<?php

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

	protected function run() {
		$this->updateSalatTime();
	}

	/**
	 * update salat time Adhen
	 */
	protected function updateSalatTime() {
		$uids = $this->calculationService->getAllUsersNotification()
		foreach ($uids as $uid)
			$this->clearOldNotifications($uid);
			$this->sendSalatNotifications($uid);
		}
	}

	/**
	 * Send a daily salat time notification
	 * @param int $uid
	 */
	protected function sendSalatNotifications($uid) {
		$PrayerTime = new \DateTime();
		$times = $this->calculationService->getPrayerTimesFromDate($uid, $PrayerTime, 1);
		$salawat = array('Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha');
		foreach ($salawat as $salat) {
			$notification = $this->notificationManager->createNotification();
			$PrayerTime->setTimestamp(strtotime($times[$salat]));
			try {
				$notification->setApp('salattime')
					->setDateTime($PrayerTime)
					->setObject('Adhen', $salat)
					->setSubject('Adhen for salat');
				$notification->setUser($uid);
				$this->notificationManager->notify($notification);
			} catch (\InvalidArgumentException $e) {
				return;
			}
		}
	}

	/**
	 * Remove old notifications
	 */
	protected function clearOldNotifications($uid) {
		$salawat = array('Fajr', 'Dhuhr', 'Asr', 'Maghrib', 'Isha');
		foreach ($salawat as $salat) {
			$notification = $this->notificationManager->createNotification();
			try {
				$notification->setApp('salattime')
					->setSubject('Adhen for salat')
					->setObject('Adhen', $salat);
				$notification->setUser($uid);
			} catch (\InvalidArgumentException $e) {
				return;
			}
			$this->notificationManager->markProcessed($notification);
		}
	}
}
