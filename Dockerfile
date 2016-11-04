FROM php:7-apache

RUN apt-get update && apt-get -y install php5-curl php5-mysql && php5enmod mysql && php5enmod curl


COPY . /var/www/html/
COPY apache.conf /etc/apache2/sites-enabled/000-default.conf
RUN a2enmod authnz_ldap
