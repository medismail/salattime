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

	public static function runPythonScriptProcOpen(string $scriptPath, array $args, ?array &$output = null, ?int &$retval = null, string $pythonPath = 'python3', ?string &$stderrOutput = null): void {
		$cmd = array_merge([$pythonPath, $scriptPath], array_map('strval', $args));

		$descriptorSpec = [
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr (optional; can be useful for debugging)
		];

		$process = proc_open($cmd, $descriptorSpec, $pipes);

		if (!is_resource($process)) {
			throw new RuntimeException('Cannot start process');
		}

		stream_set_blocking($pipes[1], false);
		stream_set_blocking($pipes[2], false);

		$stdout = '';
		$stderr = '';
		while (!feof($pipes[1]) || !feof($pipes[2])) {
			$read = [];
			if (!feof($pipes[1])) {
				$read[] = $pipes[1];
			}
			if (!feof($pipes[2])) {
				$read[] = $pipes[2];
			}
			$write = null;
			$except = null;
			if ($read === [] || stream_select($read, $write, $except, 1) === false) {
				break;
			}
			foreach ($read as $stream) {
				$content = stream_get_contents($stream);
				if ($content === false) {
					continue;
				}
				if ($stream === $pipes[1]) {
					$stdout .= $content;
				} else {
					$stderr .= $content;
				}
			}
		}

		foreach ($pipes as $pipe) {
			fclose($pipe);
		}

		$retval = proc_close($process);
		$stderrOutput = $stderr;

		// Reproduce exec(): $output as array of lines
		$output = preg_split('/\r\n|\r|\n/', rtrim($stdout));
		if ($output === false || $output === ['']) {
			$output = [];
		}
	}

	public static function getPythonBinaryPath($memcache, $dataPath): ?string {
		return self::findBinaryPath('python3', $memcache, $dataPath) ?: self::findBinaryPath('python', $memcache, $dataPath);
	}

	public static function findBinaryPath($program, $memcache, $dataPath, $default = null) {
		if (($memcache) && ($memcache->hasKey($program))) {
			return $memcache->get($program);
		}

		$paths = ['/usr/local/sbin', '/usr/local/bin', '/usr/sbin', '/usr/bin', '/sbin', '/bin', '/opt/bin', $dataPath . '/bin'];
		$result = $default;
		$exeSniffer = new ExecutableFinder();
		// Returns null if nothing is found
		$result = $exeSniffer->find($program, $default, $paths);
		if ($result && $memcache) {
			// store the value for 5 minutes
			$memcache->set($program, $result, 300);
		}
		return $result;
	}

	public static function pythonInstalled($memcache, $dataPath): bool {
		return self::getPythonBinaryPath($memcache, $dataPath) !== null;
	}

	public static function getVersion($appManager): string {
		return $appManager->getAppVersion(self::APP_ID);
	}
}
