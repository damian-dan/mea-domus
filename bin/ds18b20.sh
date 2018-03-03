#!/bin/bash

ID=$1
FILE_LOCATION=/sys/bus/w1/devices/$ID/w1_slave

if [ ! -f $FILE_LOCATION ]; then
    echo "Id not found! "
    exit 1
fi

temperature=$(tail -n 1 /sys/bus/w1/devices/$ID/driver/$ID/w1_slave | grep -o "[-0-9]*$") 
echo "scale=3;" $temperature "/ 1000.0" | bc

#roomtemp=$(cat /sys/bus/w1/devices/$ID/w1_slave | grep  -E -o ".{0,0}t=.{0,5}" | cut -c 3-)
#echo "Temperature: $roomtemp"