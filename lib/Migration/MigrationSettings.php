<?php

namespace Migration;

class MigrationSettings {
	public static function migrateSettings(array $settings) {
		$migratedSettings = [];

		foreach ($settings as $key => $value) {
			// Assume settings are delimited by commas
			$migratedSettings[$key] = explode(',', $value);
		}

		return json_encode($migratedSettings);
	}
}
