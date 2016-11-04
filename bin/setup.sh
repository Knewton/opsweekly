#!/bin/bash
docker run -d -p 3306:3306 -e MYSQL_ROOT_PASSWORD=insecure -e MYSQL_DATABASES=opsweekly --name mysql docker.knewton.net/mysql:5.7
mysql -u root --password=insecure -h boot2docker opsweekly < opsweekly.sql