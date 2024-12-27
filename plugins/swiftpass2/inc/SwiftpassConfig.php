<?php
$swiftpass_config = [
    //网关地址(留空为默认)
    'gateway_url' => $channel['appurl'],
    
    //签名类型
    'sign_type' => 'MD5',

    //商户号
    'mchid' => $channel['appid'],

    //商户MD5密钥
    'key' => $channel['appkey'],
];
return $swiftpass_config;