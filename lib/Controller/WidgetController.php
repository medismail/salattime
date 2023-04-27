<?php

namespace OCA\salattime\Controller;

require_once __DIR__ . '/../Service/CalculationService.php';

use OCA\SalatTime\Service\CalculationService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class WidgetController extends OCSController {
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
		$name = $this->calculationService->gretNames();
		return new DataResponse([
			'content' => "\n ## " . $times['Hijri'] . " \n" .
				"| ". $name['PRAYER'] ." | &nbsp;&nbsp;" . $name['TIME'] . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; | \n| ----------- | :-----------: | " .
				"\n| " . $name['FAJR'] . " | " . $times['Fajr'] . " | " .
				"\n| " . $name['SUNRISE'] . " | " . $times['Sunrise'] . " | " .
				"\n| " . $name['ZHUHR'] . " | " . $times['Dhuhr'] . " | " .
				"\n| " . $name['ASR'] . " | " . $times['Asr'] . " | " .
				"\n| " . $name['MAGHRIB'] . " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;| " . $times['Maghrib'] . " | " .
				"\n| " . $name['ISHA'] . " | " . $times['Isha'] . " |",
		]);
		//return new DataResponse(array('msg' => 'not found!'), Http::STATUS_NOT_FOUND);
	}
}
