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

namespace OCA\SalatTime\Service;

use OCP\IUserManager;
use OCP\IConfig;
use OCP\IUser;
use OCA\SalatTime\AppInfo\Application;

class ConfigService {
	private IConfig $config;
	private IUserManager $userManager;

	public function __construct(IConfig $config, IUserManager $userManager) {
		$this->config = $config;
		$this->userManager = $userManager;
	}

	public function getUserValue($userId, $key) {
		return $this->config->getUserValue($userId, Application::APP_ID, $key);
	}

	public function setUserValue($userId, $key, $value) {
		if (is_array($value)) {
			//$p_value = implode(":", $value);
			$p_value = json_encode($value);
			if ($p_value === false) {
				throw new \InvalidArgumentException('Could not encode Salat Time user config');
			}
			$value = $p_value;
		}
		$this->config->setUserValue($userId, Application::APP_ID, $key, $value);
	}

	public function getSystemValue($key) {
		return $this->config->getSystemConfig()->getValue($key);
	}

	public function getSettingsValue($userId) {
		$settings = $this->config->getUserValue($userId, Application::APP_ID, 'settings');
		$p_settings = json_decode($settings, true);
		$needsSave = false;
		if (!is_array($p_settings)) {
			$p_settings = explode(":", $settings);
			if (count($p_settings) > 2) {
				$ret['latitude'] = $p_settings[0];
				if ($ret['latitude'] == "") {
					$ret['latitude'] = 21.3890824;
				}
				$ret['longitude'] = $p_settings[1];
				if ($ret['longitude'] == "") {
					$ret['longitude'] = 39.8579118;
				}
				$ret['timezone'] = $p_settings[2];
				if ($ret['timezone'] == "") {
					$ret['timezone'] = '+0300';
				}
				if (isset($p_settings[3]) && ($p_settings[3] != "")) {
					$ret['elevation'] = $p_settings[3];
				} else {
					$ret['elevation'] = 0.0;
				}
				if (isset($p_settings[4]) && ($p_settings[4] != "")) {
					$ret['method'] = $p_settings[4];
				} else {
					$ret['method'] = 'MWL';
				}
				if (isset($p_settings[5]) && ($p_settings[5] != "")) {
					$ret['format_12_24'] = $p_settings[5];
				} else {
					$ret['format_12_24'] = '12h';
				}
				if (isset($p_settings[6]) && ($p_settings[6] != "")) {
					$ret['city'] = $p_settings[6];
				} else {
					$ret['city'] = '';
				}
			} else {
				$ret['latitude'] = 21.3890824;
				$ret['longitude'] = 39.8579118;
				$ret['timezone'] = '+0300';
				$ret['elevation'] = null;
				$ret['method'] = 'MWL';
				$ret['format_12_24'] = '12h';
				$ret['city'] = 'Makkah';
			}
			$needsSave = true;
		} else {
			$ret = (array) $p_settings;
		}
		$ret = $this->normalizeSettings($ret);
		if ($needsSave || $ret !== (array) $p_settings) {
			$this->setUserValue($userId, 'settings', $ret);
		}
		return $ret;
	}

	public function getAdjustmentsValue($userId) {
		$v_adjustments = $this->config->getUserValue($userId, Application::APP_ID, 'adjustments');
		$adjustments = json_decode($v_adjustments, true);
		$needsSave = false;
		if (!is_array($adjustments)) {
			$adjustments = explode(",", $v_adjustments);
			if (count($adjustments) == 7) {
				$ret['Day'] = $adjustments[0];
				$ret['Fajr'] = $adjustments[1];
				$ret['Dhuhr'] = $adjustments[2];
				$ret['Asr'] = $adjustments[3];
				$ret['Maghrib'] = $adjustments[4];
				$ret['Isha'] = $adjustments[5];
				$ret['NMA'] = $adjustments[6];
			} elseif (count($adjustments) == 6) {
				$ret['Day'] = $adjustments[0];
				$ret['Fajr'] = $adjustments[1];
				$ret['Dhuhr'] = $adjustments[2];
				$ret['Asr'] = $adjustments[3];
				$ret['Maghrib'] = $adjustments[4];
				$ret['Isha'] = $adjustments[5];
				$ret['NMA'] = 0;
			} else {
				$ret['Day'] = 0;
				$ret['Fajr'] = 0;
				$ret['Dhuhr'] = 0;
				$ret['Asr'] = 0;
				$ret['Maghrib'] = 0;
				$ret['Isha'] = 0;
				$ret['NMA'] = 0;
			}
			$needsSave = true;
		} else {
			$ret = (array) $adjustments;
		}
		$ret = $this->normalizeAdjustments($ret);
		if ($needsSave || $ret !== (array) $adjustments) {
			$this->setUserValue($userId, 'adjustments', $ret);
		}
		$this->setUserAutoHijriDate($userId, $this->isAutoHijriEnabled($ret));
		return $ret;
	}

	public function setCityValue($userId, $city) {
		$settings = $this->getSettingsValue($userId);
		$settings['city'] = $city;
		$this->setUserValue($userId, 'settings', $settings);
	}

	public function getUserTimeZone($userId) {
		return $this->config->getUserValue($userId, 'core', 'timezone');
	}

	public function setUserNotification($userId) {
		$this->config->setUserValue($userId, Application::APP_ID, 'notification', 'true');
	}

	public function unsetUserNotification($userId) {
		$this->config->setUserValue($userId, Application::APP_ID, 'notification', 'false');
	}

	public function getUserNotification($userId) {
		return $this->config->getUserValue($userId, Application::APP_ID, 'notification');
	}

	public function getAllUsersNotification() {
		return $this->config->getUsersForUserValue(Application::APP_ID, 'notification', 'true');
	}

	public function setUserAutoHijriDate($userId, bool $enabled) {
		$this->config->setUserValue($userId, Application::APP_ID, 'auto_hijri', $enabled ? 'true' : 'false');
	}

	public function getAllUserAutoHijriDate(): array {
		$users = $this->config->getUsersForUserValue(Application::APP_ID, 'auto_hijri', 'true');
		if ($this->config->getAppValue(Application::APP_ID, 'auto_hijri_migrated', 'false') === 'true') {
			return $users;
		}

		$legacyUsers = $this->getUsersWithConfigMatching('adjustments', ['NMA' => '!0']);
		foreach ($legacyUsers as $userId) {
			$this->setUserAutoHijriDate($userId, true);
		}
		$this->config->setAppValue(Application::APP_ID, 'auto_hijri_migrated', 'true');

		return array_values(array_unique(array_merge($users, $legacyUsers)));
	}

	public function setUserCalendar($userId) {
		$this->config->setUserValue($userId, Application::APP_ID, 'calendar', 'true');
	}

	public function unsetUserCalendar($userId) {
		$this->config->setUserValue($userId, Application::APP_ID, 'calendar', 'false');
	}

	public function getUserCalendar($userId) {
		return $this->config->getUserValue($userId, Application::APP_ID, 'calendar');
	}

	/**
	 * Get all users where the config value matches a pattern match {key:value}
	 *
	 * @param string $configKey
	 * @param array $patternParts Pattern like {key:value}
	 * @return IUser[]
	 */
	public function getUsersWithConfigMatching(string $configKey, array $patternParts): array {
		$users = $this->userManager->search('');
		$result = [];

		foreach ($users as $user) {
			$userId = $user->getUID();
			$value = $this->config->getUserValue($userId, Application::APP_ID, $configKey, null);

			if ($value !== null && $value !== '') {
				$valueParts = json_decode($value, true);

				if (is_array($valueParts) && $this->matchesPattern($valueParts, $patternParts)) {
					$result[] = $userId;
				}
			}
		}

		return $result;
	}

	/**
	 * Compare valueParts against patternParts
	 */
	private function matchesPattern(array $valueParts, array $patternParts): bool {
		foreach ($patternParts as $key => $patternValue) {
			if (!array_key_exists($key, $valueParts)) {
				return false;
			}

			if (!$this->matchesPatternValue($valueParts[$key], $patternValue)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Compare a single decoded config value against a pattern value.
	 */
	private function matchesPatternValue($value, $patternValue): bool {
		if ($patternValue === '*') {
			return true;
		}

		if (is_string($patternValue) && substr($patternValue, 0, 1) === '!') {
			return (string)$value !== substr($patternValue, 1);
		}

		return (string)$value === (string)$patternValue;
	}

	private function normalizeSettings(array $settings): array {
		$defaults = [
			'latitude' => 21.3890824,
			'longitude' => 39.8579118,
			'timezone' => '+0300',
			'elevation' => 0.0,
			'method' => 'MWL',
			'format_12_24' => '12h',
			'city' => 'Makkah',
		];

		$ret = array_merge($defaults, array_intersect_key($settings, $defaults));
		foreach ($defaults as $key => $default) {
			if ($key === 'city') {
				continue;
			}
			if ($ret[$key] === null || $ret[$key] === '') {
				$ret[$key] = $default;
			}
		}

		return $ret;
	}

	private function normalizeAdjustments(array $adjustments): array {
		$defaults = [
			'Day' => 0,
			'Fajr' => 0,
			'Dhuhr' => 0,
			'Asr' => 0,
			'Maghrib' => 0,
			'Isha' => 0,
			'NMA' => 0,
		];

		$ret = array_merge($defaults, array_intersect_key($adjustments, $defaults));
		foreach ($defaults as $key => $default) {
			if ($ret[$key] === null || $ret[$key] === '') {
				$ret[$key] = $default;
			}
		}

		return $ret;
	}

	private function isAutoHijriEnabled(array $adjustments): bool {
		return isset($adjustments['NMA']) && (string)$adjustments['NMA'] !== '0' && $adjustments['NMA'] !== '';
	}
}
