FROM docker.knewton.net/nopbase:stable

RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get -y install \
    apache2 php5 php5-mysql php5-curl

# Enable apache mods.
RUN a2enmod php5 && a2enmod authnz_ldap && a2enmod ldap

# Manually set up the apache environment variables
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

# Expose a port for apache
EXPOSE 41811

#CMD /usr/sbin/apache2ctl -D FOREGROUND

COPY src/ /var/www/html/
COPY config/apache.conf /etc/apache2/sites-enabled/000-default.conf
COPY config/ports.conf /etc/apache2/ports.conf
COPY config/secureconfig.php /var/www/html/phplib