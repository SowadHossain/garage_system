FROM php:8.1-apache

# Install system deps and PHP extensions required (mysqli, pdo_mysql)
RUN apt-get update \
	&& apt-get install -y --no-install-recommends \
		default-mysql-client \
		libzip-dev \
		zip \
	&& docker-php-ext-install mysqli pdo_mysql \
	&& rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set Apache document root to the project's public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html/garage_system/public
RUN sed -ri -e 's!DocumentRoot /var/www/html!DocumentRoot ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf \
	&& sed -ri -e 's!<Directory /var/www/>!<Directory ${APACHE_DOCUMENT_ROOT}>!g' /etc/apache2/apache2.conf

# Suppress ServerName warning
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
	&& a2enconf servername

# Copy application files into the container
COPY . /var/www/html/garage_system/

# Ensure ownership so Apache can read/write where needed
RUN chown -R www-data:www-data /var/www/html/garage_system

# Export APACHE_DOCUMENT_ROOT at runtime so Apache config variable is defined
RUN printf "\n# Project document root\nexport APACHE_DOCUMENT_ROOT=%s\n" "/var/www/html/garage_system/public" >> /etc/apache2/envvars
# Replace any remaining ${APACHE_DOCUMENT_ROOT} tokens in site configs with the
# concrete path so Apache doesn't rely on env substitution at runtime.
RUN sed -i "s!\$\{APACHE_DOCUMENT_ROOT\}!/var/www/html/garage_system/public!g" /etc/apache2/sites-available/000-default.conf \
	&& sed -i "s!\$\{APACHE_DOCUMENT_ROOT\}!/var/www/html/garage_system/public!g" /etc/apache2/apache2.conf || true
# Overwrite default vhost with a concrete DocumentRoot to avoid runtime variable
# substitution issues.
RUN cat > /etc/apache2/sites-available/000-default.conf <<'EOF'
<VirtualHost *:80>
	ServerAdmin webmaster@localhost
	# Keep the main DocumentRoot at /var/www/html so the container root behaves
	# normally, and expose the application under the /garage_system path using an Alias
	DocumentRoot /var/www/html

	# Map both the explicit /garage_system/public path and the /garage_system
	# base path to the same public folder so the app's absolute links resolve
	# correctly (place more specific alias first).
	Alias /garage_system/public /var/www/html/garage_system/public
	Alias /garage_system /var/www/html/garage_system/public

	<Directory /var/www/html/garage_system/public>
		Options Indexes FollowSymLinks
		AllowOverride All
		Require all granted
	</Directory>

	ErrorLog ${APACHE_LOG_DIR}/error.log
	CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
RUN a2enconf servername || true

EXPOSE 80

CMD ["apache2-foreground"]
