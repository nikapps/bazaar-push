<?php
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

namespace Nikapps\BazaarPush;

use Fisharebest\ExtCalendar\PersianCalendar;
use Illuminate\Support\Facades\Config;
use PHPushbullet\PHPushbullet;

class BazaarPush {

    private $urls = [
        "loginPage" => "http://cafebazaar.ir/login/",
        "paymentPage" => "http://cafebazaar.ir/panel/go-to-pardakht/",
        "paymentsList" => "http://pardakht.cafebazaar.ir/panel/sales/list/"
    ];

    private $patterns = [
        "csrfKey" => "/name='csrfmiddlewaretoken'.*?value='(.*?)'/is",
        "paymentList" => "/<tr>(.*?)<\/tr>/is",
        "paymentItem" => "/<td>(.*?)<\/td>.*?<code>(.*?)<\/code>.*?<td>(.*?)<\/td><td>(.*?)<\/td>.*?<span>(.*?)<\/span>.*?<code>(.*?)</is"
    ];

    /**
     * This will process a single account and checks for new sales.
     * @param $email
     * @param $password
     * @throws BazaarPushException
     */
    public function processAccountSale($email, $password){

        $ch = curl_init( $this->urls['loginPage'] );

        // creating a temporary cookie file
        $cookiefile = tempnam("/tmp", "cookie");

        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefile);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefile);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT ,10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $resultOfLoginPage = curl_exec($ch);
        $cookieInfo = curl_getinfo($ch);

        if ( $cookieInfo['http_code'] != 200 ) {

            throw new BazaarPushException("Could not reach bazaar login page.");

        }

        // fetching CSRF key from login page
        preg_match_all($this->patterns['csrfKey'], $resultOfLoginPage, $loginPageMatches);

        $fields = [
            'csrfmiddlewaretoken' => $loginPageMatches[1][0],
            'email' => $email,
            'password' => $password,
            'login' => 1,
            'newsletter' => 'newsletter',
            'agree' => 'agree'
        ];
        $headers = [
            'Referer: ' . $this->urls['loginPage'],
            'Origin: ' . $this->urls['loginPage'],
            // It's not very important, you can change it with your own :)
            'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36',
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_exec($ch);

        // disabling post flag
        curl_setopt($ch, CURLOPT_POST, 0);

        // going to pardakht section
        curl_setopt($ch,CURLOPT_URL, $this->urls['paymentPage']);
        curl_exec($ch);

        // fetching latest sales list
        curl_setopt($ch,CURLOPT_URL, $this->urls['paymentsList']);
        $sales = curl_exec($ch);

        preg_match_all($this->patterns['paymentList'],$sales,$salesMatches);

        // removing the headers, kind a lazy :)
        unset($salesMatches[1][0]);
        $newSalePrice = 0;
        $newSaleItems = [];
        $totalNewItems = 0;
        foreach($salesMatches[1] as $row) {
            preg_match_all($this->patterns['paymentItem'],$row,$rowMatches);

            $date = $rowMatches[3][0];
            $date = $this->endigit($date);
            list($year,$month,$day) = explode("/",$date);

            $jalaliCalender = new PersianCalendar();
            list($gYear, $gMonth, $gDay) = $jalaliCalender->ymdToJd($year,$month,$day);

            $time = $rowMatches[4][0];
            $time = $this->endigit($time);
            list($hour,$minute,$second) = explode(":",$time);

            $token = $rowMatches[6][0];
            if ( !count(BazaarSale::where('token','=',$token)->get()) ){
                $bazaarSale = new BazaarSale();
                $bazaarSale->email = $rowMatches[1][0];
                $bazaarSale->account = $email;
                $bazaarSale->package = $rowMatches[2][0];
                $bazaarSale->date = "$gYear-$gMonth-$gDay $hour:$minute:$second";
                $bazaarSale->price = intval($this->endigit(str_replace("٬","",$rowMatches[5][0])));
                $bazaarSale->token = $token;
                $bazaarSale->save();

                if(!isset($newSaleItems[$bazaarSale->package])){
                    $newSaleItems[$bazaarSale->package] = 0;
                }

                $newSaleItems[$bazaarSale->package]++;
                $newSalePrice += $bazaarSale->price;
                $totalNewItems++;
            }
        }

        if($newSalePrice > 0){
            $reportTemplates = $this->getReportTemplates();

            $reportAdapter = [
                "[[[--TOTAL-NEW-SALE--]]]" => number_format($newSalePrice),
                "[[[--TOTAL-NEW-ITEMS--]]]" => number_format($totalNewItems),
                "[[[--BAZAAR-ACCOUNT--]]]" => $email,
                "[[[--ITEMS-REPORT--]]]" => print_r($newSaleItems,1)
            ];

            $reportTitle = $reportTemplates['newSaleReport']['title'];
            $reportBody = $reportTemplates['newSaleReport']['body'];

            foreach($reportAdapter as $key => $item){
                $reportTitle = str_replace($key, $item, $reportTitle);
                $reportBody = str_replace($key, $item, $reportBody);
            }

            $this->pushReport($reportTitle, $reportBody, $email);
        }

    }

    /**
     * checks all of the accounts, their sale state and pushes a report for their new changes.
     */
    public function saleProcess(){
        $credentials = $this->getBazaarCredentials();

        foreach($credentials as $credential){
            $this->processAccountSale($credential['email'], $credential['password']);
        }
    }

    /**
     * This method will transform persian digits to english ones
     * @param  String $in_num
     * @return Integer
     */
    public static function endigit ( $in_num ) {

        $in_num = str_replace('۰' ,"0", $in_num);//
        $in_num = str_replace('۱',"1" , $in_num);//
        $in_num = str_replace('۲',"2" , $in_num);//
        $in_num = str_replace('۳',"3" , $in_num);//
        $in_num = str_replace('۴',"4" , $in_num);//
        $in_num = str_replace('۵',"5" , $in_num);//
        $in_num = str_replace('۶',"6" , $in_num);//
        $in_num = str_replace('۷',"7", $in_num);//
        $in_num = str_replace('۸',"8" , $in_num);//
        $in_num = str_replace('۹',"9" , $in_num);//

        return $in_num;

    }

    /**
     * returns an array of Bazaar credentials from configuration file.
     * @return mixed
     * @throws BazaarPushException
     */
    private function getBazaarCredentials(){
        $credentials = Config::get("bazaar-push::credentials");

        if($credentials == null){
            throw new BazaarPushException("The credentials not found in your configuration");
        }else {
            return $credentials;
        }
    }

    /**
     * returns an array of PushBullet keys from configuration file.
     * @return mixed
     * @throws BazaarPushException
     */
    private function getPushBulletKeys(){
        $pushBulletKeys = Config::get("bazaar-push::pushbulletKeys");

        if($pushBulletKeys == null){
            throw new BazaarPushException("The push bullet keys are missing from your configuration file");
        }else{
            return $pushBulletKeys;
        }

    }

    /**
     * returns an array of report templates from configuration file
     * @return mixed
     * @throws BazaarPushException
     */
    private function getReportTemplates(){
        $reportTemplates = Config::get("bazaar-push::reportTemplates");

        if($reportTemplates == null){
            throw new BazaarPushException("The report templates are missing from your configuration file");
        }else {
            return $reportTemplates;
        }
    }

    /**
     * pushes a report from the given account to the devices
     * @param $reportTitle
     * @param $reportBody
     * @param $account
     */

    public function pushReport($reportTitle, $reportBody, $account){
        $pushKeys = $this->getPushBulletKeys();

        foreach ($pushKeys as $pushKey) {
            if($pushKey['accounts'] == null || in_array($account, $pushKey['accounts'])){
                $pushBullet = new PHPushbullet($pushKey['key']);
                if($pushKey['devices'] == null){
                    // pushes to all of the devices
                    //$pushBullet->pushNote(null, $reportTitle, $reportBody);
                    foreach($pushBullet->devices() as $device){
                        $pushBullet->devices($device->iden)->note($reportTitle, $reportBody);
                    }
                }else{
                    // pushes to selected devices
                    foreach($pushKey['devices'] as $device){
                        $pushBullet->devices($device)->note($device, $reportTitle, $reportBody);
                    }
                }
            }
        }
    }
} 