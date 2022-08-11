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
    if len(args) not in [5, 6]:
        print('USAGE: {} latitude longitude elevation timezone [yyyy-mm-ddThh:mm:ssZ]'.format(args[0]))
        sys.exit(1)
    latitude = float(args[1])
    longitude = float(args[2])
    elevation = float(args[3])
    timezone = float(args[4])/3600
    if len(args) == 6:
        time = astronomy.Time.Parse(args[5])
    else:
        time = astronomy.Time.Now()
    observer = astronomy.Observer(latitude, longitude, elevation)
    return (observer, time, timezone)

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
    observer, time, timezone = ParseArgs(args)
    curhour = int(str(time).split('T')[1].split(':')[0])
    dayoffset = 0
    if timezone > 12:
        dayoffset = -1
        timezone = timezone-25
    midhour = int(-timezone)
    if timezone > 0:
        midhour = midhour+24
    if curhour < midhour:
        dayoffset = dayoffset-1
    ytime = time.AddDays(dayoffset)
    daytime = str(ytime).split('T')[0].split('-')
    midTime = astronomy.Time.Make(int(daytime[0]), int(daytime[1]), int(daytime[2]), midhour,0,0)
    sunrise  = SearchRiseSet(Body.Sun,  observer, Direction.Rise, midTime, 1)
    sunset   = SearchRiseSet(Body.Sun,  observer, Direction.Set,  midTime, 1)
    moonrise = SearchRiseSet(Body.Moon, observer, Direction.Rise, midTime, 1)
    moonset  = SearchRiseSet(Body.Moon, observer, Direction.Set,  midTime, 1)

    PrintEvent('search',   time)
    print(sunrise)
    print(sunset)
    timeEnd = midTime.AddDays(1)
    if moonrise <= timeEnd:
        print(moonrise)
    else:
        print('')
    if moonset <= timeEnd:
        print(moonset)
    else:
        print('')

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
