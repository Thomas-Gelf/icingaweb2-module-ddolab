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

export DEBIAN_FRONTEND="noninteractive"

apt-get update
apt-get dist-upgrade -y

dpkg -s lxc >/dev/null 2>&1 || apt-get install -y lxc lxc-templates

if [ ! -d /var/lib/lxc/mysql1.example.com ]; then
    lxc-create -n mysql1.example.com -t ubuntu -- --release=xenial
    lxc-start -n mysql1.example.com -d
    echo "Purging resolvconf..."
    cp -a /vagrant/vagrant/lxc/remove-resolvconf.sh /var/lib/lxc/mysql1.example.com/rootfs/tmp/
    lxc-attach -n mysql1.example.com -- /tmp/remove-resolvconf.sh
    echo "Installing MySQL server..."
    cp -a /vagrant/vagrant/lxc/provision-mysql.sh /var/lib/lxc/mysql1.example.com/rootfs/tmp/
    lxc-attach -n mysql1.example.com -- /tmp/provision-mysql.sh
    echo "Installing unit file..."
    unitfile "mysql1.example.com" "enable"
fi

if [ ! -d /var/lib/lxc/redis1.example.com ]; then
    lxc-create -n redis1.example.com -t ubuntu -- --release=xenial
    lxc-start -n redis1.example.com -d
    echo "Purging resolvconf..."
    cp -a /vagrant/vagrant/lxc/remove-resolvconf.sh /var/lib/lxc/redis1.example.com/rootfs/tmp/
    lxc-attach -n redis1.example.com -- /tmp/remove-resolvconf.sh
    echo "Installing redis server..."
    cp -a /vagrant/vagrant/lxc/provision-redis.sh /var/lib/lxc/redis1.example.com/rootfs/tmp/
    lxc-attach -n redis1.example.com -- /tmp/provision-redis.sh
    echo "Installing unit file..."
    unitfile "redis1.example.com" "enable"
fi
