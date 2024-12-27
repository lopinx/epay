<?php

$wechatpay_config = [
	//绑定支付的APPID
	'appid' => $channel['appid'],

	//商户号
	'mchid' => $channel['appmchid'],

	//商户APIv2密钥
	'apikey' => $channel['appkey'],

	//公众帐号secert（仅JSAPI支付需要配置）
	'appsecret' => '',

	//商户证书路径（仅退款、撤销订单时需要）
	'sslcert_path' => PLUGIN_ROOT.$channel['plugin'].'/cert/apiclient_cert.pem',

	//商户证书私钥路径
	'sslkey_path' => PLUGIN_ROOT.$channel['plugin'].'/cert/apiclient_key.pem',

	//付款到银行卡的RSA公钥
	'publickey_path' => PLUGIN_ROOT.$channel['plugin'].'/cert/public_key.pem',
];

if(file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_cert.pem') && file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_key.pem')){
	$wechatpay_config['sslcert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_cert.pem';
	$wechatpay_config['sslkey_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/apiclient_key.pem';
	$wechatpay_config['publickey_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appmchid'].'/public_key.pem';
}

return $wechatpay_config;