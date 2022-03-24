<?php
namespace OCA\salattime\Controller;

use OCA\salattime\Service\ConfigService;

use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ConfigController extends OCSController {
	private $configService;
        private $userId;

	public function __construct(
		$AppName,
		IRequest $request,
		ConfigService $configService,
		$UserId
		) {
		parent::__construct($AppName, $request);

		$this->configService = $configService;
		$this->userId = $UserId;
	}

	/**
	 * @NoCSRFRequired
	 * @NoAdminRequired
	 */
        public function getWidgetContent(): DataResponse {
                /*
                $p_settings = $this->configService->getSettingsValue($this->userId);
                $adjustments = $this->configService->getAdjustmentsValue($this->userId);
                return new DataResponse([
                            'content' => "\n ## Alkhamis 28 shaaban 1443H \n" .
                                         "| Salat&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | Time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp$
                                         "\n| Subuh | 6:00am | " .
                                         "\n| Sunrise | 7:00am | " .
                                         "\n| Dhuhr | 12:00pm | " .
                                         "\n| Asr | 4:00pm | " .
                                         "\n| Maghrib | 6:00pm | " .
                                         "\n| Isha | 8:00pm |",
                             ]);

                 */
                return new DataResponse(array('msg' => 'not found!'), Http::STATUS_NOT_FOUND);
	}
}
