<?php
namespace OCA\SalatTime\Controller;

use OCA\SalatTime\Notification\BackgroundJob;
use OCP\AppFramework\Controller;
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
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
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function removeJob() {
		$this->config->unsetUserNotification($this->userId);
		$this->clearOldNotifications();
		if (empty($this->config->getAllUsersNotification()))
			$this->jobList->remove(BackgroundJob::class, null);
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
