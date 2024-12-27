<?php

$qqpay_config = [
	//商户号
	'mchid' => $channel['appid'],

	//商户API密钥
	'apikey' => $channel['appkey'],

    //公众号APPID（可空）
	'appid' => '',

    //操作员账号（仅退款、撤销订单、企业付款时需要）
	//创建操作员说明：https://kf.qq.com/faq/170112AZ7Fzm170112VNz6zE.html
    'op_userid' => $channel['appurl'],

    //操作员密码
    'op_userpwd' => $channel['appmchid'],

	//商户证书路径（仅退款、撤销订单、企业付款时需要）
	'sslcert_path' => PLUGIN_ROOT.$channel['plugin'].'/cert/apiclient_cert.pem',

	//商户证书私钥路径
	'sslkey_path' => PLUGIN_ROOT.$channel['plugin'].'/cert/apiclient_key.pem',
];

if(file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/apiclient_cert.pem') && file_exists(PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/apiclient_key.pem')){
	$qqpay_config['sslcert_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/apiclient_cert.pem';
	$qqpay_config['sslkey_path'] = PLUGIN_ROOT.$channel['plugin'].'/cert/'.$channel['appid'].'/apiclient_key.pem';
}

return $qqpay_config;