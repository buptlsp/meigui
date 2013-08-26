<?php
class HttpConn {
    private $host;
    private $urltail = "/";
    private $cookie = "./cookie.txt";
    private $method = "GET";
    private $useCookie = true;
    private $data = array();
    private $useagent = 'Chrome/9.0.587.0';

    public function __construct($host, $urltail="/", $data=array())
    {
        $this->host = $host;
        $this->urltail = $urltail;
        $this->data = $data;
    }

    public function connect()
    {
        $url = $this->host.$this->urltail;
        $ch = curl_init();
        $curl_data = http_build_query($this->data);
        if(strcmp($this->method, 'GET') == 0) {
            if($curl_data && !empty($this->data)){
                $url .= "?".$curl_data;
            }
        }else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curl_data);
		}
        if($this->useCookie){
            // 设置从$cookie所指文件中读取cookie信息以发送
            curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
            // 设置将返回的cookie保存到$cookie所指文件
            curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        }
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useagent);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }

    public function unUseCookie()
    {
        $this->useCookie = false;
    }

    public function setPost()
    {
        $this->method = 'POST';
    }
    public function setData($data)
    {
        $this->data = $data;
    }

}
