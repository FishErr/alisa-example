#!/bin/bash

php -S localhost:8081 &
ssh -o ServerAliveInterval=60 -R 80:localhost:8081 serveo.net
kill %1

