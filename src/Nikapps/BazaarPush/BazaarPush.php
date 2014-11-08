<?php
/**
 * Created by PhpStorm.
 * User: hossein
 * Date: 11/8/14
 * Time: 6:58 PM
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

        // remove first row, kind a lazy :)
        unset($salesMatches[1][0]);
        $newSalePrice = 0;
        $newSaleItem = 0;

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
                $newSaleItem++;
                $newSalePrice += $bazaarSale->price;
            }
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
        $credentials = Config::get("bazaarpush.credentials");

        if($credentials == null){
            throw new BazaarPushException("The credentials not found in your configuration");
        }else {
            return$credentials;
        }
    }

} 