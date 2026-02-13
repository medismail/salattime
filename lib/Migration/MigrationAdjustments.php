<?php

namespace Migration;

class MigrationAdjustments {
    public function migrate($commaDelimitedString) {
        // Split the string by commas
        $items = explode(',', $commaDelimitedString);

        // Convert array to JSON
        $jsonData = json_encode(array_map('trim', $items));

        // Here you would implement the logic to store $jsonData
        // For demonstration, we are just returning it
        return $jsonData;
    }
}
