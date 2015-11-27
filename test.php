<?php
include('mns.php');
//参数为开发者申请阿里MNS的秘钥信息以及持有者id
$mns_queue=new Queue($key, $secret, $queueownerid, $mqsurl);//
//随后使用实例化的类调封装函数,消息收发同理
