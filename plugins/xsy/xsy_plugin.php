<?php

class xsy_plugin
{
    static $info = [
        'name' => 'xsy',       // 插件标识
        'showname' => '新生易',          // 插件显示名称
        'author' => '新生易',                 // 作者信息
        'link' => 'https://www.hnapay.com/',                 // 支付官网
        'types' => ['wxpay', 'alipay', 'bank'], // 可支持的支付类型
        'inputs' => [ // 配置信息表单
            'appid' => [
                'name' => '机构代码',
                'type' => 'input',
            ],
            'appkey' => [
                'name' => '平台公钥',
                'type' => 'textarea',
            ],
            'appsecret' => [
                'name' => '商户私钥',
                'type' => 'textarea',
            ],
            'appmchid' => [
                'name' => '商户编号',
                'type' => 'input',
            ],
            'appswitch' => [
				'name' => '环境选择',
				'type' => 'select',
				'options' => [0=>'生产环境',1=>'测试环境'],
			],
        ],
        'bindwxmp' => true, // 绑定微信公众号
        'bindwxa' => true, // 绑定微信小程序
        'note' => '',
    ];

    public static function submit()
    {
        global $siteurl, $channel, $order;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			if(checkwechat()){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile() && $channel['appwxa']>0){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
    }

    public static function mapi()
    {
        global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && $channel['appwxa']>0){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
    }

    //扫码支付
    private static function qrcode($pay_type)
    {
        global $siteurl, $channel, $order, $ordername, $conf, $clientip;
        require(PAY_ROOT . 'lib/PayClient.php');

        $param = [
            'merchantNo' => $channel['appmchid'],
            'orderNo' => TRADE_NO,
            'amt' => intval(round($order['realmoney']*100)),
            'payType' => $pay_type,
            'subject' => $order['name'],
            'trmIp' => $clientip,
            'notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/'
        ];

        $client = new \xsy\PayClient($channel['appid'], $channel['appkey'], $channel['appsecret'], $channel['appswitch'] == 1);
        return \lib\Payment::lockPayData(TRADE_NO, function () use ($client, $param) {
            $result = $client->request('/trade/activeScan', $param);
            if(strpos($result['payUrl'], 'qrContent=')){
                $result['payUrl'] = getSubstr($result['payUrl'], 'qrContent=', '&sign=');
            }
            return $result['payUrl'];
        });
    }

    //公众号小程序支付
    private static function jsapi($pay_type, $pay_way, $appid, $userid)
    {
        global $siteurl, $channel, $order, $ordername, $conf, $clientip;
        require(PAY_ROOT . 'lib/PayClient.php');

        $param = [
            'merchantNo' => $channel['appmchid'],
            'orderNo' => TRADE_NO,
            'amt' => intval(round($order['realmoney']*100)),
            'payType' => $pay_type,
            'payWay' => $pay_way,
            'subAppId' => $appid,
            'userId' => $userid,
            'subject' => $order['name'],
            'trmIp' => $clientip,
            'notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/'
        ];

        $client = new \xsy\PayClient($channel['appid'], $channel['appkey'], $channel['appsecret'], $channel['appswitch'] == 1);
        return \lib\Payment::lockPayData(TRADE_NO, function () use ($client, $param) {
            $result = $client->request('/trade/jsapiScan', $param);
            return $result;
        });
    }

    //支付宝扫码支付
    public static function alipay()
    {
        try {
            $url = self::qrcode('ALIPAY');
        } catch (Exception $e) {
            return ['type'=>'error','msg'=>'支付宝下单失败！'.$e->getMessage()];
        }
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return ['type' => 'jump', 'url' => $url];
        } else {
            return ['type' => 'qrcode', 'page' => 'alipay_qrcode', 'url' => $url];
        }
    }

    //微信扫码支付
    public static function wxpay()
    {
        global $siteurl;
        $code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
        if (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
    }

    //微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		//①、获取用户openid
		$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
		if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
		try{
			$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
			$openid = $tools->GetOpenid();
		}catch(Exception $e){
			return ['type'=>'error','msg'=>$e->getMessage()];
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		try{
			$result = self::jsapi('WECHAT', '02', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

        $payinfo = ['appId'=>$result['payAppId'], 'timeStamp'=>$result['payTimeStamp'], 'nonceStr'=>$result['paynonceStr'], 'package'=>$result['payPackage'], 'signType'=>$result['paySignType'], 'paySign'=>$result['paySign']];

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>json_encode($payinfo), 'redirect_url'=>$redirect_url]];
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');
		
		//①、获取用户openid
		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');
		try{
			$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
			$openid = $tools->AppGetOpenid($code);
		}catch(Exception $e){
			exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');
		
		//②、统一下单
		try{
			$result = self::jsapi('WECHAT', '03', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

        $payinfo = ['appId'=>$result['payAppId'], 'timeStamp'=>$result['payTimeStamp'], 'nonceStr'=>$result['paynonceStr'], 'package'=>$result['payPackage'], 'signType'=>$result['paySignType'], 'paySign'=>$result['paySign']];

		exit(json_encode(['code'=>0, 'data'=>$payinfo]));
	}

	//微信手机支付
	static public function wxwappay(){
        global $channel;
		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
        if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
        try{
            $code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
        }catch(Exception $e){
            return ['type'=>'error','msg'=>$e->getMessage()];
        }
        return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
	}

    //云闪付扫码支付
    public static function bank()
    {
        try {
            $url = self::qrcode('UNIONPAY');
        } catch (Exception $e) {
            return ['type'=>'error','msg'=>'云闪付下单失败！'.$e->getMessage()];
        }
        return ['type' => 'qrcode', 'page' => 'bank_qrcode', 'url' => $url];
    }

    //回调
    public static function notify()
    {
        global $channel, $order;
        $data = file_get_contents('php://input');
        $arr = json_decode($data, true);
        if (!$arr) return ['type' => 'html', 'data' => '{"code":"nodata"}'];

        require(PAY_ROOT . 'lib/PayClient.php');
        $client = new \xsy\PayClient($channel['appid'], $channel['appkey'], $channel['appsecret'], $channel['appswitch'] == 1);

        try {
            if (!$client->verifySign($arr, $data)){
                return ['type' => 'html', 'data' => '{"code":"fail"}'];
            }
            $out_trade_no = $arr['respData']['orderNo'];
            $api_trade_no = $arr['respData']['outOrderNo'];
            $buyer = $arr['respData']['buyerId'];
            if($out_trade_no == TRADE_NO){
                processNotify($order, $api_trade_no, $buyer);
            }
            return ['type' => 'html', 'data' => '{"code":"success"}'];
        } catch (Exception $e) {
            return ['type' => 'html', 'data' => '{"code":"'.$e->getMessage().'"}'];
        }
    }

    //退款
    public static function refund($order)
    {
        global $channel, $order;

        require(PAY_ROOT . 'lib/PayClient.php');

        $param = [
            'merchantNo' => $channel['appmchid'],
            'orderNo' => $order['refund_no'],
            'origOrderNo' => $order['trade_no'],
            'amt' => intval(round($order['refundmoney']*100))
        ];

        try {
            $client = new \xsy\PayClient($channel['appid'], $channel['appkey'], $channel['appsecret'], $channel['appswitch'] == 1);
            $result = $client->request('/trade/refund', $param);
            return ['code' => 0, 'trade_no'=>$result['orderNo'], 'refund_fee'=>$result['amt']/100];
        } catch (Exception $e) {
            return ['code' => -1, 'msg' => $e->getMessage()];
        }
    }
}