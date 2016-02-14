#!/bin/sh
 
ps aux | grep '[m]yScript.php'
if [ $? -ne 0 ]
then
    php /path/to/myScript.php
fi


# run it as 
# * * * * * /bin/sh run_an_php_script_if_its_not_already_running.sh >> /var/log/thermal.`date +\%Y-\%m-\%d`.log
# add Logging for this wrapper