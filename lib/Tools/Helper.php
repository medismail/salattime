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

use RuntimeException;

class Helper {
	public const APP_ID = 'salattime';

	public static function runPythonScriptProcOpen(string $scriptPath, array $args, array &$output = null, int &$retval = null): void {
		$cmd = array_merge(['python3', $scriptPath], array_map('strval', $args));

		$descriptorSpec = [
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr (optional; can be useful for debugging)
		];

		$process = proc_open($cmd, $descriptorSpec, $pipes);

		if (!is_resource($process)) {
			throw new RuntimeException("Cannot start process");
		}

		// Read STDOUT
		$stdout = stream_get_contents($pipes[1]);
		// Optionally, read STDERR
		$stderr = stream_get_contents($pipes[2]);

		fclose($pipes[1]);
		fclose($pipes[2]);

		$retval = proc_close($process);

		// Reproduce exec(): $output as array of lines
		$output = $stdout !== false ? preg_split('/\r\n|\r|\n/', rtrim($stdout)) : [];
	}

	public static function findBinaryPath($program, $memcache, $dataPath, $default = null) {
		if (($memcache) && ($memcache->hasKey($program))) {
			return $memcache->get($program);
		}

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

	public static function pythonInstalled($memcache, $dataPath): bool {
		return (self::findBinaryPath('python', $memcache, $dataPath) || self::findBinaryPath('python3', $memcache, $dataPath));
	}

	public static function getVersion($appManager): string {
		return $appManager->getAppVersion(self::APP_ID);
	}
}
