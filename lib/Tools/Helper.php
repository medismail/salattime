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

namespace OCA\SalatTime\Tools;

class Helper {
	public const APP_ID = 'salattime';

	public static function debug($msg) {
		if (is_array($msg)) {
			$msg = implode(",", $msg);
		}
		$logger = \OC::$server->getLogger();
		$logger->error($msg, ['app' => 'salattime']);
	}

	public static function log($msg, $file = "/tmp/nc.log") {
		file_put_contents($file, print_r($msg, true), FILE_APPEND);
	}

	public static function is_function_enabled($function_name) {
		if (!function_exists($function_name)) {
			return false;
		}
		$ini = \OC::$server->getIniWrapper();
		$disabled = explode(',', $ini->get('disable_functions') ?: '');
		$disabled = array_map('trim', $disabled);
		if (in_array($function_name, $disabled)) {
			return false;
		}
		$disabled = explode(',', $ini->get('suhosin.executor.func.blacklist') ?: '');
		$disabled = array_map('trim', $disabled);
		if (in_array($function_name, $disabled)) {
			return false;
		}
		return true;
	}

	public static function findBinaryPath($program, $default = null) {
		$memcache = \OC::$server->getMemCacheFactory()->createDistributed('findBinaryPath');
		if ($memcache->hasKey($program)) {
			return $memcache->get($program);
		}

		$dataPath = \OC::$server->getSystemConfig()->getValue('datadirectory');
		$paths = ['/usr/local/sbin', '/usr/local/bin', '/usr/sbin', '/usr/bin', '/sbin', '/bin', '/opt/bin', $dataPath . "/bin"];
		$result = $default;
		$exeSniffer = new ExecutableFinder();
		// Returns null if nothing is found
		$result = $exeSniffer->find($program, $default, $paths);
		if ($result) {
			// store the value for 5 minutes
			$memcache->set($program, $result, 300);
		}
		return $result;
	}

	public static function pythonInstalled(): bool {
		return (self::findBinaryPath('python') || self::findBinaryPath('python3'));
	}

	public static function getAppPath(): string {
		return \OC::$server->getAppManager()->getAppPath(self::APP_ID);
	}

	public static function getVersion(): string {
		return \OC::$server->getAppManager()->getAppVersion(self::APP_ID);
	}
}
