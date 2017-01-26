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

sed -i -e 's/^#\(LXC_DOMAIN=\).*$/\1lxc/' /etc/default/lxc-net
grep -q 10.0.3.1 /etc/resolvconf/resolv.conf.d/head || echo 'nameserver 10.0.3.1' >> /etc/resolvconf/resolv.conf.d/head
systemctl restart lxc-net.service
resolvconf -u

if [ ! -d /var/lib/lxc/mysql1.lxc ]; then
    lxc-create -n mysql1.lxc -t ubuntu -- --release=xenial
    lxc-start -n mysql1.lxc -d
    lxc-wait -n mysql1.lxc --state=RUNNING
    echo "Removing resolvconf..."
    cp -a /vagrant/vagrant/lxc/remove-resolvconf.sh /var/lib/lxc/mysql1.lxc/rootfs/tmp/
    lxc-attach -n mysql1.lxc -- /tmp/remove-resolvconf.sh
    echo "Installing MySQL server..."
    cp -a /vagrant/vagrant/lxc/provision-mysql.sh /var/lib/lxc/mysql1.lxc/rootfs/tmp/
    lxc-attach -n mysql1.lxc -- /tmp/provision-mysql.sh
    echo "Installing unit file..."
    unitfile "mysql1.lxc" "enable"
fi

if [ ! -d /var/lib/lxc/redis1.lxc ]; then
    lxc-create -n redis1.lxc -t ubuntu -- --release=xenial
    lxc-start -n redis1.lxc -d
    lxc-wait -n redis1.lxc --state=RUNNING
    echo "Removing resolvconf..."
    cp -a /vagrant/vagrant/lxc/remove-resolvconf.sh /var/lib/lxc/redis1.lxc/rootfs/tmp/
    lxc-attach -n redis1.lxc -- /tmp/remove-resolvconf.sh
    echo "Installing redis server..."
    cp -a /vagrant/vagrant/lxc/provision-redis.sh /var/lib/lxc/redis1.lxc/rootfs/tmp/
    lxc-attach -n redis1.lxc -- /tmp/provision-redis.sh
    echo "Installing unit file..."
    unitfile "redis1.lxc" "enable"
fi

if [ ! -d /var/lib/lxc/icinga1.lxc ]; then
    lxc-create -n icinga1.lxc -t ubuntu -- --release=xenial
    lxc-start -n icinga1.lxc -d
    lxc-wait -n mysql1.lxc --state=RUNNING
    echo "Removing resolvconf..."
    cp -a /vagrant/vagrant/lxc/remove-resolvconf.sh /var/lib/lxc/icinga1.lxc/rootfs/tmp/
    lxc-attach -n icinga1.lxc -- /tmp/remove-resolvconf.sh
    echo "Installing Icinga server..."
    cp -a /vagrant/vagrant/lxc/provision-icinga.sh /var/lib/lxc/icinga1.lxc/rootfs/tmp/
    lxc-attach -n icinga1.lxc -- /tmp/provision-icinga.sh
    echo "Installing unit file..."
    unitfile "icinga1.lxc" "enable"
fi
