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
    if len(args) not in [3, 4]:
        print('USAGE: {} latitude longitude [yyyy-mm-ddThh:mm:ssZ]'.format(args[0]))
        sys.exit(1)
    latitude = float(args[1])
    longitude = float(args[2])
    if len(args) == 4:
        time = astronomy.Time.Parse(args[3])
    else:
        time = astronomy.Time.Now()
    observer = astronomy.Observer(latitude, longitude)
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
    sunrise  = SearchRiseSet(Body.Sun,  observer, Direction.Rise, time, 300)
    sunset   = SearchRiseSet(Body.Sun,  observer, Direction.Set,  time, 300)
    moonrise = SearchRiseSet(Body.Moon, observer, Direction.Rise, time, 300)
    moonset  = SearchRiseSet(Body.Moon, observer, Direction.Set,  time, 300)

    # Calculate the Moon's ecliptic phase angle,
    # which ranges from 0 to 360 degrees.
    #   0 degrees = new moon,
    #  90 degrees = first quarter,
    # 180 degrees = full moon,
    # 270 degrees = third quarter.
    phase = astronomy.MoonPhase(time)
    print("{} : Moon's ecliptic phase angle = {:0.3f} degrees.".format(time, phase))

    # Calculate the fraction of the Moon's disc
    # that appears illuminated, as seen from the Earth.
    illum = astronomy.Illumination(astronomy.Body.Moon, time)
    print("Moon's illuminated fraction: {:0.2f}%.".format(100.0 * illum.phase_fraction))

    mq = astronomy.SearchMoonQuarter(time)
    #print('{} : {}'.format(mq.time, QuarterName(mq.quarter)))
    print('Moon Phase: {}'.format(QuarterName(mq.quarter)))

    PrintEvent('search',   time)
    PrintEvent('sunrise',  sunrise)
    PrintEvent('sunset',   sunset)
    PrintEvent('moonrise', moonrise)
    PrintEvent('moonset',  moonset)

    #print('BODY           AZ      ALT')
    body_list = [
        Body.Sun, Body.Moon
    ]
    for body in body_list:
        equ_ofdate = Equator(body, time, observer, ofdate=True, aberration=True)
        hor = Horizon(time, observer, equ_ofdate.ra, equ_ofdate.dec, Refraction.Normal)
        print('{:<8} position: AZ: {:8.2f} ALT: {:8.2f}'.format(body.name, hor.azimuth, hor.altitude))

    return 0

if __name__ == '__main__':
    sys.exit(main(sys.argv))
