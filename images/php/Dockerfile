FROM base
MAINTAINER m.lebkowski@gmail.com

RUN echo "deb http://archive.ubuntu.com/ubuntu/ precise universe" >> /etc/apt/sources.list
RUN apt-get update
RUN apt-get install -y wget nano python-software-properties software-properties-common
RUN add-apt-repository -y ppa:ondrej/php5
RUN apt-get update

# something about upstart in a vm box:
RUN dpkg-divert --local --rename --add /sbin/initctl && ln -s /bin/true /sbin/initctl

RUN apt-get install --force-yes -y php5-fpm php5-cli php5-mysql php-apc php5-sqlite php5-imagick php5-imap php5-intl php5-memcached php5-mcrypt php5-xdebug php5-curl php5-dev php5-gd
RUN pecl install mongo
RUN apt-get install --force-yes -y git subversion
RUN echo "date.timezone = Europe/Warsaw" >> /etc/php5/fpm/php.ini
RUN echo "date.timezone = Europe/Warsaw" >> /etc/php5/cli/php.ini
RUN export MONGO_INI=/etc/php5/mods-available/mongo.ini && echo "extension=mongo.so" > $MONGO_INI && ln -s $MONGO_INI /etc/php5/fpm/conf.d/20-mongo.ini && ln -s $MONGO_INI /etc/php5/cli/conf.d/20-mongo.ini

ENV SYMFONY_ENV lxc
ENV SYMFONY_TMP_DIR /tmp
