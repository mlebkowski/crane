FROM base
MAINTAINER m.lebkowski@gmail.com

RUN echo "deb http://archive.ubuntu.com/ubuntu/ precise universe" >> /etc/apt/sources.list
RUN apt-get update

RUN dpkg-divert --local --rename --add /sbin/initctl && ln -s /bin/true /sbin/initctl
RUN apt-get install -y memcached

CMD memcached -u memcache

EXPOSE 11211