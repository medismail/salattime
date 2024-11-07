<?php

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
