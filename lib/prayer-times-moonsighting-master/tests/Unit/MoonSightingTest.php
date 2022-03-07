<?php

namespace IslamicNetwork\MoonSighting\Tests\Unit;

use IslamicNetwork\MoonSighting\Fajr;
use IslamicNetwork\MoonSighting\Isha;
use PHPUnit\Framework\TestCase;
use DateTime;

class MoonSightingTest extends TestCase
{
    public function testDYYNorthFajr()
    {
        $date = new DateTime('24-12-2020'); // 2 days afer dec 21 in the same year
        $pt = new Fajr($date, 25.2119894);
        $this->assertEquals(2, $pt->getDyy());
        $this->assertEquals('north', $pt->hemisphere);
        $this->assertEquals(88, $pt->getMinutesBeforeSunrise());


        $date = new DateTime('24-11-2020'); // 338 days after dec 21 in the previous year
        $ptx = new Fajr($date, 25.2119894);
        $this->assertEquals(338, $ptx->getDyy());
        $this->assertEquals('north', $ptx->hemisphere);
        $this->assertEquals(87, $ptx->getMinutesBeforeSunrise());
    }

    public function testDYYNorthIsha()
    {
        $date = new DateTime('24-12-2020'); // 2 days afer dec 21 in the same year
        $pt = new Isha($date, 25.2119894);
        $this->assertEquals(2, $pt->getDyy());
        $this->assertEquals('north', $pt->hemisphere);
        $this->assertEquals(86, $pt->getMinutesAfterSunset());


        $date = new DateTime('24-11-2020'); // 338 days after dec 21 in the previous year
        $ptx = new Isha($date, 25.2119894);
        $this->assertEquals(338, $ptx->getDyy());
        $this->assertEquals('north', $ptx->hemisphere);
        $this->assertEquals(83, $ptx->getMinutesAfterSunset());
    }

    public function testDYYSouthFajr()
    {
        $date = new DateTime('24-06-2020'); // 2 days afer jun 21 in the same year
        $pt = new Fajr($date, -29.8586804);
        $this->assertEquals(2, $pt->getDyy());
        $this->assertEquals('south', $pt->hemisphere);
        $this->assertEquals(90, $pt->getMinutesBeforeSunrise());


        $date = new DateTime('24-05-2020'); // 337 days after jun 21 in the previous year
        $ptx = new Fajr($date, -29.8586804);
        $this->assertEquals(337, $ptx->getDyy());
        $this->assertEquals('south', $ptx->hemisphere);
        $this->assertEquals(89, $ptx->getMinutesBeforeSunrise());
    }

    public function testDYYSouthIsha()
    {
        $date = new DateTime('24-06-2020'); // 2 days afer jun 21 in the same year
        $pt = new Isha($date, -29.8586804);
        $this->assertEquals(2, $pt->getDyy());
        $this->assertEquals('south', $pt->hemisphere);
        $this->assertEquals(89, $pt->getMinutesAfterSunset());


        $date = new DateTime('24-05-2020'); // 337 days after jun 21 in the previous year
        $ptx = new Isha($date, -29.8586804);
        $this->assertEquals(337, $ptx->getDyy());
        $this->assertEquals('south', $ptx->hemisphere);
        $this->assertEquals(85, $ptx->getMinutesAfterSunset());
    }



}