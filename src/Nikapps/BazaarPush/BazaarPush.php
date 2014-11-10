<?php
/**
 * a robot on CafeBazaar developer panel as a Laravel Frameword package which regularly pushes information
 * about your apps on your mobile phone or other clients via PushBullet.
 *
 * @package     bazaar-push
 * @author      Hossein Moradgholi <h.moradgholi@icloud.com>
 * @license     MIT License
 */

namespace Nikapps\BazaarPush;

use Illuminate\Support\Facades\Config;
use Miladr\Jalali\jDateTime;

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

    public function processAccount($email, $password){

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
            list($gYear, $gMonth, $gDay) = jDateTime::toGregorian($year,$month,$day);

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

    public function exec(){
        $credentials = $this->getBazaarCredentials();

        foreach($credentials as $credential){
            $this->processAccount($credential['email'], $credential['password']);
        }
    }

    private function getBazaarCredentials(){
        $credentials = Config::get("bazaar-push::credentials");

        if($credentials == null){
            throw new BazaarPushException("The credentials not found in your configuration");
        }else {
            return $credentials;
        }
    }

    private function getPushBulletKeys(){
        $pushBulletKeys = Config::get("bazaar-push::pushbulletKeys");

        if($pushBulletKeys == null){
            throw new BazaarPushException("The push bullet keys are missing from your configuration file");
        }else{
            return $pushBulletKeys;
        }

    }

    private function getReportTemplates(){
        $reportTemplates = Config::get("bazaar-push::reportTemplates");

        if($reportTemplates == null){
            throw new BazaarPushException("The report templates are missing from your configuration file");
        }else {
            return $reportTemplates;
        }
    }

    public function pushReport($reportTitle, $reportBody, $account){
        $pushKeys = $this->getPushBulletKeys();

        foreach ($pushKeys as $pushKey) {
            if($pushKey['accounts'] == null || in_array($account, $pushKey['accounts'])){
                $pushBullet = new \Pushbullet($pushKey['key']);
                if($pushKey['devices'] == null){
                    // pushes to all of the devices
                    $pushBullet->pushNote(null, $reportTitle, $reportBody);
                }else{
                    // pushes to selected devices
                    foreach($pushKey['devices'] as $device){
                        $pushBullet->pushNote($device, $reportTitle, $reportBody);
                    }
                }
            }
        }
    }

} 