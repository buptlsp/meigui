<?php
require_once("./curl.php");
date_default_timezone_set("Asia/Chongqing");
class meigui
{
    const LOGIN_URL = 'http://pt.3g.qq.com'; 
    const MEIGUI_URL = 'http://cgi.meigui.qq.com/cgi-bin';
    public static $actionUrl = array(
        'get' => '/m_get',    //刷新页面
        'gift' => '/m_gift',  //可领取的礼包
        'gain' => '/m_gain',
        'dig' => '/m_dig',
        'sow' => '/m_sow',
        'action' => '/m_action',
        'buy' => '/m_buy',
    );
    
    public static $flowerArr = array(
        '梅花' => 133,
        '萍逢草' => 140,
    );

    public static $sowArr = array(
        133 => 233,
        140 => 701,
    );

    public $uin;
    public $sid;
    //需要访问的url
    public $getUrl;
    public $isLoad;

    //获取一个url中的参数
    public static function extractArg($str)
    {
        $data = array();
        $param = explode('&', end(explode('?', $str))); 
        foreach($param as $var){
            $tmp = explode('=', $var);
            if(count($tmp)>=2){
                $data[$tmp[0]] = $tmp[1];
            }
        }
        return $data;
    }
    
    // 登录函数，输入QQ号
    public function login($qq , $pwd)
    {
        $loginurl = "http://pt.3g.qq.com";
        $data = array(
            'loginurl' => self::LOGIN_URL.'/s?aid=nLogin&q_from=meigui',
            'sidtype' => "1",
            'nopre' => "0",
            'q_from' =>  '玫瑰小镇',
            'bid' => "0",
            'go_url' => self::MEIGUI_URL.'/m_get?cmd=1',
            'qq' => $qq,
            'pwd' => $pwd,
            'loginType' => '3',
        );
        $conn = new HttpConn(self::LOGIN_URL, "/handleLogin", $data);
        $conn->setPost();
        $str = $conn->connect();
        //从返回来的数据中提取出uin和sid
        $regex = '#'.self::MEIGUI_URL.'\/m_gift\?\H+#i';
        if(preg_match($regex, $str, $m)) {
            $str = $m[0];
            $data = self::extractArg($str);
            if(isset($data['uin']) && isset($data['sid'])) {
                $this->uin = $data['uin'];
                $this->sid = $data['sid'];
                $this->isLoad = true;
                //获取所有需要操作的url 包括收花、铲除、种花、除草等
            }
        } 
        $regex = '/http:\/\/cgi.meigui.qq.com\/cgi-bin\/m_action\?[\w\d=&;]+/i';
        if(preg_match_all($regex, $str, $m))
        {
            foreach($m[0] as $item) {
                $url = htmlspecialchars_decode($item);
                $curl = new HttpConn($url, "", array());
                $curl->connect();
                sleep(rand(1,5));
            }
        }
    }

    //刷新主页面,更新需要访问的url
    public function flushPage()
    {
        if($this->isLoad){
            $data = array(
                'uin' => $this->uin,
                'sid' => $this->sid,
                'cmd' => 1,
            );
            $conn = new HttpConn(self::MEIGUI_URL, self::$actionUrl['get'], $data);
            $str = $conn->connect();
            $this->getAllUrl($str);
        }
        return true;
    }

    //获取一个符串内所有的需要访问的链接
    public function getAllUrl($str)
    {
        $url = array();
        //四种需要访问的链接 
        $arr = array('gain', 'dig', 'get', 'action');
        foreach($arr as $value){
            $url[$value] = array();
            if($value != 'get'){
                $regex = '#'.self::MEIGUI_URL.self::$actionUrl[$value].'[?\w\d=;&]+#';
            } else {
                $regex = '#'.self::MEIGUI_URL.self::$actionUrl[$value].'[?\w\d=;&]+cmd=3[?\w\d=;&]+soilno[?\w\d=;&]+#';
            }
            if(preg_match_all($regex, $str, $m)){
                foreach($m as $var) {
                    $url[$value] = $var;
                }
            }
        }
        $this->getUrl = $url;
    }

    //依次访问完所有的需要操作的页面
    public function processUrl(){
        //先依次返问完所有的需要收的花
        $arr = $this->getUrl['gain'];
        if(!empty($arr)) {
            foreach($arr as $url) {
                $url = htmlspecialchars_decode($url);
                $conn = new HttpConn($url, '', array());
                $str = $conn->connect();
                print date('Y-m-d H:i:s')."\t 收花\n";
                sleep(1);
            }
            $this->flushPage();
        }
        //铲花
        $arr = $this->getUrl['dig'];
        if(!empty($arr)){
            foreach($arr as $url) {
                $url = htmlspecialchars_decode($url);
                $conn = new HttpConn($url, '', array());
                $str = $conn->connect();
                print date('Y-m-d H:i:s')."\t 铲花\n";
                sleep(1);
            }
            $this->flushPage();
        }
        //除草
        $arr = $this->getUrl['action'];
        if(!empty($arr)) {
            foreach($arr as $url) {
                $url = htmlspecialchars_decode($url);
                $conn = new HttpConn($url, '', array());
                $str = $conn->connect();
                print date('Y-m-d H:i:s')."\t 除草、杀虫\n";
                sleep(1);
            }
            $this->flushPage();
        }
        $arr = $this->getUrl;
        if(!empty($arr['dig']) || !empty($arr['action']) || !empty($arr['gain'])) {
            $this->processUrl();
        }
    }

    public function buyFlower($type = 133, $count = 1){
        $data = array(
            'type' => $type,
            'count' => $count,
            'cmd' => 5,
            'uin' => $this->uin,
            'sid' => $this->sid,
        );
        $conn = new HttpConn(self::MEIGUI_URL, self::$actionUrl['buy'], $data); 
        print date('Y-m-d H:i:s')."\t 买花\ttype:$type, 数量:$count\n";
        $str = $conn->connect();
    }
    //种花
    public function sowFlower()
    {
        //先确定要种什么花，在配置文件中有需要种的花就先种配置文件中的花
        //暂时先种梅花
        $arr = $this->getUrl['get'];
        if(!empty($arr)) {
            foreach($arr as $url) {
                $url = htmlspecialchars_decode($url);
                $data = self::extractArg($url);
                if(!isset($data['soilno'])){
                    continue;
                }
                $soilno = intval($data['soilno']);
                $type = null;
                //如果有水生花需要种，去买水生花
                if(in_array($soilno, array(7,8,18))){
                    $type = self::$flowerArr['萍逢草'];
                    $this->buyFlower($type);
                } else {
                    $type = self::$flowerArr['梅花'];
                    $this->buyFlower($type);
                }
                $data = array(
                    'soilno' => $soilno,
                    'type' => self::$sowArr[$type],
                    'uin' => $this->uin,
                    'sid' => $this->sid,
                );
                
                $conn = new HttpConn(self::MEIGUI_URL, self::$actionUrl['sow'], $data);
                print date('Y-m-d H:i:s')."\t 种花\tsoilno:$soilno\n";
                $str = $conn->connect();
                sleep(1);
            }
            $this->flushPage();
        }
    }
}
while(true) {
$uin = '';
$sid = '';
$meigui = new meigui();
$meigui->uin = $uin;
$meigui->sid = $sid;
$meigui->isLoad = true;
$meigui->flushPage();
$meigui->processUrl();
$meigui->sowFlower();
$sleep_time = 3600*rand(4,5) + 100*rand(1,10);
sleep($sleep_time);
}
