<?php
namespace Tests\Unit;

class DirectionTest extends \PHPUnit\Framework\TestCase
{
    public function testDirection()
    {   
        // Calculation from London
        $answer = 118.98724271029;
        $lat = 51.5073509;
        $lng = -0.1277583; 
        $this->assertEquals(\AlQibla\Calculation::get($lat, $lng), $answer);
    }
}
