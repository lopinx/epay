<?php
class hlpay_plugin
{
	static public $info = [
		'name'        => 'hlpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '汇联支付', //支付插件显示名称
		'author'      => '汇联', //支付插件作者
		'link'        => '', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['alipay','wxpay'], //支付插件支持的转账方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appmchid' => [
				'name' => '商户编号',
				'type' => 'input',
				'note' => '',
			],
			'appid' => [
				'name' => '应用APPID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '应用私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appsecret' => [
				'name' => '平台公钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appswitch' => [
				'name' => '支付方式类型',
				'type' => 'select',
				'options' => [0=>'线下支付方式',1=>'线上支付方式'],
			],
		],
		'select' => null,
		'note' => null, //支付密钥填写说明
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
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat' && $channel['appwxmp']>0){
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

	//统一下单
	static private function addOrder($way_code, $sub_appid=null, $sub_openid=null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require_once PAY_ROOT."inc/HlpayClient.php";

		$client = new HlpayClient($channel['appmchid'], $channel['appid'], $channel['appkey'], $channel['appsecret']);

		$param = [
			'way_code' => $way_code,
			'mch_order_no' => TRADE_NO,
			'amount' => $order['realmoney'],
			'client_ip'  => $clientip,
			'subject'  => $ordername,
			'notify_url'  => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
		];
		if($sub_appid && $sub_openid){
			$param += [
				'channel_extra' => json_encode(['sub_appid' => $sub_appid, 'user_id' => $sub_openid]),
			];
		}
		
		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $param) {
			$result = $client->execute('/payment.order/create', $param);
			\lib\Payment::updateOrder(TRADE_NO, $result['pay_order_id']);
			return $result;
		});
	}

	//支付宝支付
	static public function alipay(){
		global $channel;
		try{
			$result = self::addOrder($channel['appswitch'] == 1 ? 'onAlipayQr' : 'offAlipayQr');
			$code_url = $result['pay_info'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel;
		try{
			$result = self::addOrder($channel['appswitch'] == 1 ? 'onWechatQr' : 'offWechatQr');
			$code_url = $result['pay_info'];
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if(checkwechat()){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $conf;

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
			$result = self::addOrder($channel['appswitch'] == 1 ? 'onWechatPub' : 'offWechatPub', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$result['pay_info'], 'redirect_url'=>$redirect_url]];
	}

	//微信小程序支付
	static public function wxminipay(){
		global $siteurl, $channel, $order, $ordername, $conf;

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
			$result = self::addOrder($channel['appswitch'] == 1 ? 'onWechatLite' : 'offWechatLite', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			exit('{"code":-1,"msg":"'.$ex->getMessage().'"}');
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($result['pay_info'], true)]));
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
		global $channel;
		try{
			$code_url = self::addOrder($channel['appswitch'] == 1 ? 'onUnionQr' : 'offUnionQr');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		require_once PAY_ROOT."inc/HlpayClient.php";

		$client = new HlpayClient($channel['appmchid'], $channel['appid'], $channel['appkey'], $channel['appsecret']);
		$verify_result = $client->verifySign($_POST);

		if($verify_result){
			if ($_POST['state'] == '3') {
				$out_trade_no = $_POST['mch_order_no'];
				$trade_no = $_POST['pay_order_id'];
				if($out_trade_no == TRADE_NO){
					processNotify($order, $trade_no);
				}
				return ['type'=>'html','data'=>'success'];
			}
			return ['type'=>'html','data'=>'status fail'];
		}
		else {
			return ['type'=>'html','data'=>'sign fail'];
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

		require_once PAY_ROOT."inc/HlpayClient.php";

		$client = new HlpayClient($channel['appmchid'], $channel['appid'], $channel['appkey'], $channel['appsecret']);
		
		$param = [
			'pay_order_id' => $order['api_trade_no'],
			'mch_refund_no' => $order['refund_no'],
			'refund_amount' => $order['refundmoney'],
			'refund_reason' => '订单退款',
		];

		try{
			$result = $client->execute('/payment.order/refund', $param);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}

		return ['code'=>0, 'trade_no'=>$result['refund_order_id'], 'refund_fee'=>$result['refund_amount']];
	}

	//转账
	static public function transfer($channel, $bizParam){
		global $clientip, $conf;
		if(empty($channel) || empty($bizParam))exit();
		if($bizParam['type'] == 'alipay') $entry_type = 1;
		else if($bizParam['type'] == 'wxpay') $entry_type = 2;
		else if($bizParam['type'] == 'bank') $entry_type = 3;

		require_once PAY_ROOT."inc/HlpayClient.php";

		$client = new HlpayClient($channel['appmchid'], $channel['appid'], $channel['appkey'], $channel['appsecret']);

		$param = [
			'in_code' => $bizParam['type'],
			'way_code' => 'transfer',
			'entry_type' => $entry_type,
			'mch_order_no' => $bizParam['out_biz_no'],
			'amount' => $bizParam['money'],
			'client_ip' => $clientip,
			'subject' => '转账',
			'payee_name' => $bizParam['payee_real_name'],
			'payee_account' => $bizParam['payee_account'],
		];

		try{
			$result = $client->execute('/payment.transfer/create', $param);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}

		if($result['state'] == 3){
			$status = 1;
		}elseif($result['state'] == 0){
			$status = 2;
		}else{
			$status = 0;
		}
		return ['code'=>0, 'status'=>$status, 'orderid'=>$result['pay_order_id'], 'paydate'=>date('Y-m-d H:i:s')];
	}

	//转账查询
	static public function transfer_query($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		require_once PAY_ROOT."inc/HlpayClient.php";

		$client = new HlpayClient($channel['appmchid'], $channel['appid'], $channel['appkey'], $channel['appsecret']);

		$param = [
			'mch_order_no' => $bizParam['out_biz_no'],
		];

		try{
			$result = $client->execute('/payment.transfer/query', $param);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		if($result['state'] == 3){
			$status = 1;
		}elseif($result['state'] == 0){
			$status = 2;
			$errmsg = '转账失败';
		}else{
			$status = 0;
		}

		return ['code'=>0, 'status'=>$status, 'amount'=>$result['amount'], 'paydate'=>$result['success_time'], 'errmsg'=>$errmsg];
	}
}