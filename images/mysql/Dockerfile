FROM base
MAINTAINER m.lebkowski@gmail.com

RUN echo "deb http://archive.ubuntu.com/ubuntu/ precise universe" >> /etc/apt/sources.list
RUN apt-get update

RUN dpkg-divert --local --rename --add /sbin/initctl && ln -s /bin/true /sbin/initctl

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get install -y mysql-server
ENV DEBIAN_FRONTEND dialog

ADD bootstrap-mysql.sh bootstrap-mysql.sh
RUN ./bootstrap-mysql.sh

# RUN mkdir sql
# ADD sql/ ./sql/

CMD ( [ -d /home/mysql/mysql ] || cp -R -p /var/lib/mysql/* /home/mysql/ ) && mysqld

EXPOSE 3306