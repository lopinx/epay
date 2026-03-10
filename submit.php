<?php
if(!isset($_GET['pid']) && !isset($_POST['pid'])){
	@header('Content-Type: text/html; charset=UTF-8');
	exit('你还未配置支付接口商户！');
}
$nosession = true;
require './includes/common.php';

(new \lib\api\Pay())->submit();
