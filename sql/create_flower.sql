use meigui;
CREATE TABLE `flower` (
	 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
	 `name` varchar(32) NOT NULL DEFAULT '' COMMENT '花的名字',
     `buyid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '购买id',
     `sowid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '播种id',
     `growup` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '花的成熟时间',
     `price` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '花的价钱',
     `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '花的品类，0常规1水生花2多季花3阳台花',
     PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='花';

CREATE TABLE `soil` (
	 `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `type` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '盆子的种类',
     PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='花盆';

CREATE TABLE `user` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `uin` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'QQ号码',
    `sid` varchar(128) NOT NULL DEFAULT '' COMMENT 'sid',
    `status` tinyint(3) NOT NULL DEFAULT '0' COMMENT '状态0代表收取，1代表不收取',
    `money` int(10) NOT NULL DEFAULT '0' COMMENT '钱数',
    PRIMARY KEY (`id`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户';

CREATE TABLE `cron` (
	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `userid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'userid',
    `flowerid` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'flowerid',
    `flowertype` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '花所需盆的种类，0为土盆，1为水盆',
    `neednum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '数量',
    `sownum` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '播种数量',
    PRIMARY KEY (`id`),
    KEY `userid` (`userid`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='定时任务';

