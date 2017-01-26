#!/bin/bash

set -e

apt-get update
apt-get dist-upgrade -y

apt-get install -y mysql-server

mysql -e "UPDATE user SET Host='%', plugin='mysql_native_password' WHERE User='root'; FLUSH PRIVILEGES" mysql

cat <<EOF >/etc/mysql/mysql.conf.d/mysqld_bind.cnf
[mysqld]
bind-address = 0.0.0.0

EOF

systemctl restart mysql.service
