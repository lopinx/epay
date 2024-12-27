<?php

class easypay_plugin
{
	static public $info = [
		'name'        => 'easypay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '易生支付', //支付插件显示名称
		'author'      => '易生', //支付插件作者
		'link'        => 'https://www.easypay.com.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '机构号',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '设备号',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '将密钥上传到 /plugins/easypay/cert ，易生公钥pay.pem，商户私钥mch.key', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			if(checkwechat()){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile()){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return self::wxjspay();
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//扫码支付接口
	static private function qrcode($tradeCode){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/PayApp.class.php");

		$orgTrace = substr($channel['appid'], 0, 4) . substr($channel['appid'], -4) . TRADE_NO;

		$params = [
			'orgBackUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'tradeCode' => $tradeCode,
			'tradeAmt' => intval(round($order['realmoney']*100)),
			'orderInfo' => $ordername,
			'infoAttach' => TRADE_NO,
		];

		$client = new \easypay\PayApp($channel['appid'],$channel['appmchid'],$channel['appkey']);
		$result = \lib\Payment::lockPayData(TRADE_NO, function() use($client, $orgTrace, $params) {
			return $client->paySubmit('/standard/native', $orgTrace, $params);
		});
		if($result['finRetcode'] == '99'){
			return $result['qrCode'];
		}else{
			throw new Exception('['.$result['appendRetcode'].']'.$result['appendRetmsg']);
		}
	}

	//JSAPI支付接口
	static private function jsapi($tradeCode, $appid, $openid){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/PayApp.class.php");

		$orgTrace = substr($channel['appid'], 0, 4) . substr($channel['appid'], -4) . TRADE_NO;

		$params = [
			'orgBackUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'orgFrontUrl' => $siteurl . 'pay/return/' . TRADE_NO . '/',
			'payerId' => $openid,
			'tradeCode' => $tradeCode,
			'tradeAmt' => intval(round($order['realmoney']*100)),
			'orderInfo' => $ordername,
			'infoAttach' => TRADE_NO,
			'wxSubAppid' => $appid,
		];

		$client = new \easypay\PayApp($channel['appid'],$channel['appmchid'],$channel['appkey']);
		$result = \lib\Payment::lockPayData(TRADE_NO, function() use($client, $orgTrace, $params) {
			return $client->paySubmit('/standard/jsapi', $orgTrace, $params);
		});
		if($result['finRetcode'] == '99'){
			return $result;
		}else{
			throw new Exception('['.$result['appendRetcode'].']'.$result['appendRetmsg']);
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::qrcode('WAC2B');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $siteurl;

		$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';

		if(checkwechat()){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl, $channel, $order;

        if ($channel['appwxa']>0) {
            $wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
            try {
                $code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
            } catch (Exception $e) {
                return ['type'=>'error','msg'=>$e->getMessage()];
            }
            return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
        }elseif($channel['appwxmp']>0){
			$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}else{
			return self::wxpay();
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel;

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
			$result = self::jsapi('WTJS1', $wxinfo['appid'], $openid);
			$payinfo = $result['wxWcPayData'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$payinfo, 'redirect_url'=>$redirect_url]];
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel;

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
			$result = self::jsapi('WTJS2', $wxinfo['appid'], $openid);
			$payinfo = $result['wxWcPayData'];
		}catch(Exception $ex){
			exit('{"code":-1,"msg":"微信支付下单失败！'.$ex->getMessage().'"}');
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($payinfo, true)]));
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode('WUC2B');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr) return ['type'=>'html','data'=>'no data'];

		require(PAY_ROOT."inc/PayApp.class.php");
		
		$client = new \easypay\PayApp($channel['appid'],$channel['appmchid'],$channel['appkey']);
		$verify_result = $client->verifySign($arr['data'], $arr['sign']);

		if($verify_result) {//验证成功

			if ($arr['data']['finRetcode'] == '00') {
				$out_trade_no = substr($arr['data']['oriOrgTrace'], 8);
				$api_trade_no = $arr['data']['outTrace'];
				$money = $arr['data']['payerAmt'];
				$buyer = $arr['data']['payerId'];
				if($out_trade_no == TRADE_NO && intval(round($order['realmoney']*100)) == $money){
					processNotify($order, $api_trade_no, $buyer);
				}
			}
			return ['type'=>'html','data'=>'ok'];
		}
		else {
			return ['type'=>'html','data'=>'error'];
		}
	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/PayApp.class.php");

		$orgTrace = substr($channel['appid'], 0, 4) . substr($channel['appid'], -4) . $order['refund_no'];

		$params = [
			'oriOutTrace' => $order['api_trade_no'],
			'oriBizDate' => substr($order['trade_no'], 0, 14),
			'transAmt' => strval($order['refundmoney']*100),
		];
		
		try{
			$client = new \easypay\PayApp($channel['appid'],$channel['appmchid'],$channel['appkey']);
			$result = $client->refundSubmit('/ledger/mposrefund', $orgTrace, $params);

			return ['code'=>0, 'trade_no'=>$result['outTrace'], 'refund_fee'=>$result['transAmt']/100];

		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}
}