<?php
//抓取花的内容
include_once('../lib/rb.php');
require_once("../lib/curl.php");
require_once("../lib/simple_html_dom.php");
require_once("../conf/conf.php");
R::setup(DB_CONN, DB_USERNAME, DB_PASSWORD);
//获取一个用户
$users = R::findAll('user','id != ?', array(0));
$user = $users[0];
echo "获取花的相关数据中...\n";
$page = 1;
while($page<7){
	$data = array(
		'cmd' => 1,
		'page' => $page,
		'uin' => $user->uin,
		'sid' => $user->sid,
	);
	$conn = new HttpConn("http://cgi.meigui.qq.com/cgi-bin", "/m_buy", $data);
	$conn->unUseCookie();
	$str = $conn->connect();
	$html = new simple_html_dom();
	$html->load($str);
	$a = $html->find('a[href]');
	$str = $a[0]->parent()->plaintext;
	$regex = "/\d+，(?P<name>[^\s(]+)[^\s]+\s+单价：(?P<money>\d+)金币\s周期:\s(?P<hour>\d+)小时/";
	if(preg_match_all($regex, $str, $m)){
		$arr = array();
		$i = 0;
		while($i < count($m['name'])){
			$name = preg_replace('/种子/', '', $m['name'][$i]);
			$arr[$m['name'][$i]] = array(
				'name' => $name,
				'money' => intval($m['money'][$i]),
				'hour' => intval($m['hour'][$i]),
			);
			$i++;
		}
		foreach($a as $item){
			if(preg_match("/种子/",$item->plaintext) && preg_match("/cmd=4/",$item->href)) {
				if(preg_match('/type=(?P<type>\d+)/', $item->href, $m)) {
					if(isset($arr[$item->plaintext])) {
						$buyid = intval($m['type']);
						$arr[$item->plaintext]['buyid'] = $buyid;
						if($item->plaintext == "萍逢草种子"){
							$arr[$item->plaintext]['sowid'] = 701;
						} elseif ($item->plaintext == "千屈菜种子") {
							$arr[$item->plaintext]['sowid'] = 700;
						} else {
							if($buyid <1000) {
								$arr[$item->plaintext]['sowid'] = $buyid + 100;
						    } else {
								$arr[$item->plaintext]['sowid'] = $buyid + 2000;
							}
						}
					}
				}
			}
		}
	}
	foreach($arr as $item) {
	    $flower = R::dispense("flower");
		$flower->name = $item['name'];
		$flower->price = $item['money'];
		$flower->growup = $item['hour'];
		$flower->buyid = $item['buyid'];
		$flower->sowid = $item['sowid'];
		if($flower->sowid < 700) {
		    $flower->type = 0;
		} elseif($flower->sowid>700 && $flower->sowid<=1000) {
			$flower->type = 1;
		} else {
			$flower->type = 2;
		    if($flower->growup == 0) {
			    $flower->type = 3;
			}
		}
		R::store($flower);
	}
	$html->clear();
	$page++;
	sleep(1);
}

echo "创建花盆数据...\n";
$i = 1;
while($i<=18){
	$soil = R::dispense('soil');
    $soil->type = 0;
	if(in_array($i, array(7,8,18))){
	    $soil->type = 1;
	}
	R::store($soil);
	$i++;
}

