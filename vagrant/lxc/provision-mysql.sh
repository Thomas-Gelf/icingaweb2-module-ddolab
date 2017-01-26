#!/bin/bash

set -e

apt-get update
apt-get dist-upgrade -y

apt-get install -y mysql-server
