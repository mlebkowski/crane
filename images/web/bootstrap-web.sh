#!/usr/bin/env bash

set -u
set -e

export SYMFONY_ENV=lxc;

if [ ! -d "/home/docplanner" ]
then
        echo 'You must mount /home/docplanner using `docker run -v ...`'
        exit 127;
fi

php5-fpm
nginx &

mkdir -p /var/run/sshd
chmod 0755 /var/run/sshd
/usr/sbin/sshd

( cat <<EOF
parameters:
    lxc_kernel_host: ${KERNEL_HOST}
    lxc_mysql_port: ${MYSQL_PORT}
    lxc_memcached_port: ${MEMCACHED_PORT}
    lxc_search_port: ${SEARCH_PORT}
    lxc_mongo_port: ${MONGO_PORT}
    lxc_platform_dir: "/home/platform"
    lxc_platform_url: "//local.znanylekarz.pl/platform/"

EOF
) > /home/docplanner/app/config/parameters-lxc.yml

( cat <<EOF
DB:
  HOST: ${KERNEL_HOST}
  PASS:
  PORT: ${MYSQL_PORT}
EOF
) > /home/docplanner/legacy/app/config/local.yaml

cd /home/docplanner

[ -f "app/config/parameters.yml" ] || touch app/config/parameters.yml

cd -

sleep 3

ps aux

sleep 10

tail -f /var/log/nginx/* /var/log/php5-fpm.log