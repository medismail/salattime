#!/usr/bin/env python3

import sys
sys.dont_write_bytecode = True
import astronomy
from datetime import datetime, timedelta, timezone
from astronomy import Body, Direction, SearchRiseSet, SearchMoonPhase
import math

def ParseArgs(args):
    if len(args) not in [5, 6]:
        print('USAGE: {} latitude longitude elevation [yyyy-mm-ddThh:mm:ssZ] hijriday'.format(args[0]))
        sys.exit(1)
    latitude = float(args[1])
    longitude = float(args[2])
    elevation = float(args[3])
    if len(args) == 6:
        hijriday = int(args[5])
        time = astronomy.Time.Parse(args[4]).AddDays(-4 - hijriday)
    else:
        hijriday = int(args[4])
        time = astronomy.Time.Now().AddDays(-4 - hijriday)
    nextmonth = hijriday == 30
    observer = astronomy.Observer(latitude, longitude, elevation)
    return (observer, time, nextmonth)

def find_next_new_moon(time):
    # Find the next new moon
    next_new_moon = SearchMoonPhase(0, time, 40) #ephem.next_new_moon(observer.date)
    return next_new_moon

def find_sunset(observer, time):
    # Calculate the sunset time
    sunset = SearchRiseSet(Body.Sun, observer, Direction.Set, time, 1)    #sunset = observer.next_setting(ephem.Sun())
    return sunset

def calculate_time_difference(new_moon, sunset):
    # Convert both times to datetime objects
    new_moon_dt = new_moon.Utc()
    sunset_dt = sunset.Utc()
    # Calculate the difference in hours
    time_difference = (sunset_dt - new_moon_dt).total_seconds() / 3600
    return time_difference

def get_first_day(time_difference, date_t):
    # Parse the input date string
    date = date_t.Utc()

    if time_difference > 14:
        # Add one days
        new_date = date + timedelta(days=1)
    else:
        # Add two days
        new_date = date + timedelta(days=2)

    # Format the new date as a string
    new_date_str = new_date.strftime('%Y-%m-%dT%H:%M:%S.%fZ')

    # Set the time to 00:00:00.000
    new_date_str = new_date_str[:11] + '00:00:00.000Z'

    return new_date_str

def get_adjust(date_t, date_str):
    # Parse the input date string
    new_date_str = date_t.Utc().strftime('%Y-%m-%dT%H:%M:%S.%fZ')
    e_date = datetime.strptime(date_str, '%Y-%m-%dT%H:%M:%S.%fZ')
    s_date = datetime.strptime(new_date_str, '%Y-%m-%dT%H:%M:%S.%fZ')

    # Calculate the difference in hours
    day_difference = (e_date - s_date).total_seconds() / 86400
    return day_difference

def main(args):
    # Define the observer's location (latitude, longitude, elevation)
    #observer = ephem.Observer()
    #observer.lat = '37.7749'  # Example: Latitude of San Francisco
    #observer.lon = '-122.4194'  # Example: Longitude of San Francisco
    #observer.elevation = 52  # Example: Elevation of San Francisco in meters
    observer, time, nextmonth = ParseArgs(args)

    # Find the next new moon
    next_new_moon = find_next_new_moon(time)

    # Find the sunset on the same day as the new moon
    sunset = find_sunset(observer, next_new_moon)

    # Calculate the time difference in hours
    time_difference = calculate_time_difference(next_new_moon, sunset)

    # Get the first day of the month
    first_day = get_first_day(time_difference, sunset)
    if nextmonth:
        next_new_moon = find_next_new_moon(time.AddDays(5))
        sunset = find_sunset(observer, next_new_moon)
        time_difference = calculate_time_difference(next_new_moon, sunset)
        next_first_day = get_first_day(time_difference, sunset)
        #print(f"Next First day: {next_first_day}")
        # Get days difference
        day_difference = math.ceil(get_adjust(time.AddDays(35), next_first_day))
        if day_difference > -1 or day_difference < -4:
            day_difference = math.ceil(get_adjust(time, first_day)) - 5
    else:
        day_difference = math.ceil(get_adjust(time, first_day)) - 5
    # Print the results
    #print(f"Next New Moon: {next_new_moon}")
    #print(f"Next Sunset to new moon: {sunset}")
    #print(f"Time Difference (hours): {time_difference}")
    #print(f"First day: {first_day}")
    if day_difference < 4 and day_difference > -5:
        print(-day_difference)
    else:
        print('0')

if __name__ == "__main__":
    sys.exit(main(sys.argv))
