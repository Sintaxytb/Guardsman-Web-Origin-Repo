

# Guardsman Web

[![No Maintenance Intended](http://unmaintained.tech/badge.svg)](http://unmaintained.tech/)
[![img](https://img.shields.io/badge/We%20support-BlueHats-blue.svg)](https://bluehats.global)

**Guardsman Web** is a web-based administration panel designed for managing and automating tasks such as auto-punishment expiry, data backups, and statistics. This project is no longer actively maintained, so use it at your own risk.

---

## Installation

Last edited by **Jaiden** 10 months ago.

Special thanks to the **Pterodactyl Project** for their in-depth installation instructions, which were utilized in this guide.

---

> [!WARNING]
> The release of Guardsman V2 has made this Guardsman Project **deprecated**, and updates have **ceased**.
> 
> This is just a "fork" of the repo, as the "normal" one has been privatised.
> 
> Thank you for your support to Guardsman
>
> THIS REPO AND ALL THE CONTENTS INSIDE OF IT ARE OWNED BY BUNKER BRAVO LLC.
> This repo **is not a replacment** until V2 is going open-source as most of it is deprecated and/or not maintained.

---

### Dependency Installation

Guardsman Web requires several system dependencies and programs to function properly. Follow the steps below to install them:

1. Install the `software-properties-common` package to add the `add-apt-repository` command:
   ```bash
   sudo apt -y install software-properties-common curl apt-transport-https ca-certificates gnupg
   ```

2. Add additional repositories for PHP, Redis, and MariaDB:
   ```bash
   sudo LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php
   ```

3. Update the repositories list:
   ```bash
   sudo apt update && sudo apt upgrade -y
   ```

4. Install the required dependencies:
   ```bash
   sudo apt -y install php8.3 php8.3-{common,cli,gd,mysql,mbstring,bcmath,xml,fpm,curl,zip} mariadb-server nginx tar unzip git
   ```

5. Install **Composer**, a PHP package/dependency manager:
   ```bash
   sudo curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
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
 sudo composer install
   ```

3. Install NPM dependencies using your preferred package manager:
   ```bash
   sudo npm install --legacy-peer-deps #For npm install to not have conflict dependecies, due to an update
   ```

4. Generate an application key:
   ```bash
   sudo php artisan key:generate
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
   sudo php artisan migrate
   ```

---
### Web-Server install:

Without SSL:

````bash
server {
    # Replace the example <domain> with your domain name or IP address
    listen 80;
    server_name <domain>;


    root /opt/guardsman-web/public;
    index index.html index.htm index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
        expires 1y;
        add_header Cache-Control "public, no-transform";
        add_header Accept-Encoding "gzip, compress, br";
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    access_log off;
    error_log  /var/log/nginx/guardsman.log error;

    location /api/* {
        expires -1;
        add_header 'Cache-Control' 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0';
    }

    location /openapi.json {
        expires -1;
        add_header 'Cache-Control' 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0';
    }

    location /statistics.json {
        expires -1;
        add_header 'Cache-Control' 'no-store, no-cache, must-revalidate, proxy-revalidate, max-age=0';
    }

    #GZIP
    # Enable gzip compression.
    gzip on;

    # Compression level (1-9).
    # 5 is a perfect compromise between size and CPU usage, offering about
    # 75% reduction for most ASCII files (almost identical to level 9).
    gzip_comp_level    5;

    # Don't compress anything that's already small and unlikely to shrink much
    # if at all (the default is 20 bytes, which is bad as that usually leads to
    # larger files after gzipping).
    gzip_min_length    256;

    # Compress data even for clients that are connecting to us via proxies,
    # identified by the "Via" header (required for CloudFront).
    gzip_proxied       any;

    # Tell proxies to cache both the gzipped and regular version of a resource
    # whenever the client's Accept-Encoding capabilities header varies;
    # Avoids the issue where a non-gzip capable client (which is extremely rare
    # today) would display gibberish if their proxy gave them the gzipped version.
    gzip_vary          on;

    # Compress all output labeled with one of the following MIME-types.
    gzip_types
      application/atom+xml
      application/javascript
      application/json
      application/ld+json
      application/manifest+json
      application/rss+xml
      application/vnd.geo+json
      application/vnd.ms-fontobject
      application/x-font-ttf
      application/x-web-app-manifest+json
      application/xhtml+xml
      application/xml
      font/opentype
      image/bmp
      image/svg+xml
      image/x-icon
      text/cache-manifest
      text/css
      text/plain
      text/vcard
      text/vnd.rim.location.xloc
      text/vtt
      text/x-component
      text/x-cross-domain-policy;
    # text/html is always compressed by gzip module

    # allow larger file uploads and longer script runtimes
    client_max_body_size 100m;
    client_body_timeout 120s;

    sendfile off;

    location /api/docs {
        proxy_pass http://127.0.0.1:9001;
    }

    location ~ \.php$ {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param PHP_VALUE "upload_max_filesize = 100M \n post_max_size=100M";
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_PROXY "";
        fastcgi_intercept_errors off;
        fastcgi_buffer_size 16k;
        fastcgi_buffers 4 16k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    location ~ /\.ht {
        deny all;
    }
}
````

---


### Post-Installation

After completing the installation, you need to create a user with **super admin** privileges:
1. Run the following command:
   ```bash
   sudo php artisan g:role:make
   ```


2. Run the following command:
   ```bash
   sudo php artisan g:user:make
   ```

3. Follow the prompts to enter the user's information.

---

### ⚠️ **Warnings**
- **No Maintenance**: This project is no longer actively maintained. Bugs and security issues will not be addressed. (Dependecies will still be updated)
- **Database Backups**: Regularly back up your database to prevent data loss.
- **Security**: Ensure your server is properly secured, especially if exposed to the internet.

---

### License
This project is provided as-is without any warranty. Use it at your own risk.
