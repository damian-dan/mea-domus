# mea-domus
Smart box using RPi to control temperature and in general ... surveillancek
# Setup
## Prerequirements
- PHP  7
- composer
- /usr/local/bin/gpio
 
## Installation steps

```
cd /home/pi
```
```
git clone https://github.com/dndam/mea-domus.git
composer install
chmod +x /home/pi/mea-domus/bin/run
chmod -R pi:pi /home/pi/mea-domus
chmod 777 /home/pi/mea-domus/data
```

We have two scripts:
1. To play with the relay itself
2. To read and write data within the common storage. This will enfore in the end:
    a. graphics
    b. Show all temperatures. Create a menu with all possible graphics, for today, for outside etc...
    c. We need to add a login to it
    This could be done within Express or another web framework
    
# ToDo
1. retrieve CPU and GPU temperature, with a trigger for more than 50o C
2. Introduce http2 features
3. Add an event/subscriber approach, in which I should be able to start my washing machine
4. websocket tu update relay state
5. Add installer.sh

# Mobile app
- list of locations (e.g. Bals, Craiova)
- Login/Logout
