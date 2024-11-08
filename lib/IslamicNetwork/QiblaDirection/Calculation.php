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

namespace OCA\SalatTime\IslamicNetwork\QiblaDirection;

class Calculation {
	/**
	 * Geographical latitude of the Ka'aba, in degrees.
	 */
	public const KAABA_LATITUDE = 21.422517;
	/**
	 * Geographical longitude of the Ka'aba, in degrees.
	 */
	public const KAABA_LONGITUDE = 39.826166;


	/**
	 * Calculates the Qibla direction.
	 *
	 * @return
	 *   Returns the cardinal direction to the Ka'aba (Qibla) in degrees.
	 *
	 * @see: https://en.wikipedia.org/wiki/Qibla
	 * @see: https://en.wikipedia.org/wiki/Cardinal_direction
	 */
	public static function get($latitude, $longitude) {
		if (!is_numeric($latitude) || !is_numeric($longitude)) {
			throw new \Exception('AlQibla::Calculation ::: Please pass a numeric value for the latitude and longitude.');
		}

		$A = deg2rad(self::KAABA_LONGITUDE - $longitude);
		$b = deg2rad(90 - $latitude);
		$c = deg2rad(90 - self::KAABA_LATITUDE);
		$C = rad2deg(atan2(sin($A), sin($b) * self::cot($c) - cos($b) * cos($A)));

		// Azimuth is not negative
		$C += ($C < 0) * 360;

		return round($C, 2);
	}

	/**
	 * Cotangent
	 *
	 * Returns the cotangent of the $arg parameter.
	 *
	 * @param float $arg
	 *   A value in radians.
	 *
	 * @return
	 *   The cotangent of $arg.
	 *
	 * @see http://php.net/manual/en/function.tan.php
	 */
	protected static function cot($arg) {
		return tan(M_PI_2 - $arg);
	}
}
