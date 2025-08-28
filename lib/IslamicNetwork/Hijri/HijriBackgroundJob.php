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

namespace OCA\SalatTime\IslamicNetwork\Hijri;

use OCA\SalatTime\Service\CalculationService;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;

class HijriBackgroundJob extends TimedJob {
	/** @var CalculationService */
	protected $calculationService;

	public function __construct(ITimeFactory $timeFactory,
								CalculationService $calculationService) {
		parent::__construct($timeFactory);
		// Run once a day
		$this->setInterval(60 * 60 * 24);

		$this->calculationService = $calculationService;
	}

	protected function run($arguments) {
		$this->updateHijriDate();
	}

	/**
	 * update salat time Hijri Date
	 */
	protected function updateHijriDate() {
		if (Helper::pythonInstalled()) {
			$uids = $this->calculationService->getAllUserAutoHijriDate();
			foreach ($uids as $uid) {
				$this->updateUserHijriDate($uid);
			}
		}
	}

	/**
	 * update salat time User Hijri Date
	 */
	protected function updateUserHijriDate($uid) {
		$adjustments = $this->calculationService->getConfigAdjustments($uid);
		$date = new DateTime('', new DateTimezone('UTC'));
		$curtime = strtotime($date->format('d-m-Y H:i:s'));
		$hijri = new HijriDate($curtime);
		$hijri->tune($adjustments['day'], $adjustments['nma']);
		if ($hijri->get_day() == 29) {
			$output = null;
			$retval = null;
			$date = new DateTime('today +1 day', new DateTimezone('UTC'));
			$p_settings = $this->calculationService->getConfigSettings($uid);  //$p_settings['timezone']
			exec('python3 ' . __DIR__ . '/../../bin/hijriadjust.py ' . $p_settings['latitude'] . ' ' . $p_settings['longitude'] . ' ' . $p_settings['elevation'] . ' ' . $date->format('Y-m-d\TH:i:s.u\Z') . ' 30', $output, $retval);
			$adjust = (int)$output[0];
			$curtime = strtotime($date->format('d-m-Y H:i:s'));
			$hijri = new HijriDate($curtime);
			$hijri->tune($adjustments['day'], $adjustments['nma']);
			if (($adjust == 0) && ($hijri->get_day() == 1)) {
				$adjustments['nma'] = -$hijri->get_month();
				$this->calculationService->setConfigAdjustments($uid, implode(":", $adjustments));
			} elseif (($adjust == 1)($hijri->get_day() == 30)) {
				$adjustments['nma'] = $hijri->get_month();
				$this->calculationService->setConfigAdjustments($uid, implode(":", $adjustments));
			}
		} elseif ($hijri->get_day() == 1) {
			if ($adjustments['nma'] == $hijri->get_month()) {
				$adjustments['day'] = $adjustments['day'] + 1;
				$adjustments['nma'] = 15;
				$this->calculationService->setConfigAdjustments($uid, implode(":", $adjustments));
			} elseif ($adjustments['nma'] == -$hijri->get_month()) {
				$adjustments['day'] = $adjustments['day'] - 1;
				$adjustments['nma'] = 15;
				$this->calculationService->setConfigAdjustments($uid, implode(":", $adjustments));
			}
		}
	}
}
