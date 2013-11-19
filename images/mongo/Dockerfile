FROM base
MAINTAINER m.lebkowski@gmail.com

RUN echo "deb http://archive.ubuntu.com/ubuntu/ precise universe" >> /etc/apt/sources.list
RUN apt-get update
RUN apt-get install -y wget nano

RUN dpkg-divert --local --rename --add /sbin/initctl && ln -s /bin/true /sbin/initctl

RUN apt-get install -y mongodb-server

CMD mongod --dbpath /home/mongo
EXPOSE 27017