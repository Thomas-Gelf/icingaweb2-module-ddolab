#!/bin/bash

set -e

apt-get update
DEBIAN_FRONTEND="noninteractive" apt-get dist-upgrade -y

dpkg -s mysql-server >/dev/null 2>&1 || DEBIAN_FRONTEND="noninteractive" apt-get install -y mysql-server
