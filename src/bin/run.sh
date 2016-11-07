#!/bin/bash
docker stop opsweekly
docker rm -f opsweekly
docker build . -t opsweekly
docker run  -d  -p 80:80 --link mysql:mysql --name opsweekly opsweekly