<?php
namespace IslamicNetwork\MoonSighting;

use DateTime;

class Fajr extends PrayerTimes
{

    public function __construct(DateTime $date, float $latitude)
    {
        parent::__construct($date, $latitude);

        $this->a = 75 + 28.65 / 55 * abs($this->latitude);
        $this->b = 75 + 19.44 / 55 * abs($this->latitude);
        $this->c = 75+ 32.74 / 55 * abs($this->latitude);
        $this->d = 75 + 48.1 / 55 * abs($this->latitude);
    }

    public function getMinutesBeforeSunrise(): float
    {
        return round($this->getMinutes());
    }

}