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
 */

declare(strict_types=1);

namespace OCA\SalatTime\Controller;

use OCA\SalatTime\Service\CalculationService;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class WidgetController extends OCSController {
	private CalculationService $calculationService;
	private ?string $userId;

	public function __construct(
		string $appName,
		IRequest $request,
		CalculationService $calculationService,
		?string $userId
	) {
		parent::__construct($appName, $request);
		$this->calculationService = $calculationService;
		$this->userId = $userId;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getWidgetData(): DataResponse {
		$times = $this->calculationService->getPrayerTimes($this->userId);
		$names = $this->calculationService->gretNames();
		$nextPrayerKey = (string)($times['Salat'] ?? '');

		$prayerDefinitions = [
			['key' => 'Fajr', 'nameKey' => 'FAJR'],
			['key' => 'Sunrise', 'nameKey' => 'SUNRISE'],
			['key' => 'Dhuhr', 'nameKey' => 'ZHUHR'],
			['key' => 'Asr', 'nameKey' => 'ASR'],
			['key' => 'Maghrib', 'nameKey' => 'MAGHRIB'],
			['key' => 'Isha', 'nameKey' => 'ISHA'],
		];

		$prayers = array_map(static function (array $definition) use ($names, $times, $nextPrayerKey): array {
			$key = $definition['key'];
			return [
				'key' => $key,
				'label' => (string)($names[$definition['nameKey']] ?? $key),
				'time' => (string)($times[$key] ?? ''),
				'isNext' => $key === $nextPrayerKey,
			];
		}, $prayerDefinitions);

		$nextPrayer = null;
		foreach ($prayers as $prayer) {
			if ($prayer['isNext']) {
				$nextPrayer = $prayer;
				break;
			}
		}

		return new DataResponse([
			'hijri' => (string)($times['Hijri'] ?? ''),
			'city' => (string)($times['City'] ?? ''),
			'prayers' => $prayers,
			'nextPrayer' => $nextPrayer,
			'remaining' => (string)($times['Remain'] ?? ''),
		]);
	}
}
