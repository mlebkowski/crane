FROM puck/php
MAINTAINER m.lebkowski@gmail.com

# RUN mkdir /root/.ssh
# ADD id_rsa /root/.ssh/
# ADD id_rsa.pub /root/.ssh/authorized_keys
# RUN chmod 700 /root/.ssh
# RUN chmod 600 /root/.ssh/id_rsa
RUN apt-get install --force-yes -y ssh
RUN apt-get install --force-yes -y openssh-server
RUN sed -i"" -e 's/^.*PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config
RUN sed -i"" -e 's/#ListenAddress 0.0.0.0/ListenAddress 0.0.0.0/' /etc/ssh/sshd_config
RUN echo "root:toor"|chpasswd
RUN chmod -R 777 /tmp

RUN add-apt-repository -y ppa:nginx/stable
RUN apt-get update

RUN apt-get install -y nginx
ADD nginx.conf /etc/nginx/sites-available/default
RUN echo "daemon off;" >> /etc/nginx/nginx.conf

ADD bootstrap-web.sh bootstrap-web.sh
CMD ./bootstrap-web.sh

EXPOSE 80
EXPOSE 22
