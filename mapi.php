<?php
if(!isset($_POST['pid'])){
	@header('Content-Type: application/json; charset=UTF-8');
	exit('{"code":-4, "msg":"未传入任何参数"}');
}
$nosession = true;
require './includes/common.php';

(new \lib\api\Pay())->create();
