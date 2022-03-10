<?php
namespace IslamicNetwork\MoonSighting;

use DateTime;

class PrayerTimes
{
    public $latitude;
    public $date;
    public $a;
    public $b;
    public $c;
    public $d;
    public $dyy;
    public $hemisphere;
    public const DYY_NORTH_0 = "12-21";
    public const DYY_SOUTH_0 = "06-21";

    public function __construct(DateTime $date, float $latitude)
    {
        $this->date = $date;
        $this->latitude = $latitude;
        $this->getDyy();
    }

    public function getDyy(): int
    {
        $year = $this->date->format('Y');
        if ($this->latitude > 0) { // Northern Hemisphere
            $this->hemisphere = 'north';
            $dateDyyZero = DateTime::createFromFormat('m-d-Y', self::DYY_NORTH_0 . '-' . $year);
        } else { // Southern Hemisphere
            $this->hemisphere = 'south';
            $dateDyyZero = DateTime::createFromFormat('m-d-Y', self::DYY_SOUTH_0 . '-' . $year);
        }

        $diff = $dateDyyZero->diff($this->date)->format('%r%a');

        if ($diff > 0) {
            $this->dyy = $diff;
        } else {
            $this->dyy = 365 + $diff;
        }

        return $this->dyy;
    }

    protected function getMinutes()
    {
        if ($this->dyy < 91)
            return $this->a + ($this->b - $this->a) / 91 * $this->dyy; // '91 DAYS SPAN
        else if ($this->dyy < 137)
            return $this->b + ($this->c - $this->b) / 46 * ( $this->dyy - 91 ); // '46 DAYS SPAN
        else if ($this->dyy< 183)
            return $this->c + ($this->d - $this->c) / 46 * ( $this->dyy - 137 ); // '46 DAYS SPAN
        else if ($this->dyy < 229)
            return $this->d + ($this->c - $this->d) / 46 * ( $this->dyy - 183 ); // '46 DAYS SPAN
        else if ($this->dyy < 275)
            return $this->c + ($this->b - $this->c) / 46 * ( $this->dyy - 229 ); // '46 DAYS SPAN
        else if ($this->dyy >= 275)
            return $this->b + ($this->a - $this->b) / 91 * ( $this->dyy - 275 ); // ' 91 DAYS SPAN
    }




}