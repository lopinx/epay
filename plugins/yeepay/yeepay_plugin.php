<?php

class yeepay_plugin
{
	static public $info = [
		'name'        => 'yeepay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '易宝支付', //支付插件显示名称
		'author'      => '易宝支付', //支付插件作者
		'link'        => 'https://www.yeepay.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appkey' => [
				'name' => '应用标识',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '商户私钥',
				'type' => 'textarea',
				'note' => '',
			],
			'appid' => [
				'name' => '发起方商户编号',
				'type' => 'input',
				'note' => '标准商户则填写标准商户商编；平台商入驻商户，则填写平台商商编',
			],
			'appmchid' => [
				'name' => '收款商户编号',
				'type' => 'input',
				'note' => '留空则与发起方商户编号一致',
			],
		],
		'select' => null,
		'note' => '密钥需要选RSA格式的', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			if(checkwechat() && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/'];
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
			if($mdevice=='wechat' && $channel['appwxmp']>0){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/'];
			}elseif($device=='mobile'){
				return self::wxwappay();
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//聚合支付托管下单
	static private function tutelage_pay($payWay, $payType){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/YopClient.php');

		if($payType == 'ALIPAY'){
			$scene = 'OFFLINE';
		}else{
			$scene = 'ONLINE';
		}
		$params = [
			'parentMerchantNo' => $channel['appid'],
			'merchantNo' => empty($channel['appmchid'])?$channel['appid']:$channel['appmchid'],
			'orderId' => TRADE_NO,
			'orderAmount' => $order['realmoney'],
			'goodsName' => $ordername,
			'notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'payWay' => $payWay,
			'channel' => $payType,
			'scene' => $scene,
			'userIp' => $clientip,
			'redirectUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
		];
		if($order['profits'] > 0){
			global $DB;
			$psreceiver = $DB->find('psreceiver', '*', ['id'=>$order['profits']]);
			if($psreceiver){
				$psmoney = round(floor($order['realmoney'] * $psreceiver['rate']) / 100, 2);
				$divideDetail = [
					[
						'ledgerNo' => $psreceiver['account'],
						'amount' => $psmoney,
						'ledgerType' => 'MERCHANT2MERCHANT',
					]
				];
				$params['fundProcessType'] = 'REAL_TIME_DIVIDE';
				$params['divideDetail'] = json_encode($divideDetail);
				$params['divideNotifyUrl'] = $conf['localurl'] . 'pay/dividenotify/' . TRADE_NO . '/';
			}
		}

		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $params) {
			$result = $client->post('/rest/v1.0/aggpay/tutelage/pre-pay', $params);
        	if($result['code'] == '00000'){
				return $result['prePayTn'];
			}else{
				throw new Exception('['.$result['code'].']'.$result['message']);
			}
		});
	}

	//聚合支付统一下单
	static private function pre_pay($payWay, $payType, $appId = null, $userId = null){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		require(PAY_ROOT.'inc/YopClient.php');

		$params = [
			'parentMerchantNo' => $channel['appid'],
			'merchantNo' => empty($channel['appmchid'])?$channel['appid']:$channel['appmchid'],
			'orderId' => TRADE_NO,
			'orderAmount' => $order['realmoney'],
			'goodsName' => $ordername,
			'notifyUrl' => $conf['localurl'] . 'pay/notify/' . TRADE_NO . '/',
			'redirectUrl' => $siteurl.'pay/return/'.TRADE_NO.'/',
			'payWay' => $payWay,
			'channel' => $payType,
			'scene' => 'ONLINE',
			'userIp' => $clientip,
		];
		if($appId && $userId){
			$params += [
				'appId' => $appId,
				'userId' => $userId
			];
		}
		if($order['profits'] > 0){
			global $DB;
			$psreceiver = $DB->find('psreceiver', '*', ['id'=>$order['profits']]);
			if($psreceiver){
				$psmoney = round(floor($order['realmoney'] * $psreceiver['rate']) / 100, 2);
				$divideDetail = [
					[
						'ledgerNo' => $psreceiver['account'],
						'amount' => $psmoney,
						'ledgerType' => 'MERCHANT2MERCHANT',
					]
				];
				$params['fundProcessType'] = 'REAL_TIME_DIVIDE';
				$params['divideDetail'] = json_encode($divideDetail);
				$params['divideNotifyUrl'] = $conf['localurl'] . 'pay/dividenotify/' . TRADE_NO . '/';
			}
		}

		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($client, $params) {
			$result = $client->post('/rest/v1.0/aggpay/pre-pay', $params);
        	if($result['code'] == '00000'){
				return $result['prePayTn'];
			}else{
				throw new Exception('['.$result['code'].']'.$result['message']);
			}
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::pre_pay('USER_SCAN', 'ALIPAY');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝支付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $siteurl;

		$code_url = $siteurl.'pay/wxwappay/'.TRADE_NO.'/';
		/*try{
			$code_url = self::pre_pay('USER_SCAN', 'WECHAT');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}*/

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
			$payinfo = self::pre_pay('WECHAT_OFFIACCOUNT', 'WECHAT', $wxinfo['appid'], $openid);
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
			$payinfo = self::pre_pay('MINI_PROGRAM', 'WECHAT', $wxinfo['appid'], $openid);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		exit(json_encode(['code'=>0, 'data'=>json_decode($payinfo, true)]));
	}

	//微信手机支付
	static public function wxwappay(){
		try{
			$jump_url = self::tutelage_pay('H5_PAY', 'WECHAT');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		
		if(checkwechat()){
			return ['type'=>'jump','url'=>$jump_url];
		}else{
			return ['type'=>'qrcode','page'=>'wxpay_h5','url'=>$jump_url];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			$code_url = self::pre_pay('USER_SCAN', 'UNIONPAY');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$code_url];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		if(!$_POST['response']) return ['type'=>'html','data'=>'no data'];

		require(PAY_ROOT.'inc/YopClient.php');
		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);
		try{
			$data = $client->notifyDecrypt($_POST['response']);
		}catch(Exception $e){
			return ['type'=>'html','data'=>$e->getMessage()];
		}

		if($data) {
			$out_trade_no = $data['orderId'];
			$api_trade_no = $data['uniqueOrderNo'];
			$total_amount = $data['orderAmount'];
			$payerInfo = json_decode($data['payerInfo'], true);
			$buyer = $payerInfo['userID'];

			if ($data['status'] == 'SUCCESS') {
				if($out_trade_no == TRADE_NO && round($total_amount,2)==round($order['realmoney'],2)){
					processNotify($order, $api_trade_no, $buyer);
				}
			}
			return ['type'=>'html','data'=>'SUCCESS'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	//支付返回页面
	static public function return(){
		return ['type'=>'page','page'=>'return'];
	}

	//退款
	static public function refund($order){
		global $channel, $clientip;
		if(empty($order))exit();
		
		require(PAY_ROOT.'inc/YopClient.php');

		$params = [
			'parentMerchantNo' => $channel['appid'],
			'merchantNo' => empty($channel['appmchid'])?$channel['appid']:$channel['appmchid'],
			'orderId' => $order['trade_no'],
			'refundRequestId' => $order['refund_no'] ?? $order['trade_no'],
			'refundAmount' => $order['refundmoney']
		];

		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);
		
		$result = $client->post('/rest/v1.0/trade/refund', $params);

		if($result['code'] == 'OPR00000'){
			return ['code'=>0, 'trade_no'=>$result['uniqueRefundNo'], 'refund_fee'=>$result['refundAmount']];
		}else{
			return ['code'=>-1, 'msg'=>'['.$result['code'].']'.$result['message']];
		}
	}

	//异步回调
	static public function applynotify(){
		global $channel;

		if(!$_POST['response']) return ['type'=>'html','data'=>'no data'];

		require(PAY_ROOT.'inc/YopClient.php');
		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);
		try{
			$data = $client->notifyDecrypt($_POST['response']);
		}catch(Exception $e){
			return ['type'=>'html','data'=>$e->getMessage()];
		}

		if($data) {
			$model = \lib\Applyments\CommUtil::getModel2($channel);
			if($model) $model->notify($data);
			
			return ['type'=>'html','data'=>'SUCCESS'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	//投诉通知
	static public function complainnotify(){
		global $channel;

		if(!$_POST['response']) return ['type'=>'html','data'=>'no data'];

		require(PAY_ROOT.'inc/YopClient.php');
		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);
		try{
			$data = $client->notifyDecrypt($_POST['response']);
		}catch(Exception $e){
			return ['type'=>'html','data'=>$e->getMessage()];
		}

		if($data) {
			$model = \lib\Complain\CommUtil::getModel($channel);
			if($model) $model->refreshNewInfo($data['complaintNo'], $data['actionType']);
			
			return ['type'=>'html','data'=>'SUCCESS'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'FAIL'];
		}
	}

	//分账回调
	static public function dividenotify(){
		global $channel, $DB;

		if(!$_POST['response']) return ['type'=>'html','data'=>'no data'];

		require(PAY_ROOT.'inc/YopClient.php');
		$client = new \Yeepay\YopClient($channel['appkey'], $channel['appsecret']);
		try{
			$data = $client->notifyDecrypt($_POST['response']);
		}catch(Exception $e){
			return ['type'=>'html','data'=>$e->getMessage()];
		}

		if($data) {
			$divide_trade_no = $data['divideRequestId'];
			$out_trade_no = $data['orderId'];
			$status = $data['divideStatus'];
			$psorder = $DB->find('psorder', '*', ['trade_no'=>$out_trade_no]);
			if($psorder){
				if($status == 'SUCCESS'){
					$DB->update('psorder', ['status'=>2,'settle_no'=>$divide_trade_no], ['id'=>$psorder['id']]);
				}elseif($status == 'FAIL'){
					$DB->update('psorder', ['status'=>3,'result'=>$data['failReason']], ['id'=>$psorder['id']]);
				}
			}
			
			return ['type'=>'html','data'=>'SUCCESS'];
		}
		else {
			//验证失败
			return ['type'=>'html','data'=>'FAIL'];
		}
	}
}