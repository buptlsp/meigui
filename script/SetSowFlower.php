<?php
define("ROOT_DIR", dirname(__FILE__));
include_once(ROOT_DIR.'/config/config.php');
include_once(ROOT_DIR.'/lib/rb.php');
R::setup(DB_CONN,DB_USERNAME,DB_PASSWORD);
if(count($argv) < 3) {
    echo $argv[0]." + qq + <flowername> + <num> \n";
	die;
}
$uin = intval($argv[1]);
$flowername = $argv[2];
$num = intval($argv[3]);
insertCron($uin, $flowername, $num);

function insertCron($uin, $flowername, $num)
{
	$users = R::findAll('user', " where uin=? ", array($uin));
	$flowers = R::findAll('flower', 'where name = ? ', array($flowername));
	$user = current($users);
	$flower = current($flowers);
	$cron = R::dispense('cron');
	$cron->userid = $user->id;
	$cron->flowerid = $flower->id;
	$cron->flowertype = $flower->type == 1 ? 1:0;
	$cron->neednum = $num;
	$cron->sownum = 0;
	R::store($cron);
}
