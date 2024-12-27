<?php
/* *
 * 配置文件
 */

$epay_config = [
    //支付接口地址
    'apiurl' => $channel['appurl'],

    //商户ID
    'pid' => $channel['appid'],

    //平台公钥
    'platform_public_key' => $channel['appkey'],

    //商户私钥
    'merchant_private_key' => $channel['appsecret'],
];