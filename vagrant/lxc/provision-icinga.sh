#!/bin/bash

set -e

apt-get install -y wget

wget -O - http://packages.icinga.com/icinga.key | apt-key add -

cat <<EOF >/etc/apt/sources.list.d/icinga.list
deb http://packages.icinga.com/ubuntu icinga-xenial-snapshots main
deb-src http://packages.icinga.com/ubuntu icinga-xenial-snapshots main
EOF

apt-get update
apt-get dist-upgrade -y

dpkg -s icinga2-bin >/dev/null 2>&1 || apt-get install -y icinga2-bin icinga2-ido-mysql
