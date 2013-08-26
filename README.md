Meigui -玫瑰花园外挂 
======

开发的原因
======
    有时候喜欢打个斗地主，没欢乐豆时就比较郁闷了。于是就跟着种起了玫瑰花园，但是又不愿意打理，于是开发了脚本自动收梅花，种梅花，每天能刷几千的欢乐豆。有时候玩的时候直接去花园卖花就行了。
    后来，有了更高级的需求。有的人想按任务来种花，早期就只能固定死种梅花等，因此重构了整体的代码。

部署所需要的环境
======
    1. php + mysql 
    2. 一台联网的linux主机（window应该也可以，但是没有试过）

怎样安装使用
======
    1. 安装php并安装好mysql
    2. 将本代码下载至您的机器。
    3. cd meigui
       vi config/meigui.php  //将对应配置文件中的数据库连接，数据库的用户名，密码等改成自己对应的配置
       php install.php   //抓取网页，生成相关的数据库
       在user表中，添加你平时想要自动收花的qq和sid。
       设置crontab
