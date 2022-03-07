<?php
namespace IslamicNetwork\MoonSighting;

use DateTime;

class Isha extends PrayerTimes
{
    public const SHAFAQ_AHMER = 'ahmer';
    public const SHAFAQ_ABYAD = 'abyad';
    public const SHAFAQ_GENERAL = 'general';
    public $shafaq;

    public function __construct(DateTime $date, float $latitude, $shafaq = self::SHAFAQ_GENERAL)
    {
        parent::__construct($date, $latitude);
        $this->setShafaq($shafaq);
    }

    public function setShafaq(string $shafaq): void
    {
        if (in_array($shafaq, [self::SHAFAQ_GENERAL, self::SHAFAQ_ABYAD, self::SHAFAQ_AHMER])) {
            $this->shafaq = $shafaq;
        }

        if ($this->shafaq === self::SHAFAQ_AHMER) {
            $this->a = 62 + 17.4 / 55.0 * abs($this->latitude);
            $this->b = 62 - 7.16 / 55.0 * abs($this->latitude);
            $this->c = 62 + 5.12 / 55.0 * abs($this->latitude);
            $this->d = 62 + 19.44 / 55.0 * abs($this->latitude);
        } else if ($this->shafaq === self::SHAFAQ_ABYAD) {
            $this->a = 75 + 25.6 / 55.0 * abs($this->latitude);
            $this->b = 75 + 7.16 / 55.0 * abs($this->latitude);
            $this->c = 75 + 36.84 / 55.0 * abs($this->latitude);
            $this->d = 75 + 81.84 / 55.0 * abs($this->latitude);
        } else {
            $this->a = 75 + 25.6 / 55.0 * abs($this->latitude);
            $this->c = 75 - 9.21 / 55.0 * abs($this->latitude);
            $this->b = 75 + 2.05 / 55.0 * abs($this->latitude);
            $this->d = 75 + 6.14 / 55.0 * abs($this->latitude);
        }
    }

    public function getMinutesAfterSunset(): float
    {
        return (int) round($this->getMinutes());
    }

}