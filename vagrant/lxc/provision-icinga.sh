#!/bin/bash

set -e

apt-get update

apt-get install -y wget

wget -O - http://packages.icinga.com/icinga.key | apt-key add -

cat <<EOF >/etc/apt/sources.list.d/icinga.list
deb http://packages.icinga.com/ubuntu icinga-xenial-snapshots main
deb-src http://packages.icinga.com/ubuntu icinga-xenial-snapshots main
EOF

apt-get update

apt-get install -y icinga2-bin icinga2-ido-mysql icinga2

icinga2 api setup

cat <<EOF >/etc/icinga2/conf.d/ddo.conf
template Host "dummy-host" {
  check_command = "dummy"
  address = "127.0.0.1"
  check_interval = 1m
  retry_interval = 30s
  vars.dummy_state = {{
    if (Math.random() * 1000 > 990) {
      return 1
    } else {
      return 0
    }
  }}
  vars.dummy_text = "It's fine or not"
  enable_notifications = false
}

globals.nextRandomHost = 0
globals.nextLocalHost = 0
globals.desiredNum = 0

globals.createNextRandomHost = function() {
  globals.nextRandomHost += 1
  var name = "random" + globals.nextRandomHost
  object Host name {
    import globals.desiredTemplate
  }
}

globals.createRandomHosts = function() {
  var cnt = 0
  var num = globals.desiredNum
  while (cnt < num) {
    cnt += 1
    globals.createNextRandomHost()
  }
}

globals.addRandomHosts = function(num, tpl) {
  globals.desiredTemplate = tpl
  globals.desiredNum = num
  Internal.run_with_activation_context(globals.createRandomHosts)
}

EOF

cat <<EOF >/etc/icinga2/features-available/ido-mysql.conf
library "db_ido_mysql"

object IdoMysqlConnection "ido-mysql" {
  user = "icinga2",
  password = "icinga2",
  host = "mysql1.lxc",
  database = "icinga2"
}

EOF

cat <<EOF >>/etc/icinga2/conf.d/api-users.conf

object ApiUser "icingaweb2" {
  password = "icingaweb2"
  permissions = [ "actions/*", "objects/modify/hosts", "objects/modify/services", "objects/modify/icingaapplication" ]
}

object ApiUser "director" {
  password = "director"
  permissions = [ "*" ]
}

EOF

icinga2 feature enable ido-mysql

systemctl restart icinga2
