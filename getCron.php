<?php
define("ROOT_DIR", dirname(__FILE__));
define('LOG_FILE',  ROOT_DIR."/log/meigui.log");
include_once(ROOT_DIR.'/lib/rb.php');
include_once(ROOT_DIR.'/config/config.php');
require_once(ROOT_DIR."/lib/BaiduPCS.class.php");
R::setup(DB_CONN,DB_USERNAME,DB_PASSWORD);
//获取所有用户,
$access_token = ACCESS_TOKEN; 
$pcs = new BaiduPCS($access_token); 

//获取当前的时间
$TS = strtotime("now");
//应用目录
$appName = 'testdir';
//应用根目录
$root_dir = '/apps' . '/' . $appName . '/';

$fileName = 'test.txt';
//文件路径
$path = $root_dir . $fileName;
$pcs = new BaiduPCS($access_token);

header('Content-Disposition:attachment;filename="' . $fileName . '"');
header('Content-Type:application/octet-stream');
$result = $pcs->download($path);

//解析文件，并将相应的内容插入至数据库
//获取上次截止时间
$updateFile = ROOT_DIR."/log/updateTime.log";
$lasttime = file_get_contents($updateFile);
$lasttime = intval(trim($lasttime));
$arr = explode("\n", $result);
$newText = "";
foreach($arr as $text){
	if(!$text){
		$newText .= $text."\n";	
		continue;	
	}
	$line = explode("\t", $text);
    if(count($line) == 3){
		 $time = intval($line[0]);
		 $name = $line[1];	
		 $count = intval($line[2]);
		 //判断该记录是否更新过
		 if($time <= $lasttime) {
			  $newText .= $text."\n";	 
			  continue;
		 }
		 $flower = R::findOne('flower', 'name = ?', array($name));
		 if(!empty($flower)) {
			 $cron = R::dispense('cron');
			 $cron->userid = 1;
			 $cron->flowerid = $flower->id;
			 $cron->flowertype = $flower->type == 1 ? 1 : 0;
			 $cron->neednum = $count;
			 $cron->sownum = 0;
			 R::store($cron);
	    }
	} else {
	    $newText .= $text."\n";
	}
}
//更新时间
file_put_contents($updateFile, $TS);
print $newText;
//将已处理的从文件中删除
$pcs->upload($newText, $root_dir, $fileName, null);
