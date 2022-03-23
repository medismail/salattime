<?php
namespace OCA\salattime\Controller;

use OCA\salattime\Service\ConfigService;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ConfigController extends OCSController {
	private $configService;

	public function __construct(
		$AppName,
		IRequest $request,
		ConfigService $configService
		) {
		parent::__construct($AppName, $request);

		$this->configService = $configService;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
        public function getWidgetContent(): DataResponse {
                return new DataResponse('not found', 400);
	}

}
