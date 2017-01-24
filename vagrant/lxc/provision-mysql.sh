#!/bin/bash

set -e

apt-get update
apt-get dist-upgrade -y

dpkg -s mysql-server >/dev/null 2>&1 || apt-get install -y mysql-server
