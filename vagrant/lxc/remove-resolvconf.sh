#!/bin/bash

set -e

apt-get remove -y resolvconf

sleep 2

cat <<EOF >/etc/resolv.conf
nameserver 8.8.8.8
EOF
