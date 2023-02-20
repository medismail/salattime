<?php
namespace OCA\SalatTime\Controller;

use OCA\SalatTime\Notificaion\BackgroundJob;
use OCP\AppFramework\Controller;
use OCP\BackgroundJob\IJobList;
use OCA\SalatTime\Service\ConfigService;
use OCP\IRequest;

class NotificationController extends Controller {

	private IJobList $jobList;

	private ConfigService $config;

	private $UserId;

	public function __construct(string $appName, IRequest $request, IJobList $jobList, ConfigService $configService, $UserId) {
		parent::__construct($appName, $request);

		$this->jobList = $jobList;
		$this->userId = $UserId;
		$this->config = $configService;
	}

	public function addJob() {
		$this->config->setUserNotification($this->userId);
		if !($this->jobList->has(BackgroundJob::class, null))
			$this->jobList->add(BackgroundJob::class, null);
	}

	public function removeJob() {
		$this->config->unsetUserNotification($this->userId);
		if empty($this->config->getAllUserNotification())
			$this->jobList->remove(BackgroundJob::class, null);
	}
}
