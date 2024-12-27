<?php
$swiftpass_config = [
    //网关地址(留空为默认)
    'gateway_url' => $channel['appurl'],
    
    //签名类型
    'sign_type' => 'RSA_1_256',

    //商户号
    'mchid' => $channel['appid'],

    //平台RSA公钥
    'rsa_public_key' => $channel['appkey'],

    //商户RSA私钥
    'rsa_private_key' => $channel['appsecret'],
];
return $swiftpass_config;