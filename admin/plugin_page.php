<?php
/**
 * 支付插件页面
**/
include("../includes/common.php");
$title='支付插件页面';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
$channelid = isset($_GET['channel'])?intval($_GET['channel']):0;
$func = isset($_GET['func'])?$_GET['func']:'';
if($channelid == 0 || empty($func)) showmsg('参数错误',3);
$channel = \lib\Channel::get($channelid);
if(!$channel) showmsg('当前支付通道不存在',3);

try{
    \lib\Plugin::loadForAdmin($func);
}catch(Exception $e){
    showmsg($e->getMessage(),3);
}
?>
 </div>
</div>