# BazaarPush
a robot based on Laravel framework which regularly login into your CafeBazaar accounts, parses pages and pushes reports for new events like sales on your devices via PushBullet.

## Installation

Using [composer](https://packagist.org/packages/nikapps/bazaar-push):
Add this package dependency to your Laravel's composer.json.

```
{
    "require": {
        "nikapps/bazaar-push": "1.*"
    }
}
```
Update composer
```
composer update
```
Add this package provider in your providers array `[app/config/app.php]`
```
'Nikapps\BazaarPush\BazaarPushServiceProvider'
```
Run migrations for this package, this will add a table to your database named `bazaar_sale` which keeps track of your sale records.
```
php artisan migrate --package="nikapps/bazaar-push"
```
Publish configuration file into your app directory
```
php artisan config:publish nikapps/bazaar-push
```
Run 
```
php artisan
```
if you see a `bazaarpush` command namespace, you are all set to go !

## Configuration
This command will create a config file in `[app/config/packages/nikapps/bazaar-push]` directory which you have to declare your CafeBazaar credentials and also PushBullet keys and devices in it.

