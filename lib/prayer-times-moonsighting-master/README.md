[![CircleCI](https://circleci.com/gh/islamic-network/prayer-times-moonsighting.svg?style=shield)](https://circleci.com/gh/islamic-network/prayer-times-moonsighting)
[![Releases](https://img.shields.io/github/v/release/islamic-network/prayer-times-moonsighting)](https://github.com/islamic-network/prayer-times-moonsighting/releases)
![](https://img.shields.io/packagist/dt/islamic-network/prayer-times-moonsighting.svg)

# PHP Library to Calculate Fajr and Isha Timings per MoonSighting.com
This library has been written for the AlAdhan.com API @ https://aladhan.com/prayer-times-api and is included in the main prayer times library @ https://github.com/islamic-network/prayer-times.

## Requirements
* PHP 7.3+

## Install
```php
composer install islamic-network/prayer-times-moonsighting
```

## Usage
To calculate Fajr minutes before sunrise:

```php
use IslamicNetwork\MoonSighting\Fajr;
use DateTime;

$date = new DateTime('24-12-2020');
$pt = new Fajr($date, 25.2119894);
$pt->getMinutesBeforeSunrise(); // 88 minutes
```

To calculate Isha minutes after sunset:

```php
use IslamicNetwork\MoonSighting\Fajr;
use DateTime;

$date = new DateTime('24-12-2020');
$pt = new Isha($date, 25.2119894, 'general'); // The third parameter is shafaq, acceptable values for which are 'general', 'ahmer', 'abyad'.
$pt->getMinutesAfterSunset(); // 86 minutes
```

## Tests
To run unit tests, from the root of this repository execute:

```php
vendor/bin/phpunit tests/Unit/
```
## Credits

Syed Khalid Shaukat, who has done the research for this method of computing timings for higher latitude areas. For more information about the calculation, 
please see the Fajr and Isha booklet @ https://github.com/islamic-network/prayer-times-moonsighting/blob/master/booklet-fajr-isha.pdf and visit https://www.moonsighting.com/.
