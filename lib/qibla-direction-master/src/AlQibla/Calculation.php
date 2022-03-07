<?php
namespace AlQibla;

class Calculation
{

    /**
     * Geographical latitude of the Ka'aba, in degrees.
     */
    const KAABA_LATITUDE = 21.422517;
    /**
     * Geographical longitude of the Ka'aba, in degrees.
     */
    const KAABA_LONGITUDE = 39.826166;


    /**
     * Calculates the Qibla direction.
     *
     * @return
     *   Returns the cardinal direction to the Ka'aba (Qibla) in degrees.
     *
     * @see: https://en.wikipedia.org/wiki/Qibla
     * @see: https://en.wikipedia.org/wiki/Cardinal_direction
     */
    public static function get($latitude, $longitude)
    {
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new \Exception('AlQibla::Calculation ::: Please pass a numeric value for the latitude and longitude.');
        }

        $A = deg2rad(self::KAABA_LONGITUDE - $longitude);
        $b = deg2rad(90 - $latitude);
        $c = deg2rad(90 - self::KAABA_LATITUDE);
        $C = rad2deg(atan2(sin($A), sin($b) * self::cot($c) - cos($b) * cos($A)));

        // Azimuth is not negative
        $C += ($C < 0) * 360;

        return $C;
    }

    /**
     * Cotangent
     *
     * Returns the cotangent of the $arg parameter.
     *
     * @param float $arg
     *   A value in radians.
     *
     * @return
     *   The cotangent of $arg.
     *
     * @see http://php.net/manual/en/function.tan.php
     */
    protected static function cot($arg)
    {
        return tan(M_PI_2 - $arg);
    }

}
