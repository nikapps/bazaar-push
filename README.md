# BazaarPush
a wrapper based on Laravel framework which regularly login into your CafeBazaar accounts, parses pages and pushes reports for new events like sales on your devices via PushBullet.

![BazaarPush](http://s22.postimg.org/e6xhwu93l/Bazaar_Push.jpg)

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
This cronjob runs the checking command each 20 minutes and fetches the new sales from CafeBazaar. Also, it will report new events as a push to your devices.
Don't forget to change the artisan path :)
```
*/20 * * * * php /absolute/path/to/your/artisan bazaarpush:sale >/dev/null 2>&1
```
## Probable issues
- This project parses CafeBazaar pages and fetches required data from them, if CafeBazaar changes the current panel layout we need to change the matching Regular Expression patterns. Our recomendation is to set a watcher on this project to keep up with the updates.
- At this moment CafeBazaar just gives the latest 100 sale records, so if you have more than 100 sale records in 20 minutes (#RichKidsOfIran !), you'll lose some of the records.

## Future
Pushing the daily income and also new comments. 
Wanna contribute ? simply fork this project and make a pull request !

## Dependencies
This project uses these projects : 
- [PHPushbullet](https://github.com/joetannenbaum/phpushbullet)
- [PHP calendar functions](https://github.com/fisharebest/ext-calendar)

## License 
This project released under the [MIT License](http://opensource.org/licenses/mit-license.php).
```
/*
 * Copyright (C) 2014 NikApps Team.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * 1- The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * 2- THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */
```
## Donation
[![Donate via Paypal](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=G3WRCRDXJD6A8)
