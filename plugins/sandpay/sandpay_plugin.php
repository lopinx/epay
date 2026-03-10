<?php

class sandpay_plugin
{
	static public $info = [
		'name'        => 'sandpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '杉德支付', //支付插件显示名称
		'author'      => '杉德', //支付插件作者
		'link'        => 'https://www.sandpay.com.cn/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['bank'], //支付插件支持的转账方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '商户编号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '私钥证书密码',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '环境选择',
				'type' => 'select',
				'options' => [0=>'生产环境',1=>'测试环境'],
			],
			'product' => [
				'name' => '市场产品',
				'type' => 'select',
				'options' => ['QZF'=>'标准线上收款','CSDB'=>'企业杉德宝'],
			],
		],
		'select_bank' => [
			'1' => '银联聚合码',
			'2' => '快捷支付',
		],
		'select' => null,
		'note' => '将杉德公钥证书sand.cer、商户私钥证书client.pfx（或商户编号.pfx）上传到 /plugins/sandpay/cert/', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			if(checkwechat() && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif(checkmobile() && $channel['appwxa']>0){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='bank'){
			if(in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>'/pay/fastpay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
			}
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat' && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile' && $channel['appwxa']>0){
				return ['type'=>'jump','url'=>$siteurl.'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			if(in_array('2',$channel['apptype'])){
				return self::fastpay();
			}else{
				return self::bank();
			}
		}
	}

	//统一下单
	static private function addOrder($pay_type, $pay_mode, $sub_openid = null, $sub_appid = null){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		require(PAY_ROOT."inc/SandpayClient.php");

		$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
		$params = [
			'marketProduct' => $channel['product'],
			'outReqTime' => date('YmdHis'),
			'mid' => $channel['appid'],
			'outOrderNo' => TRADE_NO,
			'description' => $ordername,
			'goodsClass' => '01',
			'amount' => $order['realmoney'],
			'payType' => $pay_type,
			'payMode' => $pay_mode,
			'payerInfo' => [
				'payAccLimit' => '',
			],
			'notifyUrl' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'riskmgtInfo' => [
				'sourceIp' => $clientip,
			],
		];
		if($sub_openid && $sub_appid){
			$params['payerInfo'] = [
				'subAppId' => $sub_appid,
				'subUserId' => $sub_openid,
				'frontUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			];
		}elseif($sub_openid){
			$params['payerInfo'] = [
				'userId' => $sub_openid,
				'frontUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			];
		}

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $params) {
			$result = $client->execute('/v4/sd-receipts/api/trans/trans.order.create', $params);
			\lib\Payment::updateOrder(TRADE_NO, $result['sandSerialNo']);
			return $result['credential'];
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$result = self::addOrder('ALIPAY','QR');
			$code_url = $result['qrCode'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $siteurl, $device, $mdevice;
		try{
			$result = self::addOrder('WXPAY','QR');
			$code_url = $result['qrCode'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if (checkwechat() || $mdevice=='wechat') {
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile() || $device == 'mobile') {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

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

		try{
			$payinfo = self::addOrder('WXPAY','JSAPI',$openid,$wxinfo['appid']);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

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
			$payinfo = self::addOrder('WXPAY','MINI',$openid,$wxinfo['appid']);
		}catch(Exception $ex){
			exit('{"code":-1,"msg":"微信支付下单失败！'.$ex->getMessage().'"}');
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($payinfo, true)]));
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

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
	static public function bank(){
		try{
			$result = self::addOrder('CUPPAY','QR');
			$code_url = $result['qrCode'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//快捷支付
	static public function fastpay(){
		if(!empty($_COOKIE['sandpay_user_id'])){
			$user_id = $_COOKIE['sandpay_user_id'];
		}else{
			$user_id = substr(getSid(), 0, 10);
			setcookie('sandpay_user_id', $user_id, time()+3600*24*365, '/');
		}
		try{
			$result = self::addOrder('FASTPAY','SANDH5',$user_id);
			$jump_url = $result['cashierUrl'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}
		return ['type'=>'jump','url'=>$jump_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		$sign   = $_POST['sign']; //签名
		$data   = $_POST['bizData']; //支付数据

		require(PAY_ROOT."inc/SandpayClient.php");

		$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
		$verifyFlag = $client->verify($data, $sign);

		if($verifyFlag){
			$array = json_decode($data, true);
			if($array['orderStatus'] == 'success'){
				$out_trade_no = $array['outOrderNo'];
				$trade_no = $array['sandSerialNo'];
				$money = $array['amount'];
				$buyer = $array['payer']['payerAccNo'];
				if($out_trade_no == TRADE_NO){
					processNotify($order, $trade_no, $buyer);
				}
				return ['type'=>'html','data'=>'respCode=000000'];
			}
		}
		return ['type'=>'html','data'=>'respCode=020002'];
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel, $conf;
		if(empty($order))exit();

		require(PAY_ROOT."inc/SandpayClient.php");

		$params = [
			'marketProduct' => $channel['product'],
			'outReqTime' => date('YmdHis'),
			'mid' => $channel['appid'],
			'outOrderNo' => $order['refund_no'],
			'oriOutOrderNo' => $order['trade_no'],
			'amount' => $order['refundmoney'],
			'notifyUrl' => $conf['localurl'].'pay/refundnotify/'.TRADE_NO.'/',
		];
		try{
			$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$result = $client->execute('/v4/sd-receipts/api/trans/trans.order.refund', $params);
			return ['code'=>0, 'trade_no'=>$result['sandSerialNo'], 'refund_fee'=>$result['amount']];
		}catch(Exception $ex){
			return ['code'=>-1,'msg'=>$ex->getMessage()];
		}
	}

	//退款回调
	static public function refundnotify(){
		global $channel, $order;

		$sign   = $_POST['sign']; //签名
		$data   = $_POST['bizData']; //支付数据

		require(PAY_ROOT."inc/SandpayClient.php");

		$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
		$verifyFlag = $client->verify($data, $sign);

		if($verifyFlag){
			$array = json_decode($data, true);
			if($array['orderStatus'] == 'success'){
				$out_trade_no = $array['outOrderNo'];
				$trade_no = $array['sandSerialNo'];
				$money = $array['amount'];
				return ['type'=>'html','data'=>'respCode=000000'];
			}
		}
		return ['type'=>'html','data'=>'respCode=020002'];
	}

	//转账
	static public function transfer($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require(PLUGIN_ROOT.'sandpay/inc/SandpayClient.php');
		
		$params = [
			'mid' => $channel['appid'],
			'outOrderNo' => $bizParam['out_biz_no'],
			'amount' => $bizParam['money'],
			'payeeInfo' => [
				'accType' => 'cup',
				'accNo' => $bizParam['payee_account'],
				'accName' => $bizParam['payee_real_name'],
			],
			'payerInfo' => [
				'sdaccSubId' => 'payment',
				'remark' => $bizParam['transfer_desc'],
			],
		];

		try{
			$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$result = $client->execute('/v4/sd-payment/api/trans/trans.payment.order.create', $params);
			$status = $result['paymentStatus'] == 'success' ? 1 : 0;
			return ['code'=>0, 'status'=>$status, 'orderid'=>$result['sandSerialNo'], 'paydate'=>$result['finishedTime']];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//转账查询
	static public function transfer_query($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require(PLUGIN_ROOT.'sandpay/inc/SandpayClient.php');

		$params = [
			'mid' => $channel['appid'],
			'outReqDate' => substr($bizParam['out_biz_no'], 0, 8),
			'outOrderNo' => $bizParam['out_biz_no'],
		];
		try{
			$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$result = $client->execute('/v4/sd-payment/api/trans/trans.payment.order.query', $params);
			$status = $result['orderStatus'] == 'success' ? 1 : 0;
			return ['code'=>0, 'status'=>$status];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}

	//余额查询
	static public function balance_query($channel, $bizParam){
		if(empty($channel))exit();

		require(PLUGIN_ROOT.'sandpay/inc/SandpayClient.php');

		$params = [
			'mid' => $channel['appid'],
			'sdaccSubId' => 'payment',
		];
		try{
			$client = new SandpayClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$result = $client->execute('/v4/sd-payment/api/trans/trans.payment.balance.query', $params);
			$account = $result['accountList'][0];
			if(empty($account)) return ['code'=>-1, 'msg'=>'未查询到账户信息'];
			return  ['code'=>0, 'amount'=>$account['availableBal'], 'msg'=>'当前账户可用余额：'.$account['availableBal'].' 元，冻结金额：'.$account['frozenBal'].'，在途余额：'.$account['transitBal']];
		}catch(Exception $ex){
			return ['code'=>-1, 'msg'=>$ex->getMessage()];
		}
	}
}