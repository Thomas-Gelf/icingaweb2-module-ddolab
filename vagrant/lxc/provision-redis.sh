#!/bin/bash

set -e

apt-get update

apt-get install -y redis-server

sed -i -e 's/^\(bind \).*$/\10.0.0.0/' /etc/redis/redis.conf

systemctl restart redis-server
