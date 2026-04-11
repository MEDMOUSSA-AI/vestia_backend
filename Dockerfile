FROM php:8.2-apache

# نسخ الملفات
COPY . /var/www/html/

# ضبط الصلاحيات
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# تفعيل mod_rewrite
RUN a2enmod rewrite

# السماح بـ .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
