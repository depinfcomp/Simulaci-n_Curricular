FROM php:7.4-apache as web

# Container user
ARG USER_NAME
ARG USER_ID
ARG GROUP_ID

RUN if [ ${USER_ID:-0} -ne 0 ] && [ ${GROUP_ID:-0} -ne 0 ]; then \
    userdel -f $USER_NAME &&\
    if getent group $USER_NAME ; then groupdel $USER_NAME ;fi &&\
    groupadd -g ${GROUP_ID} $USER_NAME &&\
    useradd -l -u ${USER_ID} -g $USER_NAME $USER_NAME &&\
    install -d -m 0755 -o $USER_NAME -g $USER_NAME /home/$USER_NAME &&\
    mkdir -p /var/lib/postgresql/data/pgdata &&\
    mkdir -p /var/lib/postgresql/data/user_data && \
    chown --changes --silent --no-dereference --recursive \
    --from=33:33 ${USER_ID}:${GROUP_ID} \
    /home/$USER_NAME \
    /var/lib/postgresql/data/pgdata \
    /var/lib/postgresql/data/user_data \
;fi

# Extensions
RUN apt-get update --yes && apt-get install --yes \
    dos2unix

RUN apt-get install -y \ 
    build-essential \
    libssl-dev \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    zip 

# Codeigniter Extensions
RUN apt-get update --yes \
    && apt-get install --yes libbz2-dev \
    && php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && apt-get install --yes wget \
    && HASH="$(wget -q -O - https://composer.github.io/installer.sig)" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install intl
RUN docker-php-ext-configure intl
RUN docker-php-ext-install -j$(nproc) \
        bz2 \
        bcmath \
        zip \
        gd \
        pdo_mysql \
        mysqli \
        shmop \
        sockets \
        pdo_pgsql \
        pgsql \
    && a2enmod rewrite

# Entrypoint
ADD /init/init_framework.sh /init/init_framework.sh
RUN chmod +x /init/init_framework.sh
CMD ["/init/init_framework.sh"]

# Container config  
COPY /memory-limit.ini /usr/local/etc/php/conf.d 
COPY /timezone.ini /usr/local/etc/php/conf.d
COPY /resources-limit.ini /usr/local/etc/php/conf.d
# Enable apache rewrite
COPY 000-default.conf /etc/apache2/sites-available/000-default.conf

USER $USER_NAME