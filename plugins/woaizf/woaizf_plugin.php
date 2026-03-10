<?php

class woaizf_plugin
{
	static public $info = [
		'name'        => 'woaizf', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '我爱支付', //支付插件显示名称
		'author'      => '我爱支付', //支付插件作者
		'link'        => 'https://www.52zhifu.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay','qqpay','bank'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'transtypes'  => ['alipay','wxpay'], //支付插件支持的转账方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '应用AppID',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '应用MD5密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '自定义接口地址',
				'type' => 'input',
				'note' => '可不填,默认是https://payapi.52zhifu.com/',
			],
		],
		'select_wxpay' => [
			'4' => '微信扫码',
			'5' => '微信H5',
			'6' => '微信公众号',
			'7' => '微信小程序',
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static private function make_sign($param, $key){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!=''){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		$sign = md5($signstr.$key);
		return $sign;
	}

	static public function submit(){
		global $siteurl, $channel, $order, $sitename;

		if($order['typename']=='alipay'){
			return ['type'=>'jump','url'=>'/pay/alipay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='wxpay'){
			if(checkwechat() && in_array('6',$channel['apptype'])){
				return ['type'=>'jump','url'=>'/pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
		}elseif($order['typename']=='qqpay'){
			return ['type'=>'jump','url'=>'/pay/qqpay/'.TRADE_NO.'/'];
		}elseif($order['typename']=='bank'){
			return ['type'=>'jump','url'=>'/pay/bank/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $device, $mdevice;

		if($order['typename']=='alipay'){
			return self::alipay();
		}elseif($order['typename']=='wxpay'){
			if($mdevice=='wechat' && in_array('6',$channel['apptype'])){
				return ['type'=>'jump','url'=>$siteurl.'pay/wxjspay/'.TRADE_NO.'/?d=1'];
			}else{
				return self::wxpay();
			}
		}elseif($order['typename']=='qqpay'){
			return self::qqpay();
		}elseif($order['typename']=='bank'){
			return self::bank();
		}
	}

	//扫码支付
	static private function addOrder($pay_type, $openid = null){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $clientip;

		if(!empty($channel['appurl'])) { 
			$apiurl = $channel['appurl'].'api/payment/create';
		}else{
			$apiurl = 'https://payapi.52zhifu.com/api/payment/create';
		}
		$param = [
			'appid' => $channel['appid'],
			'channel' => $pay_type,
			'money' => $order['realmoney'],
			'client_ip' => $clientip,
			'name' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'timestamp' => ''.time()
		];
		if($openid){
			$param['openid'] = $openid;
		}
		$param['sign'] = self::make_sign($param, $channel['appkey']);

		$data = get_curl($apiurl, http_build_query($param));

		$result = json_decode($data, true);

		if(isset($result['code']) && $result['code']==200){
			return [$result['data']['type'], $result['data']['param']];
		}else{
			throw new Exception($result['message']?$result['message']:'返回数据解析失败');
		}
	}

	//支付宝扫码支付
	static public function alipay(){
		global $channel, $device;
		if(in_array('3',$channel['apptype']) && ($device=='mobile' || checkmobile())){
			$pay_type = 'ALIPAY_WAP';
		}elseif(in_array('2',$channel['apptype']) && ($device=='pc' || !checkmobile())){
			$pay_type = 'ALIPAY_PC';
		}else{
			$pay_type = 'ALIPAY_QR';
		}
		try{
			list($type, $payData) = self::addOrder($pay_type);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}

		if($type == 'redirect_url'){
			return ['type'=>'jump','url'=>$payData];
		}elseif($type == 'form'){
			return ['type'=>'form','url'=>$payData];
		}else{
			return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$payData];
		}
	}

	//微信扫码支付
	static public function wxpay(){
		global $channel, $device, $mdevice;
		if(in_array('5',$channel['apptype']) && ($device=='mobile' || checkmobile())){
			$pay_type = 'WECHAT_H5';
		}else{
			$pay_type = 'WECHAT_QR';
		}
		try{
			list($type, $payData) = self::addOrder($pay_type);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($type == 'redirect_url'){
			return ['type'=>'jump','url'=>$payData];
		}elseif($type == 'form'){
			return ['type'=>'form','url'=>$payData];
		}else{
			if (checkmobile()) {
				return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$payData];
			} else {
				return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$payData];
			}
		}
	}

	//微信公众号支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		//①、获取用户openid
		if(!isset($_GET['openid'])){
			$redirect_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
			if(!empty($channel['appurl'])) { 
				$apiurl = $channel['appurl'].'api/openid';
			}else{
				$apiurl = 'https://payapi.52zhifu.com/api/openid';
			}
			$url = $apiurl.'?appid='.$channel['appid'].'&redirect_uri='.urlencode($redirect_url);
			return ['type'=>'jump','url'=>$url];
		}
		$openId = $_GET['openid'];
		$blocks = checkBlockUser($openId, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		try{
			list($type, $payData) = self::addOrder('WECHAT_JSAPI', $openId);
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}
		
		$redirect_url='data.backurl';
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$payData, 'redirect_url'=>$redirect_url]];
	}

	//QQ扫码支付
	static public function qqpay(){
		try{
			list($type, $payData) = self::addOrder('QQPAY_QR');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'QQ钱包支付下单失败！'.$ex->getMessage()];
		}

		if(checkmobbileqq()){
			return ['type'=>'jump','url'=>$payData];
		} elseif(checkmobile() && !isset($_GET['qrcode'])){
			return ['type'=>'qrcode','page'=>'qqpay_wap','url'=>$payData];
		} else {
			return ['type'=>'qrcode','page'=>'qqpay_qrcode','url'=>$payData];
		}
	}

	//云闪付扫码支付
	static public function bank(){
		try{
			list($type, $payData) = self::addOrder('BANK_QR');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'云闪付下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'bank_qrcode','url'=>$payData];
	}

	//异步回调
	static public function notify(){
		global $channel, $order;

		if(!isset($_POST)) return ['type'=>'html','data'=>'no data'];

		$sign = self::make_sign($_POST, $channel['appkey']);

		if($sign===$_POST["sign"]){
			$out_trade_no = $_POST['out_trade_no'];
			$api_trade_no = $_POST['trade_no'];
			$money = $_POST['money'];

			if ($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)) {
				processNotify($order, $api_trade_no);
			}
			return ['type'=>'html','data'=>'success'];
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
		global $channel, $conf;
		if(empty($order))exit();
		
		if(!empty($channel['appurl'])) { 
			$apiurl = $channel['appurl'].'api/refund/confrim';
		}else{
			$apiurl = 'https://payapi.52zhifu.com/api/refund/confrim';
		}
		$param = [
			'appid' => $channel['appid'],
			'trade_no' => $order['api_trade_no'],
			'money' => $order['refundmoney'],
			'timestamp' => ''.time()
		];
		$param['sign'] = self::make_sign($param, $channel['appkey']);
		$data = get_curl($apiurl, http_build_query($param));
		$result = json_decode($data, true);

		if(isset($result['code']) && $result['code']==200){
			return ['code'=>0];
		}else{
			return ['code'=>-1, 'msg'=>$result['message']?$result['message']:'接口返回错误'];
		}
	}

	//转账
	static public function transfer($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		if($bizParam['type'] == 'wxpay') $bizParam['type'] = 'wechat';
		
		if(!empty($channel['appurl'])) { 
			$apiurl = $channel['appurl'].'api/trans/confrim';
		}else{
			$apiurl = 'https://payapi.52zhifu.com/api/trans/confrim';
		}
		$param = [
			'appid' => $channel['appid'],
			'type' => $bizParam['type'],
			'account' => $bizParam['payee_account'],
			'name' => $bizParam['payee_real_name'],
			'memo' => $bizParam['transfer_desc'],
			'money' => $bizParam['money'],
			'timestamp' => ''.time()
		];
		$param['sign'] = self::make_sign($param, $channel['appkey']);
		$data = get_curl($apiurl, http_build_query($param));
		$result = json_decode($data, true);

		if(isset($result['code']) && $result['code']==200){
			return ['code'=>0, 'status'=>$result['data']['status'], 'orderid'=>$result['data']['trade_no'], 'paydate'=>date('Y-m-d H:i:s')];
		}else{
			return ['code'=>-1, 'msg'=>$result['message']?$result['message']:'接口返回错误'];
		}
	}

	//转账查询
	static public function transfer_query($channel, $bizParam){
		if(empty($channel) || empty($bizParam))exit();

		if(!empty($channel['appurl'])) { 
			$apiurl = $channel['appurl'].'api/trans/query';
		}else{
			$apiurl = 'https://payapi.52zhifu.com/api/trans/query';
		}
		$param = [
			'appid' => $channel['appid'],
			'trade_no' => $bizParam['orderid'],
			'timestamp' => ''.time()
		];
		$param['sign'] = self::make_sign($param, $channel['appkey']);
		$data = get_curl($apiurl, http_build_query($param));
		$result = json_decode($data, true);

		if(isset($result['code']) && $result['code']==200){
			return ['code'=>0, 'status'=>$result['data']['status'], 'amount'=>$result['data']['money'], 'paydate'=>$result['data']['trade_time'], 'errmsg'=>$result['data']['status_text']];
		}else{
			return ['code'=>-1, 'msg'=>$result['message']?$result['message']:'接口返回错误'];
		}
	}

}