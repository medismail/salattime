<?php

namespace OCA\salattime\Controller;

require_once __DIR__ . '/../Service/CalculationService.php';

use OCA\salattime\Service\CalculationService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ConfigController extends OCSController {
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
                $name = $this->calculationService->gretNames($this->userId);
                return new DataResponse([
                        'content' => "\n ## " . $times[$name['HIJRI']] . " \n" .
                                     "| Salat | &nbsp;&nbsp;Time&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | \n| ----------- | :-----------: | " .
                                     "\n| " . $name['FAJR'] . " | " . $times[$name['FAJR']] . " | " .
                                     "\n| " . $name['SUNRISE'] . " | " . $times[$name['SUNRISE']] . " | " .
                                     "\n| " . $name['ZHUHR'] . " | " . $times[$name['ZHUHR']] . " | " .
                                     "\n| " . $name['ASR'] . " | " . $times[$name['ASR']] . " | " .
                                     "\n| " . $name['MAGHRIB'] . " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;| " . $times[$name['MAGHRIB']] . " | " .
                                     "\n| " . $name['ISHA'] . " | " . $times[$name['ISHA']] . " |",
                ]);
                //return new DataResponse(array('msg' => 'not found!'), Http::STATUS_NOT_FOUND);
	}
}
