<?php
/**
* Get hijri date from gregorian
*
* @author   Faiz Shukri
* @date     5 Dec 2013
* @url      https://gist.github.com/faizshukri/7802735
*
* Copyright 2013 | Faiz Shukri
*
* @date     2022
* Copyright 2022 | Mohamed Ismail MEJRI
* Released under the MIT license
*/

namespace OCA\SalatTime\IslamicNetwork\Hijri;

class HijriDate{

    private $hijri;

    private $time;

    public function __construct( $time = false ){
        if(!$time) $time = time();
        $this->time = $time;
        $this->hijri = $this->GregorianToHijri($time);
    }

    public function get_date(){
        return $this->get_day_name() . ' ' . $this->hijri[1] . ' ' . $this->get_month_name() . ' ' . $this->hijri[2] . 'H';
    }

    public function get_day(){
        return $this->hijri[1];
    }

    public function get_month(){
        return $this->hijri[0];
    }

    public function get_year(){
        return $this->hijri[2];
    }

    public function get_day_name(){
        //return ($this->hijriWeekdays())[date('l',strtotime())]['en'];
         return (self::hijriWeekdays())[date('l', $this->time)]['en'];
    }

    public function get_month_name(){
           return (self::getIslamicMonths())[$this->hijri[0]]['en'];
    }

    public function get_day_special_name(){
         return (self::getHijriHolidays($this->hijri[1], $this->hijri[0]));
    }

    public function get_name_of_month($i){
        static $month  = array(
            "muharram", "safar", "rabiulawal", "rabiulakhir",
            "jamadilawal", "jamadilakhir", "rejab", "syaaban",
            "ramadhan", "syawal", "zulkaedah", "zulhijjah"
        );
        return $month[$i-1];
    }

    public static function specialDays()
    {
        $days = [];
        $days[] = ['month' => 1, 'day' => 10, 'name' => 'Ashura'];
        $days[] = ['month' => 3, 'day' => 12, 'name' => 'Mawlid al-Nabi'];
        $days[] = ['month' => 7, 'day' => 27, 'name' => 'Lailat-ul-Miraj'];
        $days[] = ['month' => 8, 'day' => 15, 'name' => 'Lailat-ul-Bara\'at'];
        $days[] = ['month' => 9, 'day' => 1, 'name' => '1st Day of Ramadan'];
        $days[] = ['month' => 9, 'day' => 21, 'name' => 'Lailat-ul-Qadr'];
        $days[] = ['month' => 9, 'day' => 23, 'name' => 'Lailat-ul-Qadr'];
        $days[] = ['month' => 9, 'day' => 25, 'name' => 'Lailat-ul-Qadr'];
        $days[] = ['month' => 9, 'day' => 27, 'name' => 'Lailat-ul-Qadr'];
        $days[] = ['month' => 9, 'day' => 29, 'name' => 'Lailat-ul-Qadr'];
        $days[] = ['month' => 10, 'day' => 1, 'name' => 'Eid-ul-Fitr'];
        $days[] = ['month' => 12, 'day' => 8, 'name' => 'Hajj'];
        $days[] = ['month' => 12, 'day' => 9, 'name' => 'Hajj'];
        $days[] = ['month' => 12, 'day' => 9, 'name' => 'Arafa'];
        $days[] = ['month' => 12, 'day' => 10, 'name' => 'Eid-ul-Adha'];
        $days[] = ['month' => 12, 'day' => 10, 'name' => 'Hajj'];
        $days[] = ['month' => 12, 'day' => 11, 'name' => 'Hajj'];
        $days[] = ['month' => 12, 'day' => 12, 'name' => 'Hajj'];
        $days[] = ['month' => 12, 'day' => 13, 'name' => 'Hajj'];

        return $days;
    }

    public static function getHijriHolidays($day, $month)
    {
        $holydays = [];
        $day = (int) $day;
        $month = (int) $month;
        foreach (self::specialDays() as $hol) {
            if ($hol['day'] == $day && $hol['month'] == $month) {
                $holydays[] = $hol['name'];
            }
        }
        return $holydays;
    }

    public static function hijriWeekdays($gDay = '')
    {
        $week = [
            'Monday' => ['en' => 'Al Athnayn', 'ar' => 'الاثنين'],
            'Tuesday' => ['en' => 'Al Thulaatha', 'ar' => 'الثلاثاء'],
            'Wednesday' => ['en' => 'Al Arbya\'a', 'ar' => 'الاربعاء'],
            'Thursday' => ['en' => 'Al Khamees', 'ar' => 'الخميس'],
            'Friday' => ['en' => 'Al Juma\'a', 'ar' => 'الجمعة'],
            'Saturday' => ['en' => 'Al Sabt', 'ar' => 'السبت'],
            'Sunday' => ['en' => 'Al Ahad', 'ar' => 'الاحد']
        ];
        if ($gDay == '') {
            return $week;
        } else {
            return $week[$gDay];
        }
    }

    public static function getGregorianMonths()
    {
        return [
            1 => ['number' => 1, 'en' => 'January'],
            2 => ['number' => 2,'en' => 'February'],
            3 => ['number' => 3,'en' => 'March'],
            4 => ['number' => 4,'en' => 'April'],
            5 => ['number' => 5,'en' => 'May'],
            6 => ['number' => 6,'en' => 'June'],
            7 => ['number' => 7,'en' => 'July'],
            8 => ['number' => 8,'en' => 'August'],
            9 => ['number' => 9,'en' => 'September'],
            10 => ['number' => 10,'en' => 'October'],
            11 => ['number' => 11,'en' => 'November'],
            12 => ['number' => 12,'en' => 'December']
        ];
    }

    public static function getIslamicMonths()
    {
        return [
            1 => ['number' => 1, 'en' => 'Muḥarram', 'ar' => 'مُحَرَّم'],
            2 => ['number' => 2,'en' => 'Ṣafar', 'ar' => 'صَفَر'],
            3 => ['number' => 3,'en' => 'Rabīʿ al-awwal', 'ar' => 'رَبيع الأوّل'],
            4 => ['number' => 4,'en' => 'Rabīʿ al-thānī', 'ar' => 'رَبيع الثاني'],
            5 => ['number' => 5,'en' => 'Jumādá al-ūlá', 'ar' => 'جُمادى الأولى'],
            6 => ['number' => 6,'en' => 'Jumādá al-ākhirah', 'ar' => 'جُمادى الآخرة'],
            7 => ['number' => 7,'en' => 'Rajab', 'ar' => 'رَجَب'],
            8 => ['number' => 8,'en' => 'Shaʿbān', 'ar' => 'شَعْبان'],
            9 => ['number' => 9,'en' => 'Ramaḍān', 'ar' => 'رَمَضان'],
            10 => ['number' => 10,'en' => 'Shawwāl', 'ar' => 'شَوّال'],
            11 => ['number' => 11,'en' => 'Dhū al-Qaʿdah', 'ar' => 'ذوالقعدة'],
            12 => ['number' => 12,'en' => 'Dhū al-Ḥijjah', 'ar' => 'ذوالحجة']
        ];
    }

    public function tune($days = null){
          if ($days) {
              $time = $this->time + (86400 * $days);
              $this->hijri = $this->GregorianToHijri($time);
          }
    }

    private function GregorianToHijri($time = null){
        if ($time === null) $time = time();
        $m = date('m', $time);
        $d = date('d', $time);
        $y = date('Y', $time);

        return $this->JDToHijri($this->GregorianToJulian($m, $d, $y));
    }

    private function HijriToGregorian($m, $d, $y){
        return jd_to_cal(CAL_GREGORIAN, $this->HijriToJD($m, $d, $y));
    }

    // Julian Day Count To Hijri
    private function JDToHijri($jd){
        $jd = $jd + 0.5 - 1948440 + 10632;
        $n  = (int)(($jd - 1) / 10631);
        $jd = $jd - 10631 * $n + 354;
        $j  = ((int)((10985 - $jd) / 5316)) *
            ((int)(50 * $jd / 17719)) +
            ((int)($jd / 5670)) *
            ((int)(43 * $jd / 15238));
        $jd = $jd - ((int)((30 - $j) / 15)) *
            ((int)((17719 * $j) / 50)) -
            ((int)($j / 16)) *
            ((int)((15238 * $j) / 43)) + 29;
        $m  = (int)(24 * $jd / 709);
        $d  = $jd - (int)(709 * $m / 24);
        $y  = 30*$n + $j - 30;

        return array($m, $d, $y);
    }

    // Hijri To Julian Day Count
    private function HijriToJD($m, $d, $y){
        return (int)((11 * $y + 3) / 30) +
            354 * $y + 30 * $m -
            (int)(($m - 1) / 2) + $d + 1948440 - 385;
    }

    private function GregorianToJulian($Month, $Day, $Year){
        if ($Month < 3)
        {
            $Month = $Month + 12;
            $Year = $Year - 1;
        }
        //return (int)($Day + (153 * $Month - 457) / 5 + 365 * $Year + ($Year / 4) - ($Year / 100) + ($Year / 400) + 1721119);
        /*Y: Year, M: Month, D: Day
          A = Y/100
          B = A/4
          C = 2-A+B
          E = 365.25x(Y+4716)
          F = 30.6001x(M+1)
          JD= C+D+E+F-1524.5*/
        return (2-(int)($Year / 100)+(int)($Year / 400)+$Day+(int)(365.25*($Year+4716))+(int)(30.6001*($Month+1))-1524.5);
    }
}
