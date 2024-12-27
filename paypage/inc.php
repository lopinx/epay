<?php
include("../includes/common.php");

function showerror($msg){
	global $cdnpublic;
	include ROOT.'paypage/error.php';
	exit;
}

function showerrorjson($msg){
	$result = ['code'=>-1, 'msg'=>$msg];
	exit(json_encode($result));
}

function check_paytype(){
	$type=null;
	if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger/')!==false){
		$type='wxpay';
	}elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient/')!==false){
		$type='alipay';
	}elseif(strpos($_SERVER['HTTP_USER_AGENT'], 'QQ/')!==false){
		$type='qqpay';
	}
	return $type;
}

function alipayOpenId($channel){
	global $DB,$siteurl;
	$channel = \lib\Channel::get($channel);
	if(!$channel)showerror('支付通道不存在');
	$alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
	try{
		$oauth = new \Alipay\AlipayOauthService($alipay_config);
		if(isset($_GET['auth_code'])){
			$result = $oauth->getToken($_GET['auth_code']);
			if(!empty($result['user_id'])){
				$openid = $result['user_id'];
			}else{
				$openid = $result['open_id'];
			}
			return $openid;
		}else{
			$redirect_uri = $siteurl.'paypage/';
			$oauth->oauth($redirect_uri);
		}
	}catch(Exception $e){
		showerror('支付宝快捷登录失败！'.$e->getMessage());
	}
}

function weixinOpenId($channel){
	global $DB;
	$channel = \lib\Channel::get($channel);
	if(!$channel)showerror('支付通道不存在');
	
	$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
	if(!$wxinfo)showerror('支付通道绑定的微信公众号不存在');

	try{
		$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
		$openId = $tools->GetOpenid();
	}catch(Exception $e){
		showerror($e->getMessage());
	}
	return $openId;
}
