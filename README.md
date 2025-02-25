[![No Maintenance Intended](http://unmaintained.tech/badge.svg)](http://unmaintained.tech/)

Installation
Last edited by Jaiden 10 months ago
Installation
Thank you Pterodactyl Project for in-depth installation instructions utilized here.

Dependency installation
Guardsman Web requires some system dependencies and programs in order to function. These instructions assume you use the Ubuntu operating system.

# Add "add-apt-repository" command
apt -y install software-properties-common curl apt-transport-https ca-certificates gnupg

# Add additional repositories for PHP, Redis, and MariaDB
LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php

# Update repositories list
apt update

# Install Dependencies

apt -y install php8.3 php8.3-{common,cli,gd,mysql,mbstring,bcmath,xml,fpm,curl,zip} mariadb-server nginx tar unzip git

# Composer
Composer is a php package / dependency manager that Guardsman utilizes for its backend. To install it, run: 

curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

Project installation
To install Guardsman Web on your system, follow these steps:

Clone the Guardsman Web repository. (ex: git clone https://git.bunkerbravointeractive.com/bunker-bravo-interactive/guardsman-web.git -C /opt/guardsman-web)
Install composer dependencies via composer install.
Install NPM dependencies with your package manager of choice (npm install, pnpm install, yarn install)
Run:

php artisan key:generate

Crontab Configuration
In order for things such as auto-punishment expiry, data backups, and other miscellaneous functions like statistics to run, we need to create a crontab entry that will run the scheduled tasks every minute. If you want to run the crontab at different intervals, see Crontab Guru

Open the crontab using 

sudo crontab -e, if it prompts you for an editor, the easiest to use is GNU nano.

Add the following entry to the bottom of the file. 

* * * * * php /opt/guardsman-web/artisan schedule:run >> /dev/null 2>&1

Configuration
Begin by copying .env.example to .env (cp .env.example .env). Change all values you may want to use. Guardsman Web REQUIRES a working MySQL or MariaDB installation.

After configuring DB_ Values, run php artisan migrate.

Post-installation
After installation of the panel, you'll need to create a user on the panel that has "super admin" privileges. To do so, you can utilize php artisan g:user:make. This command will prompt you for the user's information and will create an account for them.

