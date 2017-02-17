# IC Locker online storage

## Install software

To install, clone repository and run command:
```
composer update
```
This will download all dependency libraries and will create autoload file for lazy loading.

## Database

`database.sql` file is provided to be imported to MySQL database.

## Config file

Finally, rename `config_site_template.php` to `config_site.php` and update settings for your web server.

## First user

Register first user who will automatically become administrator. Go to `/register` url and process.