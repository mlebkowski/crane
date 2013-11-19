FROM base
MAINTAINER m.lebkowski@gmail.com

RUN echo "deb http://archive.ubuntu.com/ubuntu/ precise universe" >> /etc/apt/sources.list
RUN apt-get update
RUN apt-get install -y wget nano

RUN dpkg-divert --local --rename --add /sbin/initctl && ln -s /bin/true /sbin/initctl

# java & elastic:
RUN apt-get install -y openjdk-6-jdk
RUN cd /tmp && wget https://download.elasticsearch.org/elasticsearch/elasticsearch/elasticsearch-0.90.3.deb && dpkg -i /tmp/elasticsearch-0.90.3.deb && rm elasticsearch-0.90.3.deb

CMD /usr/share/elasticsearch/bin/elasticsearch -f

EXPOSE 9200