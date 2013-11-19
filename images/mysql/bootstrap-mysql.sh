#!/usr/bin/env bash

# boot mysql
mysqld &
sleep 3

PASSWORD="toor"

mysqladmin -u root password "$PASSWORD"

(
	echo "CREATE USER 'zl'@'%';"
	echo "CREATE USER 'pl'@'%';"
	echo "GRANT ALL PRIVILEGES ON *.* TO 'zl'@'%' WITH GRANT OPTION; "
	echo "GRANT ALL PRIVILEGES ON *_pl_*.* TO 'pl'@'%' WITH GRANT OPTION; "
	echo "GRANT ALL PRIVILEGES ON zl_central_dev.* TO 'pl'@'%' WITH GRANT OPTION;"
	echo "FLUSH PRIVILEGES;"
) \
| mysql -uroot -p$PASSWORD

# exit mysqld
killall -9 mysqld
sleep 3

# bind to all interfaces
sed -i"" -e s/127.0.0.1/0.0.0.0/ /etc/mysql/my.cnf
# use external data dir
sed -i"" -e 's/^datadir.*/datadir = \/home\/mysql/' /etc/mysql/my.cnf
# and send logs to stderr
sed -i"" -e 's/log_error = .*/log_error = \/dev\/stderr/' /etc/mysql.cnf