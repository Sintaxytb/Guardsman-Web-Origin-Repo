

# Guardsman Web

[![No Maintenance Intended](http://unmaintained.tech/badge.svg)](http://unmaintained.tech/)

**Guardsman Web** is a web-based administration panel designed for managing and automating tasks such as auto-punishment expiry, data backups, and statistics. This project is no longer actively maintained, so use it at your own risk.

---

## Installation

Last edited by **Jaiden** 10 months ago.

Special thanks to the **Pterodactyl Project** for their in-depth installation instructions, which were utilized in this guide.

---

> [!WARNING]
> The release of Guardsman V2 has made this Guardsman Project **deprecated**, and updates have ceased.
> 
> This is just a "fork" of the repo, as the "normal" one has been privatised.
> 
> Thank you for your support to Guardsman
>
> THIS REPO AND ALL THE CONTENTS INSIDE OF IT ARE OWNED BY BUNKER BRAVO LLC.

---

### Dependency Installation

Guardsman Web requires several system dependencies and programs to function properly. Follow the steps below to install them:

1. Install the `software-properties-common` package to add the `add-apt-repository` command:
   ```bash
   apt -y install software-properties-common curl apt-transport-https ca-certificates gnupg
   ```

2. Add additional repositories for PHP, Redis, and MariaDB:
   ```bash
   LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php
   ```

3. Update the repositories list:
   ```bash
   apt update
   ```

4. Install the required dependencies:
   ```bash
   apt -y install php8.3 php8.3-{common,cli,gd,mysql,mbstring,bcmath,xml,fpm,curl,zip} mariadb-server nginx tar unzip git
   ```

5. Install **Composer**, a PHP package/dependency manager:
   ```bash
   curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
   ```

---

### Project Installation

To install Guardsman Web on your system, follow these steps:

1. Clone the Guardsman Web repository:
   ```bash
   git clone https://github.com/Sintaxytb/Guardsman-Web-Origin-Repo
   ```

1.2 Go in the cloned repo.
```bash
cd guardsman-web
```

2. Install Composer dependencies:
   ```bash
   composer install
   ```

3. Install NPM dependencies using your preferred package manager:
   ```bash
   npm install
   # OR
   pnpm install
   # OR
   yarn install
   ```

4. Generate an application key:
   ```bash
   php artisan key:generate
   ```

---

### Crontab Configuration

To enable automated tasks such as auto-punishment expiry, data backups, and statistics, you need to set up a crontab entry that runs the scheduled tasks every minute.

1. Open the crontab editor:
   ```bash
   sudo crontab -e
   ```
   If prompted, select an editor (e.g., GNU nano).

2. Add the following entry to the bottom of the file:
   ```bash
   * * * * * php /opt/guardsman-web/artisan schedule:run >> /dev/null 2>&1
   ```

   > **Note**: To customize the interval, use [Crontab Guru](https://crontab.guru/).

---

### Configuration

1. Copy the `.env.example` file to `.env`:
   ```bash
   cp .env.example .env
   ```

2. Edit the `.env` file to configure your environment variables. **Guardsman Web requires a working MySQL or MariaDB installation.**

3. After configuring the `DB_*` values, run the database migrations:
   ```bash
   php artisan migrate
   ```

---

### Post-Installation

After completing the installation, you need to create a user with **super admin** privileges:

1. Run the following command:
   ```bash
   php artisan g:user:make
   ```

2. Follow the prompts to enter the user's information.

---

### ⚠️ **Warnings**
- **No Maintenance**: This project is no longer actively maintained. Bugs and security issues will not be addressed. (Dependecies will still be updated)
- **Database Backups**: Regularly back up your database to prevent data loss.
- **Security**: Ensure your server is properly secured, especially if exposed to the internet.

---

### License
This project is provided as-is without any warranty. Use it at your own risk.
