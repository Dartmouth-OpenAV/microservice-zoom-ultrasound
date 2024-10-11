#!/bin/bash
route -n | grep UG | awk '{print $2}' > /container_to_container_ip
nohup bash -c "/checkcode.php > /var/log/checkcode" &
nohup bash -c "/playloop.php > /var/log/playloop" &
apachectl -D FOREGROUND