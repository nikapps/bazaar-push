# BazaarPush
a robot based on Laravel framework which regularly login into your CafeBazaar accounts, parses pages and pushes reports for new events like sales on your devices via PushBullet.

## Installation

Using [composer](https://packagist.org/packages/nikapps/bazaar-push), add this package dependency to your Laravel's composer.json :

```
{
    "require": {
        "nikapps/bazaar-push": "1.*"
    }
}
```
Update composer :
```
composer update
```
Add this package provider in your providers array `[app/config/app.php]` : 
```
'Nikapps\BazaarPush\BazaarPushServiceProvider'
```
Run migrations for this package, this will add a table to your database named `bazaar_sale` which keeps track of your sale records.
Run :
```
php artisan migrate --package="nikapps/bazaar-push"
```
Publish configuration file.
Run :
```
php artisan config:publish nikapps/bazaar-push
```
Run :
```
php artisan
```
if you see a `bazaarpush` command namespace, you are all set to go !

## Configuration

Open `[app/config/packages/nikapps/bazaar-push/config.php]`.
Setup your CafeBazaar credentials, each one of them as an associative array like the example below : 
```php
"credentials" => [
       [
           "email" => "first@account.com",
           "password" => "secret"
       ],
       [
           "email" => "second@account.com",
           "password" => "top secret"
       ], 
       .
       .
       .
   ]
```
Setup your PushBullet accounts like the example, get your PushBullet API key from [PushBullet](https://www.pushbullet.com/account)
```php
"pushbulletKeys" => [
       [
           "key" => "FirstAccountKey",
           "accounts" => ['first@account.com'] // declare CafeBazaar accounts as an array which you want to get reports from them
           "devices" => ['LGENexus 4'] // declare your devices as an array which you want to get pushes on them
       ],
       [
           "key" => "SecondAccountKey",
           "accounts" => null, // you can pass null to get reports from all of your declared CafeBazaar accounts in credentials section
           "devices" => null // you can pass null to get pushes on all of the devices associated with this PushBullet account.
       ],
       .
       .
       .
   ],
```
## Adding Cronjob
This cronjob runs the checking command each 20 minutes and fetches the new sales from CafeBazaar, it will report new events as a push to your devices.
Don't forget to change the artisan path :)
```
*/20 * * * * php /absolute/path/to/your/artisan bazaarpush:sale >/dev/null 2>&1
```
