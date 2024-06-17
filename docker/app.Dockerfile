FROM php:7.4.3-apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY src ./
CMD ["apache2-foreground"]