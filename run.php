<?php
define("ROOT_DIR", dirname(__FILE__));
define('LOG_FILE',  ROOT_DIR."/log/meigui.log");
include_once(ROOT_DIR.'/lib/rb.php');
include_once(ROOT_DIR.'/config/config.php');
include_once(ROOT_DIR.'/script/meigui.php');
R::setup(DB_CONN,DB_USERNAME,DB_PASSWORD);
//获取所有用户,
$users = R::findAll('user', 'ORDER BY id DESC');
foreach($users as $user){
	Meigui::processByUser($user);
}
