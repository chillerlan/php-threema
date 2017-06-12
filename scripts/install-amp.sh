#!/usr/bin/env bash

export DEBIAN_FRONTEND=noninteractive

# based on https://github.com/laravel/homestead

BOX_NAME="phpstorm.box"
BOX_DOCROOT="/vagrant/public"
BOX_DBPASS="vagrant"

# Update Package List
apt-get update

# Update System Packages
apt-get -y upgrade

# Force Locale
echo "LC_ALL=en_US.UTF-8" >> /etc/default/locale
locale-gen en_US.UTF-8

# Install Some Basic Packages
apt-get install -y build-essential software-properties-common curl dos2unix gcc git libmcrypt4 \
libpcre3-dev ntp unzip make python2.7-dev python-pip re2c supervisor unattended-upgrades whois \
vim libnotify-bin pv cifs-utils

# Set My Timezone
ln -sf /usr/share/zoneinfo/UTC /etc/localtime

# Install AMP

debconf-set-selections <<< "mysql-server mysql-server/root_password password $BOX_DBPASS"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $BOX_DBPASS"

# @todo: apache-http2
#apt-add-repository ppa:ondrej/apache2 -y
apt-add-repository ppa:ondrej/php -y
apt-add-repository ppa:chris-lea/redis-server -y
apt-add-repository ppa:chris-lea/libsodium -y

apt-get update

apt-get install -y --allow-downgrades --allow-remove-essential --allow-change-held-packages \
apache2 libapache2-mod-php7.1 \
php7.1-cli php7.1-dev \
php7.1-gd php7.1-curl php7.1-imap php7.1-mbstring \
php7.1-xml php7.1-zip php7.1-bcmath php7.1-soap \
php7.1-intl php7.1-readline php-xdebug \
libsodium-dev

# Setup Some PHP-FPM Options
echo "xdebug.remote_enable = 1" >> /etc/php/7.1/mods-available/xdebug.ini
echo "xdebug.remote_connect_back = 1" >> /etc/php/7.1/mods-available/xdebug.ini
echo "xdebug.remote_port = 9000" >> /etc/php/7.1/mods-available/xdebug.ini
echo "xdebug.max_nesting_level = 512" >> /etc/php/7.1/mods-available/xdebug.ini
echo "opcache.revalidate_freq = 0" >> /etc/php/7.1/mods-available/opcache.ini

sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/7.1/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php/7.1/apache2/php.ini
sed -i "s/;cgi.fix_pathinfo=1/cgi.fix_pathinfo=0/" /etc/php/7.1/apache2/php.ini
sed -i "s/memory_limit = .*/memory_limit = 512M/" /etc/php/7.1/apache2/php.ini
sed -i "s/upload_max_filesize = .*/upload_max_filesize = 100M/" /etc/php/7.1/apache2/php.ini
sed -i "s/post_max_size = .*/post_max_size = 100M/" /etc/php/7.1/apache2/php.ini
sed -i "s/;date.timezone.*/date.timezone = UTC/" /etc/php/7.1/apache2/php.ini

# Set Some PHP CLI Settings
sudo sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php/7.1/cli/php.ini
sudo sed -i "s/display_errors = .*/display_errors = On/" /etc/php/7.1/cli/php.ini
sudo sed -i "s/memory_limit = .*/memory_limit = 1G/" /etc/php/7.1/cli/php.ini
sudo sed -i "s/;date.timezone.*/date.timezone = UTC/" /etc/php/7.1/cli/php.ini

# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
printf "\nPATH=\"$(sudo su - vagrant -c 'composer config -g home 2>/dev/null')/vendor/bin:\$PATH\"\n" | tee -a /home/vagrant/.profile

# Install global PHPUnit
wget https://phar.phpunit.de/phpunit.phar
chmod +x phpunit.phar
mv phpunit.phar /usr/local/bin/phpunit
phpunit --version

# Install Libsodium extension
pecl channel-update pecl.php.net
pecl install libsodium

echo "extension=libsodium.so" > /etc/php/7.1/mods-available/libsodium.ini

ln -sf /etc/php/7.1/mods-available/libsodium.ini /etc/php/7.1/apache2/conf.d/20-libsodium.ini
ln -sf /etc/php/7.1/mods-available/libsodium.ini /etc/php/7.1/cli/conf.d/20-libsodium.ini


# Apache
sed -i "s/www-data/vagrant/" /etc/apache2/envvars

PATH_SSL="/home/vagrant/.ssl"
PATH_CNF="${PATH_SSL}/${BOX_NAME}.cnf"
PATH_KEY="${PATH_SSL}/${BOX_NAME}.key"
PATH_CRT="${PATH_SSL}/${BOX_NAME}.crt"

mkdir "$PATH_SSL" 2>/dev/null

# Uncomment the global 'copy_extentions' OpenSSL option to ensure the SANs are copied into the certificate.
sed -i '/copy_extensions\ =\ copy/s/^#\ //g' /etc/ssl/openssl.cnf

# Generate an OpenSSL configuration file specifically for this certificate.
BOX_SSL_CERT="
[ req ]
prompt = no
default_bits = 2048
default_keyfile = $PATH_KEY
encrypt_key = no
default_md = sha256
distinguished_name = req_distinguished_name
x509_extensions = v3_ca

[ req_distinguished_name ]
O=Vagrant
C=UN
CN=$BOX_NAME

[ v3_ca ]
basicConstraints=CA:FALSE
subjectKeyIdentifier=hash
authorityKeyIdentifier=keyid,issuer
keyUsage = nonRepudiation, digitalSignature, keyEncipherment
subjectAltName = @alternate_names

[ alternate_names ]
DNS.1 = $BOX_NAME
"
echo "$BOX_SSL_CERT" > $PATH_CNF

# Finally, generate the private key and certificate.
openssl genrsa -out "$PATH_KEY" 2048 2>/dev/null
openssl req -new -x509 -config "$PATH_CNF" -out "$PATH_CRT" -days 365 2>/dev/null

BOX_DEFAULT_HOST="<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot $BOX_DOCROOT

    <Directory $BOX_DOCROOT>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/$BOX_NAME-error.log
    CustomLog \${APACHE_LOG_DIR}/$BOX_NAME-access.log combined

    Include conf-available/$BOX_NAME-aliases.conf
</VirtualHost>
"

echo "$BOX_DEFAULT_HOST" > "/etc/apache2/sites-available/$BOX_NAME.conf"
ln -fs "/etc/apache2/sites-available/$BOX_NAME.conf" "/etc/apache2/sites-enabled/$BOX_NAME.conf"

a2dissite 000-default

ps auxw | grep apache2 | grep -v grep > /dev/null

if [ $? == 0 ]
then
    service apache2 reload
fi


# Configure Supervisor

systemctl enable supervisor.service
service supervisor start

# Install ngrok

wget https://bin.equinox.io/c/4VmDzA7iaHb/ngrok-stable-linux-amd64.zip
unzip ngrok-stable-linux-amd64.zip -d /usr/local/bin
rm -rf ngrok-stable-linux-amd64.zip

# Clean Up

apt-get -y autoremove
apt-get -y clean

# Enable Swap Memory

/bin/dd if=/dev/zero of=/var/swap.1 bs=1M count=1024
/sbin/mkswap /var/swap.1
/sbin/swapon /var/swap.1

# Minimize The Disk Image

echo "Minimizing disk image..."
dd if=/dev/zero of=/EMPTY bs=1M
rm -f /EMPTY
sync
