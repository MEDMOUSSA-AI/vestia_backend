FROM php:8.2-apache

# نسخ الملفات
COPY . /var/www/html/

# ضبط الصلاحيات
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# تفعيل mod_rewrite
RUN a2enmod rewrite

# تحديد المجلد الصحيح كـ DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/html/vestia_backend/vestia/api|g' /etc/apache2/sites-available/000-default.conf

# السماح بـ .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

EXPOSE 80
