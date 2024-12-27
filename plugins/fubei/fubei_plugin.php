<?php

class fubei_plugin
{
	static public $info = [
		'name'        => 'fubei', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '付呗聚合支付', //支付插件显示名称
		'author'      => '付呗', //支付插件作者
		'link'        => 'https://www.51fubei.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '开放平台ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '接口密钥',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '门店ID',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
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
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}else{
				return self::wxpay();
			}
		}
	}

	//下单通用
	static private function addOrder($pay_type, $user_id, $sub_appid = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');

		$bizContent = [
			'merchant_order_sn' => TRADE_NO,
			'pay_type' => $pay_type,
			'total_amount' => $order['realmoney'],
			'store_id' => $channel['appmchid'],
			'user_id' => $user_id,
			'body' => $ordername,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		];
		if($sub_appid) $bizContent['sub_appid'] = $sub_appid;

		$client = new FubeiClient($channel['appid'], $channel['appkey']);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $bizContent) {
			$retData = $client->execute('fbpay.order.create', $bizContent);
			return $retData;
		});
	}

	//支付宝H5下单
	static private function alipayH5(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');

		$bizContent = [
			'merchant_order_sn' => TRADE_NO,
			'total_amount' => $order['realmoney'],
			'store_id' => $channel['appmchid'],
			'body' => $ordername,
			'user_ip' => $clientip,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
		];

		$client = new FubeiClient($channel['appid'], $channel['appkey']);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $bizContent) {
			$retData = $client->execute('fbpay.order.wap.create', $bizContent);
			return $retData;
		});
	}

	//获取微信网页授权url
	static private function getWechatAuthUrl(){
		global $siteurl, $channel;

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');

		$url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
		$bizContent = [
			'url' => $url,
			'store_id' => $channel['appmchid'],
		];
		$client = new FubeiClient($channel['appid'], $channel['appkey']);
		$retData = $client->execute('openapi.agent.merchant.wechat.payment.auth', $bizContent);
		return $retData['authUrl'];
	}

	//支付宝扫码支付
	static public function alipay(){
		global $siteurl;
		$code_url = $siteurl.'pay/alipayjs/'.TRADE_NO.'/';

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//支付宝JS支付
	static public function alipayjs(){
		if(!isset($_GET['userid'])){
			$redirect_uri = '/pay/alipayjs/'.TRADE_NO.'/';
			return ['type'=>'jump','url'=>'/user/oauth.php?state='.urlencode(authcode($redirect_uri, 'ENCODE', SYS_KEY))];
		}

		$blocks = checkBlockUser($_GET['userid'], TRADE_NO);
		if($blocks) return $blocks;
		
		try{
			$retData = self::addOrder('alipay', $_GET['userid']);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'alipay_jspay','data'=>['alipay_trade_no'=>$retData['prepay_id'], 'redirect_url'=>$redirect_url]];
	}

	//支付宝H5支付
	static public function alipaywap(){
		try{
			$retData = self::alipayH5();
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		$html = $retData['html'];
		return ['type'=>'html','data'=>$html];
	}

	//微信扫码支付
	static public function wxpay(){
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
		global $siteurl,$channel;

		if($channel['appwxmp'] > 0){
			$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
			$appid = $wxinfo['appid'];
	
			try{
				$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
				$openid = $tools->GetOpenid();
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
		}else{
			if(!isset($_GET['open_id'])){
				try{
					$auth_url = self::getWechatAuthUrl();
					return ['type'=>'jump','url'=>$auth_url];
				}catch(Exception $e){
					return ['type'=>'error','msg'=>'获取微信网页授权url失败！'.$e->getMessage()];
				}
			}
			$openid = $_GET['open_id'];
			$appid = 'wxab36abed3127b34a';
		}
		
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;
		
		try{
			$retData = self::addOrder('wxpay', $openid, $appid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}

		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>json_encode($retData['sign_package']), 'redirect_url'=>$redirect_url]];
	}

	//微信参数配置
	static public function wxconfig(){
		global $siteurl,$channel;

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');
		$client = new FubeiClient($channel['appid'], $channel['appkey']);

		$bizContent=[
			'store_id' => $channel['appmchid'],
			'jsapi_path' => $siteurl
		];
		try{
			$retData = $client->execute('fbpay.order.wxconfig', $bizContent);
			return ['type'=>'error','msg'=>$retData['jsapi_msg']];
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'微信参数配置失败！'.$e->getMessage()];
		}
	}

	//微信参数配置查询
	static public function wxconfigquery(){
		global $siteurl,$channel;

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');
		$client = new FubeiClient($channel['appid'], $channel['appkey']);

		$bizContent=[
			'store_id' => $channel['appmchid']
		];
		try{
			$retData = $client->execute('fbpay.order.wxconfig.query', $bizContent);
			return ['type'=>'error','msg'=>'appid_list:'.$retData['appid_list'].',jsapi_path_list:'.$retData['jsapi_path_list']];
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'微信参数配置失败！'.$e->getMessage()];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::addOrder('unionpay', '');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');
		$client = new FubeiClient($channel['appid'], $channel['appkey']);
		$verify_result = $client->verify($_POST);

		if($verify_result){
			$data = json_decode($_POST["data"], true);
			if($data['order_status'] == 'SUCCESS'){
				$out_trade_no = daddslashes($data['merchant_order_sn']);
				$api_trade_no = daddslashes($data['order_sn']);
				$money = $data['total_amount'];

				if ($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)) {
					processNotify($order, $api_trade_no);
				}
				return ['type'=>'html','data'=>'success'];
			}else{
				return ['type'=>'html','data'=>'status='.$data['order_status']];
			}
		}else{
			return ['type'=>'html','data'=>'fail'];
		}
	}

	//支付成功页面
	static public function ok(){
		return ['type'=>'page','page'=>'ok'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require_once(PAY_ROOT.'inc/FubeiClient.class.php');
		$client = new FubeiClient($channel['appid'], $channel['appkey']);

		$bizContent=[
			'order_sn' => $order['api_trade_no'],
			'merchant_refund_sn' => $order['refund_no'],
			'refund_amount' => $order['refundmoney'],
		];
		try{
			$retData = $client->execute('fbpay.order.refund', $bizContent);
			$result = ['code'=>0, 'trade_no'=>$retData['merchant_order_sn'], 'refund_fee'=>$retData['refund_amount']];
		}catch(Exception $e){
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

}