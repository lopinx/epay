<?php

class adapay_plugin
{
	static public $info = [
		'name'        => 'adapay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'AdaPay聚合支付', //支付插件显示名称
		'author'      => 'AdaPay', //支付插件作者
		'link'        => 'https://www.adapay.tech/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用App_ID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'prod模式API_KEY',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '商户RSA私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appswitch' => [
				'name' => '微信是否使用托管小程序支付',
				'type' => 'select',
				'options' => [0=>'否',1=>'是'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
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
		global $siteurl, $channel, $order, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat'){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//通用创建订单
	static private function addOrder($pay_channel, $openid = null){
		global $channel, $order, $ordername, $conf, $clientip;

		require PAY_ROOT . 'inc/Build.class.php';
		$pay_config = include PAY_ROOT . 'inc/config.php';
		$params = [
			'order_no' => TRADE_NO,
			'pay_channel' => $pay_channel,
			'pay_amt' => $order['realmoney'],
			'goods_title' => $ordername,
			'goods_desc' => $ordername,
			'currency' => 'cny',
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		];
		if ($pay_channel === 'wx_pub' || $pay_channel === 'wx_lite') {
			$params['expend'] = [
				'openid' => $openid,
			];
		}elseif ($pay_channel === 'alipay_pub' || $pay_channel === 'alipay_lite') {
			$params['expend'] = [
				'buyer_id' => $openid,
			];
		}
		if($order['profits2'] > 0){
			$params['pay_mode'] = 'delay';
		}
		/*if($order['profits2'] > 0){
			global $DB;
			$psreceiver = $DB->find('psreceiver2', '*', ['id'=>$order['profits2']]);
			if($psreceiver){
				$psmoney = round(floor($order['realmoney'] * $psreceiver['rate']) / 100, 2);
				$psmoney2 = round($order['realmoney']-$psmoney, 2);
				$div_members = [];
				$div_members[] = ['member_id'=>$psreceiver['id'], 'amount' => sprintf('%.2f' , $psmoney), 'fee_flag'=>'N'];
				$div_members[] = ['member_id'=>0, 'amount' => sprintf('%.2f' , $psmoney2), 'fee_flag'=>'Y'];
				$params['div_members'] = $div_members;
			}
		}*/
		return \lib\Payment::lockPayData(TRADE_NO, function() use($pay_config, $params) {
			$result = AdaPay::config($pay_config)->createPayment($params);
			return $result['expend'];
		});
	}

	//跳转支付创建订单
	static private function pagepay($pay_channel){
		global $channel, $order, $ordername, $conf, $clientip, $siteurl;

		require PAY_ROOT . 'inc/Build.class.php';
		$pay_config = include PAY_ROOT . 'inc/config.php';
		$params = [
			'adapay_func_code' => 'wxpay.createOrder',
			'order_no' => TRADE_NO,
			'pay_channel' => $pay_channel,
			'pay_amt' => $order['realmoney'],
			'goods_title' => $ordername,
			'goods_desc' => $ordername,
			'currency' => 'cny',
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'callback_url' => $siteurl.'pay/return/'.TRADE_NO.'/',
		];

		return \lib\Payment::lockPayData(TRADE_NO, function() use($pay_config, $params) {
			$result = AdaPay::config($pay_config)->queryAdapay($params);
			return $result['expend'];
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$result = self::addOrder('alipay_qr');
		}catch (Exception $e) {
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$e->getMessage()];
		}
		
		$code_url = $result['qrcode_url'];

		if(checkalipay()){
			return ['type'=>'jump','url'=>$code_url];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		global $siteurl;

		$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';

		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order;

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
			$result = self::addOrder('wx_pub', $openid);
		}catch (Exception $e) {
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$e->getMessage()];
		}

		$jsApiParameters = $result['pay_info'];

		if($_GET['d']==1){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$jsApiParameters, 'redirect_url'=>$redirect_url]];
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl,$channel, $order, $ordername, $conf, $clientip;

		if($channel['appwxa']>0){
			$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
			try{
				$code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		}else{

			//托管小程序支付
			if($channel['appswitch'] == 1){
				try{
					$result = self::pagepay('wx_lite');
				}catch (Exception $e) {
					return ['type'=>'error','msg'=>'微信支付下单失败！'.$e->getMessage()];
				}
				$code_url = $result['scheme_code'];
				return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
			}

			$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}
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
			$result = self::addOrder('wx_lite', $openid);
		}catch (Exception $e) {
			exit('{"code":-1,"msg":"微信支付下单失败！'.$e->getMessage().'"}');
		}

		$jsApiParameters = $result['pay_info'];
		exit(json_encode(['code'=>0, 'data'=>json_decode($jsApiParameters, true)]));
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$result = self::addOrder('union_qr');
		}catch (Exception $e) {
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$e->getMessage()];
		}

		$code_url = $result['qrcode_url'];

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		//file_put_contents('logs.txt',http_build_query($_POST));

		require_once PAY_ROOT . 'inc/Build.class.php';
		$app = AdaPay::config(include PAY_ROOT . 'inc/config.php');
		if ($app->ada_tools->verifySign($_POST['sign'] , $_POST['data'])) {
			$_data = json_decode($_POST['data'] , true);
			if ($_data['status'] == 'succeeded') {
				$api_trade_no = daddslashes($_data['id']);
				$trade_no = daddslashes($_data['order_no']);
				$orderAmount = sprintf('%.2f' , $_data['pay_amt']);
				if (sprintf('%.2f' ,$order['realmoney']) == $orderAmount && $trade_no == TRADE_NO) {

					if($order['profits2'] > 0){
						usleep(300000);
						global $DB;
						$psreceiver = $DB->find('psreceiver2', '*', ['id'=>$order['profits2']]);
						if($psreceiver){
							$psmoney = round(floor($order['realmoney'] * $psreceiver['rate']) / 100, 2);
							$psmoney2 = round($order['realmoney']-$psmoney, 2);
							$div_members = [];
							$div_members[] = ['member_id'=>$psreceiver['id'], 'amount' => sprintf('%.2f' , $psmoney), 'fee_flag'=>'N'];
							$div_members[] = ['member_id'=>0, 'amount' => sprintf('%.2f' , $psmoney2), 'fee_flag'=>'Y'];
							$params = [
								'payment_id' => $api_trade_no,
								'order_no' => date("YmdHis").rand(11111,99999),
								'confirm_amt' => $order['realmoney'],
								'div_members' => $div_members,
							];
							$app->createPaymentConfirm($params);
						}
					}
					processNotify($order, $api_trade_no);
				}
				return ['type'=>'html','data'=>'Ok'];
			} else {
				return ['type'=>'html','data'=>'No'];
			}
		} else {
			return ['type'=>'html','data'=>'No'];
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

		require PAY_ROOT . 'inc/Build.class.php';
		$params = [
			'payment_id' => $order['api_trade_no'],
			'refund_order_no' => $order['refund_no'],
			'refund_amt' => $order['realmoney']
		];
		try{
			$res = AdaPay::config(include PAY_ROOT . 'inc/config.php')->createRefund($params);
		}catch(Exception $e){
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}

		if($res['status']=='succeeded'||$res['status']=='pending'){
			$result = ['code'=>0, 'trade_no'=>$res['id'], 'refund_fee'=>$res['refund_amt']];
		}else{
			$result = ['code'=>-1, 'msg'=>'['.$res["error_code"].']'.$res["error_msg"]];
		}
		return $result;
	}
}