#!/bin/bash

set -e

function unitfile()
{
    UNITFILE=/etc/systemd/system/multi-user.target.wants/$1.service

    case $2 in
        enable)
            cat <<EOF >"$UNITFILE"
[Unit]
Description=Container $1 service

[Service]
Type=forking
ExecStart=/usr/bin/lxc-start -d -n $1
ExecStop=/usr/bin/lxc-stop -n $1
RemainAfterExit=yes

[Install]
WantedBy=multi-user.target

EOF
            ;;
        disable)
            rm $UNITFILE
            ;;
    esac

    systemctl daemon-reload
}

grep us.archive /etc/apt/sources.list >/dev/null && sed -i 's/us\.archive/de.archive/' /etc/apt/sources.list

export DEBIAN_FRONTEND="noninteractive"

if [ ! -f /etc/apt/sources.list.d/icinga-snapshot.list ]; then
    wget -O - http://packages.icinga.com/icinga.key | apt-key add -
    cat <<EOF >/etc/apt/sources.list.d/icinga-snapshot.list
deb http://packages.icinga.com/ubuntu icinga-xenial-snapshots main
deb-src http://packages.icinga.com/ubuntu icinga-xenial-snapshots main

EOF
fi

apt-get update

dpkg -s icingaweb2 >/dev/null 2>&1 || apt-get install -y libapache2-mod-php7.0 icingaweb2 php-curl

if [ ! -f /etc/php/7.0/apache2/conf.d/30-date.ini ]; then
    cat <<EOF >/etc/php/7.0/apache2/conf.d/30-date.ini
[Date]
date.timezone = UTC

EOF
fi

systemctl restart apache2

if [ ! -d /usr/share/icingaweb2-modules/ ]; then
    mkdir /usr/share/icingaweb2-modules/
    pushd /usr/share/icingaweb2-modules/
    git clone https://github.com/Icinga/icingaweb2-module-businessprocess.git businessprocess
    git clone https://github.com/Icinga/icingaweb2-module-director.git director
    popd
    ln -s /vagrant/ /usr/share/icingaweb2-modules/ddolab
    mkdir /etc/icingaweb2/enabledModules/
    ln -s /usr/share/icingaweb2-modules/businessprocess/ /etc/icingaweb2/enabledModules/businessprocess
    ln -s /usr/share/icingaweb2-modules/director/ /etc/icingaweb2/enabledModules/director
    ln -s /usr/share/icingaweb2-modules/ddolab/ /etc/icingaweb2/enabledModules/ddolab
    ln -s /usr/share/icingaweb2/modules/monitoring/ /etc/icingaweb2/enabledModules/monitoring
    mkdir /etc/icingaweb2/modules/businessprocess
    mkdir /etc/icingaweb2/modules/ddolab
    mkdir /etc/icingaweb2/modules/director
    mkdir /etc/icingaweb2/modules/monitoring

    cat <<EOF >/etc/icingaweb2/authentication.ini
[icingaweb2]
backend = "db"
resource = "icingaweb2"

EOF

    cat <<EOF >/etc/icingaweb2/resources.ini
[icingaweb2]
type = "db"
db = "mysql"
host = "mysql1.lxc"
port = "3306"
dbname = "icingaweb2"
username = "icingaweb2"
password = "icingaweb2"
charset = "utf8"

[icinga2]
type = "db"
db = "mysql"
host = "mysql1.lxc"
port = "3306"
dbname = "icinga2"
username = "icinga2"
password = "icinga2"

[director]
type = "db"
db = "mysql"
host = "mysql1.lxc"
port = "3306"
dbname = "director"
username = "director"
password = "director"
charset = "utf8"

[ddolab]
type = "db"
db = "mysql"
host = "mysql1.lxc"
port = "3306"
dbname = "ddolab"
username = "ddolab"
password = "ddolab"
charset = "utf8"

EOF

    cat <<EOF >/etc/icingaweb2/config.ini
[global]
show_stacktraces = "1"
module_path = "/usr/share/icingaweb2/modules:/usr/share/icingaweb2-modules"
config_backend = "db"
config_resource = "icingaweb2"

[logging]
log = "syslog"
level = "DEBUG"
application = "icingaweb2"
facility = "user"

EOF

    cat <<EOF >/etc/icingaweb2/roles.ini
[administrators]
users = "icingaadmin"
permissions = "*"

EOF

    cat <<EOF >/etc/icingaweb2/modules/director/config.ini
[db]
resource = "director"

EOF

    cat <<EOF >/etc/icingaweb2/modules/ddolab/config.ini
[db]
resource = "ddolab"

[redis]
host = redis1.lxc

EOF

    cat <<EOF >/etc/icingaweb2/modules/monitoring/backends.ini
[icinga2]
type = "ido"
resource = "icinga2"

EOF

    cat <<EOF >/etc/icingaweb2/modules/monitoring/commandtransports.ini
[icinga2]
transport = "api"
host = "icinga1.lxc"
port = "5665"
username = "icingaweb2"
password = "icingaweb2"

EOF

    find /etc/icingaweb2/ -type d -exec chmod 2770 "{}" \;
    find /etc/icingaweb2/ -type f -exec chmod 0660 "{}" \;
fi

dpkg -s lxc >/dev/null 2>&1 || apt-get install -y lxc lxc-templates
dpkg -s dnsmasq >/dev/null 2>&1 || apt-get install -y dnsmasq

sed -i -e 's/^#\(LXC_DOMAIN=\).*$/\1lxc/' /etc/default/lxc-net
sed -i -e 's,^#\(LXC_DHCP_CONFILE=\).*$,\1/etc/lxc/dnsmasq.conf,' /etc/default/lxc-net

cat <<EOF >/etc/dnsmasq.d/serve-lxc
server=/lxc/10.0.3.1

EOF

cat <<EOF >/etc/lxc/dnsmasq.conf
server=/lxc/10.0.3.1
dhcp-host=mysql1.lxc,10.0.3.11
dhcp-host=redis1.lxc,10.0.3.12
dhcp-host=icinga1.lxc,10.0.3.13
EOF

systemctl restart dnsmasq
systemctl restart lxc-net

if [ ! -d /var/lib/lxc/mysql1.lxc/ ]; then
    lxc-create -n mysql1.lxc -t ubuntu -- --release=xenial
    lxc-start -n mysql1.lxc -d
    lxc-wait -n mysql1.lxc --state=RUNNING
    echo "Installing MySQL server..."
    cp -a /vagrant/vagrant/lxc/provision-mysql.sh /var/lib/lxc/mysql1.lxc/rootfs/tmp/
    lxc-attach -n mysql1.lxc -- /tmp/provision-mysql.sh
    echo "Installing unit file..."
    unitfile "mysql1.lxc" "enable"
fi

if [ ! -d /var/lib/lxc/redis1.lxc/ ]; then
    lxc-create -n redis1.lxc -t ubuntu -- --release=xenial
    lxc-start -n redis1.lxc -d
    lxc-wait -n redis1.lxc --state=RUNNING
    echo "Installing redis server..."
    cp -a /vagrant/vagrant/lxc/provision-redis.sh /var/lib/lxc/redis1.lxc/rootfs/tmp/
    lxc-attach -n redis1.lxc -- /tmp/provision-redis.sh
    echo "Installing unit file..."
    unitfile "redis1.lxc" "enable"
fi

if [ ! -d /var/lib/lxc/icinga1.lxc/ ]; then
    lxc-create -n icinga1.lxc -t ubuntu -- --release=xenial
    lxc-start -n icinga1.lxc -d
    lxc-wait -n mysql1.lxc --state=RUNNING
    echo "Installing Icinga server..."
    cp -a /vagrant/vagrant/lxc/provision-icinga.sh /var/lib/lxc/icinga1.lxc/rootfs/tmp/
    lxc-attach -n icinga1.lxc -- /tmp/provision-icinga.sh
    echo "Installing unit file..."
    unitfile "icinga1.lxc" "enable"
fi
