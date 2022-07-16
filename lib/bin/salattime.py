#!/usr/bin/env python3
#
#
#    Example Python program for Astronomy Engine:
#    https://github.com/cosinekitty/astronomy
#
#    This program calculates sunrise, sunset, moonrise, and moonset
#    times for an observer at a given latitude and longitude.
#
#    To execute, run the command:

#    python3 salattime.py latitude longitude [yyyy-mm-ddThh:mm:ssZ]
#
import sys
sys.dont_write_bytecode = True

import re
import astronomy
from astronomy import Body, Direction, SearchRiseSet, Refraction, Equator, Horizon


def ParseArgs(args):
    if len(args) not in [4, 5]:
        print('USAGE: {} latitude longitude elevation [yyyy-mm-ddThh:mm:ssZ]'.format(args[0]))
        sys.exit(1)
    latitude = float(args[1])
    longitude = float(args[2])
    elevation = float(args[3])
    if len(args) == 5:
        time = astronomy.Time.Parse(args[4])
    else:
        time = astronomy.Time.Now()
    observer = astronomy.Observer(latitude, longitude, elevation)
    return (observer, time)

def QuarterName(quarter):
    return [
        'New Moon',
        'First Quarter',
        'Full Moon',
        'Third Quarter'
    ][quarter]

def PrintEvent(name, time):
    if time is None:
        raise Exception('Failure to calculate ' + name)
    print('{:<8s} : {}'.format(name, time))

def main(args):
    observer, time = ParseArgs(args)
    daytime = str(time).split('T')[0].split('-')
    timers = astronomy.Time.Make(int(daytime[0]), int(daytime[1]), int(daytime[2]),0,0,0)
    sunrise  = SearchRiseSet(Body.Sun,  observer, Direction.Rise, timers, 1)
    sunset   = SearchRiseSet(Body.Sun,  observer, Direction.Set,  timers, 1)
    moonrise = SearchRiseSet(Body.Moon, observer, Direction.Rise, timers, 1)
    moonset  = SearchRiseSet(Body.Moon, observer, Direction.Set,  timers, 1)

    PrintEvent('search',   time)
    print(sunrise)
    print(sunset)
    print(moonrise)
    print(moonset)

    mq = astronomy.SearchMoonQuarter(time)
    #print('{} : {}'.format(mq.time, QuarterName(mq.quarter)))
    #print('Moon Phase: {}'.format(QuarterName(mq.quarter-1 if mq.quarter>0 else 3)))
    print(QuarterName(mq.quarter-1 if mq.quarter>0 else 3))

    # Calculate the Moon's ecliptic phase angle,
    # which ranges from 0 to 360 degrees.
    #   0 degrees = new moon,
    #  90 degrees = first quarter,
    # 180 degrees = full moon,
    # 270 degrees = third quarter.
    phase = astronomy.MoonPhase(time)
    #print("Moon's ecliptic phase angle: {:0.3f} degrees.".format(phase))
    print("{:0.3f}".format(phase))

    # Calculate the fraction of the Moon's disc
    # that appears illuminated, as seen from the Earth.
    illum = astronomy.Illumination(astronomy.Body.Moon, time)
    #print("Moon's illuminated fraction: {:0.2f}%.".format(100.0 * illum.phase_fraction))
    print("{:0.2f}".format(100.0 * illum.phase_fraction))

    #print('BODY           AZ      ALT')
    body_list = [
        Body.Sun, Body.Moon
    ]
    for body in body_list:
        equ_ofdate = Equator(body, time, observer, ofdate=True, aberration=True)
        hor = Horizon(time, observer, equ_ofdate.ra, equ_ofdate.dec, Refraction.Normal)
        #print('{:<8} position Azimuth: {:8.2f}'.format(body.name, hor.azimuth))
        #print('{:<8} position Altitude: {:8.2f}'.format(body.name, hor.altitude))
        print('{:0.2f}'.format(hor.azimuth))
        print('{:0.2f}'.format(hor.altitude))

    return 0

if __name__ == '__main__':
    sys.exit(main(sys.argv))
