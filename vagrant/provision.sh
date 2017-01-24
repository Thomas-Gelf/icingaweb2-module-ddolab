#!/bin/bash

set -e

grep us.archive /etc/apt/sources.list >/dev/null && sed -i 's/us\.archive/de.archive/' /etc/apt/sources.list

apt-get update

dpkg -s lxc >/dev/null 2>&1 || apt-get install -y lxc lxc-templates
