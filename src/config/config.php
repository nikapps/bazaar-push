<?php
/**
 * Configuration
 * BazaarPush by NikApps
 */

return [
   "credentials" => [
       /**
        * Your CafeBazaar credentials.
        * define each one of them as an associative array like the example
        */
       [
           "email" => "",
           "password" => ""
       ]
   ],
   "pushbulletKeys" => [
       /**
        * Your PushBullet Keys
        * define each one of them as an associative array like the example
        * You can get the key from https://www.pushbullet.com/account
        * also if you want push reports to this PushBullet account from some of your CafeBazaar account,
        * simply define them as an array in 'accounts' key. you can also pass 'null' for sending report
        * from all of your defined CafeBazaar accounts.
        *
        * You can define the devices that you want to push on them, for example if you have 3 devices
        * in your PushBullet account and you just want push reports on just one or two of them you can pass
        * their PushBullet identifires as an ARRAY in 'devices' key.
        * Again, you can pass 'null' to send the report on all of your devices.
        */
       [
           "key" => "pushbullet key",
           "accounts" => null,
           "devices" => null
       ]
   ],

    /**
     * Push Report Templates
     * This is a very simple push report template with some placeholders, you can customize them for yourself.
     */
    "reportTemplates" => [
       "newSaleReport" => [
           "title" => "<< Bazaar Sale Report >>",
           "body" => "[[[--TOTAL-NEW-SALE--]]] RLS sale on [[[--TOTAL-NEW-ITEMS--]]] items !\nAccount : [[[--BAZAAR-ACCOUNT--]]]\nItems : \n [[[--ITEMS-REPORT--]]]"
       ]
   ]
];