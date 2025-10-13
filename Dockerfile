FROM php:7.4-apache

# Instalar extensões essenciais
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Ativar mod_rewrite
RUN a2enmod rewrite

# Permitir .htaccess no Apache
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Criar a pasta includes com permissão para www-data
RUN mkdir -p /var/www/html/includes \
    && chown -R www-data:www-data /var/www/html/includes \
    && chmod -R 755 /var/www/html/includes

# Copiar os arquivos do seu app
COPY . /var/www/html

# Ajustar permissões de todo o projeto
RUN chown -R www-data:www-data /var/www/html

# Expor porta padrão do Apache
EXPOSE 80

# Rodar Apache em foreground
CMD ["apache2-foreground"]