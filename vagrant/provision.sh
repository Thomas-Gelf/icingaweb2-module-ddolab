#!/bin/bash

set -e

function unitfile()
{
    UNITFILE=/etc/systemd/system/multi-user.target.wants/$1-container.service

    case $2 in
        enable)
            cat <<EOF >"$UNITFILE"
[Unit]
Description=Container $2 service

[Service]
Type=forking
ExecStart=/usr/bin/lxc-start -d -n $2
ExecStop=/usr/bin/lxc-stop -n $2
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target

EOF
            ;;
        disable)
            rm $UNITFILE
            ;;
    esac
}

grep us.archive /etc/apt/sources.list >/dev/null && sed -i 's/us\.archive/de.archive/' /etc/apt/sources.list

apt-get update
DEBIAN_FRONTEND="noninteractive" apt-get dist-upgrade -y

dpkg -s lxc >/dev/null 2>&1 || apt-get install -y lxc lxc-templates

if [ ! -d /var/lib/lxc/mysql1.example.com ]; then
    lxc-create -n mysql1.example.com -t ubuntu -- --release=xenial
    cp -a /vagrant/vagrant/lxc/provision-mysql.sh /var/lib/lxc/mysql1.example.com/rootfs/tmp/
    cp -a /etc/apt/sources.list /var/lib/lxc/mysql1.example.com/rootfs/etc/apt/
    lxc-start -n mysql1.example.com -d
    lxc-attach -n mysql1.example.com -- /tmp/provision-mysql.sh
    unitfile "mysql1.example.com" "enable"
fi
