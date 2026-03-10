<?php

class umfpay_plugin
{
	static public $info = [
		'name'        => 'umfpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '联动优势', //支付插件显示名称
		'author'      => '联动优势', //支付插件作者
		'link'        => 'https://xy.umfintech.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户编号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户密钥',
				'type' => 'input',
				'note' => '此项随便填写',
			],
		],
		'select' => null,
		'note' => '将平台公钥cert.pem和商户私钥key.pem放到/plugins/umfpay/cert/文件夹下', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			if(checkwechat()){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
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
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//扫码支付
	static private function qrcode($type){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/UmfService.class.php");

		$params = [
			'service' => 'active_scancode_order_new',
			'notify_url' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'goods_inf' => $ordername,
			'order_id' => TRADE_NO,
			'mer_date' => date("Ymd"),
			'amount' => strval($order['realmoney']*100),
			'user_ip' => $clientip,
			'scancode_type' => $type,
			'mer_flag' => 'KMER',
			'consumer_id' => str_replace('.','',$clientip)
		];

		$client = new UmfService($channel['appid']);
		$result = $client->submit($params);
		
        if (isset($result['ret_code']) && $result['ret_code'] == '0000') {
			return base64_decode($result['bank_payurl']);
        } elseif(isset($result['ret_code'])) {
			throw new Exception('['.$result['ret_code'].']'.$result['ret_msg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::qrcode('ALIPAY');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		try{
			$code_url = self::qrcode('WECHAT');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT."inc/UmfService.class.php");

		$params = [
			'service' => 'publicnumber_and_verticalcode',
			'notify_url' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'ret_url' => $siteurl . 'pay/return/' . TRADE_NO . '/',
			'goods_inf' => $ordername,
			'order_id' => TRADE_NO,
			'mer_date' => date("Ymd"),
			'amount' => strval($order['realmoney']*100),
			'user_ip' => $clientip,
			'is_public_number' => 'Y',
		];

		$client = new UmfService($channel['appid']);
		$url = $client->getpayurl($params);
		
        return ['type'=>'jump','url'=>$url];
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::qrcode('UNION');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require(PAY_ROOT."inc/UmfService.class.php");
		
		$client = new UmfService($channel['appid']);
		$verify_result = $client->verifySign($_GET);

		$params = [
			'order_id' => $_GET['order_id'],
			'mer_date' => $_GET['mer_date'],
		];

		if($verify_result) {//验证成功

			if ($_GET['trade_state'] == 'TRADE_SUCCESS') {
				if($_GET['order_id'] == TRADE_NO){
					processNotify($order, $_GET['trade_no'], $_GET['mer_cust_id']);
				}
			}else{
				$params['ret_code'] = '0001';
				$params['ret_msg'] = 'trade_state'.$_GET['trade_state'];
			}
		}
		else {
			//验证失败
			$params['ret_code'] = '0001';
			$params['ret_msg'] = 'sign fail';
		}

		$response_str = $client->responseUMFstr($params);

		$html = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">';
		$html .= '<html><head>';
		$html .= '<META NAME="MobilePayPlatform" CONTENT="'.$response_str.'">';
		$html .= '</head>';
		$html .= '<body></body>';
		$html .= '</html>';

		return ['type'=>'html','data'=>$html];
	}

	//同步回调
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require(PAY_ROOT."inc/UmfService.class.php");

		$params = [
			'service' => 'mer_refund',
			'refund_no' => $order['refund_no'],
			'order_id' => $order['trade_no'],
			'mer_date' => substr($order['trade_no'], 0, 8),
			'org_amount' => strval($order['realmoney']*100),
			'refund_amount' => strval($order['refundmoney']*100),
		];
		
		try{
			$client = new UmfService($channel['appid']);
			$result = $client->submit($params);
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}

        if (isset($result['ret_code']) && $result['ret_code'] == '0000') {
			return ['code'=>0, 'trade_no'=>$result['order_id'], 'refund_fee'=>$result['refund_amt']];
        } elseif(isset($result['ret_code'])) {
			return ['code'=>-1, 'msg'=>'['.$result['ret_code'].']'.$result['ret_msg']];
		}else{
			return ['code'=>-1, 'msg'=>'未知错误'];
		}
	}
}