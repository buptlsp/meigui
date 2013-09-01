<?php
require_once(ROOT_DIR."/lib/curl.php");
require_once(ROOT_DIR."/lib/rb.php");
date_default_timezone_set("Asia/Chongqing");
class Meigui
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
    private function login($qq , $pwd)
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
        $regex = '/http:\/\/cgi.meigui.qq.com\/cgi-bin\/m_action\?[\w\d=_-&;]+/i';
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
    private function flushPage()
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

	private function print_log($data)
	{
        $time = date('Y-m-d H:i:s');
		$str = "$time\tqq=$this->uin";
		foreach($data as $key => $val) {
		    $str .= "&$key=$val";
		}
		$str .= "\n";
	    file_put_contents(LOG_FILE, $str, FILE_APPEND);
	}

    //获取一个符串内所有的需要访问的链接
    private function getAllUrl($str)
    {
        $url = array();
        //四种需要访问的链接 
        $arr = array('gain', 'dig', 'get', 'action');
        foreach($arr as $value){
            $url[$value] = array();
            if($value != 'get'){
                $regex = '#'.self::MEIGUI_URL.self::$actionUrl[$value].'[?\w\d=;&_-]+#';
            } else {
                $regex = '#'.self::MEIGUI_URL.self::$actionUrl[$value].'[?\w\d=;&_-]+cmd=3[?\w\d=;&_-]+soilno[?\w\d=;&_-]+#';
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
    private function processUrl(){
        //先依次返问完所有的需要收的花
        $arr = $this->getUrl['gain'];
        if(!empty($arr)) {
            foreach($arr as $url) {
                $url = htmlspecialchars_decode($url);
                $conn = new HttpConn($url, '', array());
                $str = $conn->connect();
				$this->print_log(array('action' => '收花'));
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
				$this->print_log(array('action' => '铲花'));
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
				$this->print_log(array('action' => '除草,杀虫，晒太阳', 'url'=> $url));
                sleep(1);
            }
            $this->flushPage();
        }
    }

    private function buyFlower($type = 133, $count = 1){
        $data = array(
            'type' => $type,
            'count' => $count,
            'cmd' => 5,
            'uin' => $this->uin,
            'sid' => $this->sid,
        );
		$conn = new HttpConn(self::MEIGUI_URL, self::$actionUrl['buy'], $data);	
		$this->print_log(array('action' => '买花', 'type' => $type, 'count' => $count));
		$str = $conn->connect();
		sleep(1);
    }
	
	//种花
	private function sowFlower($type, $soilno) {
		$data = array(
			'soilno' => $soilno,
			'type' => $type,
			'uin' => $this->uin,
			'sid' => $this->sid,
		);
		
		$conn = new HttpConn(self::MEIGUI_URL, self::$actionUrl['sow'], $data);
		$str = $conn->connect();
		$this->print_log(array('action' => '种花', 'type' => $type, 'soilno' => $soilno));
		sleep(1);
	}
	
	//获取所有需要种的花盆
    private function getNeedSowSoilno()
    {
		$soilnos = array();	
		//暂时先种梅花
        $arr = $this->getUrl['get'];
        if(!empty($arr)) {
            foreach($arr as $url) {
                $url = htmlspecialchars_decode($url);
                $data = self::extractArg($url);
                if(!isset($data['soilno'])){
                    continue;
				}
                $soilnos[] = intval($data['soilno']);
			}
		}
        return $soilnos;
    }
    
	public static function processByUser($user)
	{
		$meigui = new self();
		$meigui->uin = $user->uin;
		$meigui->sid = $user->sid;
		$meigui->isLoad = true;
		$meigui->flushPage();
	    //处理该用户所有的收花铲花之类的操作
		$meigui->processUrl();
		$meigui->flushPage();
  
		//获取该用户所有的需要种的花
		R::setup(DB_CONN,DB_USERNAME,DB_PASSWORD);
		$crons = R::findAll('cron', 'where userid = ? and sownum < neednum', array($user->id));
		$sowFlowers = array();
		$soilnos = $meigui->getNeedSowSoilno();
		foreach($soilnos as $soilno) {
			$soil = R::load('soil', $soilno);
	        foreach($crons as $cron) {
				if($cron->flowertype == $soil->type && $cron->sownum < $cron->neednum) {
				    $flower = R::load('flower', $cron->flowerid); 	
					$meigui->buyFlower($flower->buyid);
					$meigui->sowFlower($flower->sowid, $soilno);
					$cron->sownum ++ ;
					break;
				}	
			} 
		}
        //在完成播种后，把所有的定时任务完成的结果保存至数据库中
		foreach($crons as $cron) {
		    R::store($cron);
		}

		//重新运行一次，将未种上花的盆子种上默认的花
		$meigui->flushPage();
		$meigui->processUrl();
		$meigui->flushPage();
		$soilnos = $meigui->getNeedSowSoilno();
	    foreach($soilnos as $soilno) {
		    $soil = R::load('soil', $soilno);
			$flowerid = $user->soil_flower;
			if($soil->type == 1) {
			    $flowerid = $user->water_flower;
			}
			$flower = R::load('flower', $flowerid);
			$meigui->buyFlower($flower->buyid);
			$meigui->sowFlower($flower->sowid, $soilno);
		}
	}
}

