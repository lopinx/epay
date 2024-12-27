<?php

class xorpay_plugin
{
	static public $info = [
		'name'        => 'xorpay', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'XorPay', //支付插件显示名称
		'author'      => 'XorPay', //支付插件作者
		'link'        => 'https://xorpay.com/', //支付插件作者链接
		'types'       => ['alipay','wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'AppId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'AppSecret',
				'type' => 'input',
				'note' => '',
			],
		],
		'select' => null,
		'note' => '点金计划商家小票链接（用于公众号支付跳转回网站）：[siteurl]gold.php', //支付密钥填写说明
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
			}elseif(checkmobile()){
				return ['type'=>'jump','url'=>'/pay/wxwappay/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/wxpay/'.TRADE_NO.'/'];
			}
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
		}
	}

	//扫码支付
	static private function qrcode($pay_type){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		$apiurl = 'https://xorpay.com/api/pay/'.$channel['appid'];
		$param = [
			'name' => $ordername,
			'pay_type' => $pay_type,
			'price' => $order['realmoney'],
			'order_id' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
		];
		$param['sign'] = md5($param['name'].$param['pay_type'].$param['price'].$param['order_id'].$param['notify_url'].$channel['appkey']);

		return \lib\Payment::lockPayData(TRADE_NO, function() use($apiurl, $param) {
			$data = get_curl($apiurl, http_build_query($param));
			$result = json_decode($data, true);

			if(isset($result['status']) && $result['status']=='ok'){
				$code_url = $result['info']['qr'];
			}else{
				throw new Exception($result['status']?$result['status']:'返回数据解析失败');
			}
			return $code_url;
		});
	}

	//支付宝扫码支付
	static public function alipay(){
		try{
			$code_url = self::qrcode('alipay');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'支付宝下单失败！'.$ex->getMessage()];
		}

		return ['type'=>'qrcode','page'=>'alipay_qrcode','url'=>$code_url];
	}

	//微信扫码支付
	static public function wxpay(){
		global $siteurl, $device, $mdevice;
		try{
			$code_url = self::qrcode('native');
		}catch(Exception $ex){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$ex->getMessage()];
		}

		if($mdevice == 'wechat' || checkwechat()){
			return ['type'=>'jump','url'=>$code_url];
		} elseif (checkmobile()) {
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		} else {
			return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
		}
	}

	//微信收银台支付
	static public function wxjspay(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf;

		if($_GET['d']=='1'){
			$redirect_url=$siteurl.'pay/return/'.TRADE_NO.'/';
		}else{
			$redirect_url=$siteurl.'pay/ok/'.TRADE_NO.'/';
		}

		$apiurl = 'https://xorpay.com/api/cashier/'.$channel['appid'];
		$param = [
			'name' => $ordername,
			'pay_type' => 'jsapi',
			'price' => $order['realmoney'],
			'order_id' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'return_url' => $redirect_url,
		];
		$param['sign'] = md5($param['name'].$param['pay_type'].$param['price'].$param['order_id'].$param['notify_url'].$channel['appkey']);

		$html_text = '<form action="'.$apiurl.'" method="post" id="dopay">';
		foreach($param as $k => $v) {
			$html_text .= "<input type=\"hidden\" name=\"{$k}\" value=\"{$v}\" />\n";
		}
		$html_text .= '<input type="submit" value="正在跳转"></form><script>document.getElementById("dopay").submit();</script>';

		return ['type'=>'html','data'=>$html_text];
	}

	//微信手机支付
	static public function wxwappay(){
		global $siteurl;
		$code_url = $siteurl.'pay/wxjspay/'.TRADE_NO.'/';
		return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
	}


	//异步回调
	static public function notify(){
		global $channel, $order;

		$sign = md5($_POST['aoid'].$_POST['order_id'].$_POST['pay_price'].$_POST['pay_time'].$channel['appkey']);

		if(isset($_POST['aoid']) && $sign===$_POST["sign"]){
			$out_trade_no = daddslashes($_POST['order_id']);
			$api_trade_no = daddslashes($_POST['aoid']);
			$money = $_POST['pay_price'];
			$data = json_decode($_POST['detail'], true);
			$buyer = $data['buyer'];

			if ($out_trade_no == TRADE_NO && round($money,2)==round($order['realmoney'],2)) {
				processNotify($order, $api_trade_no, $buyer);
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

		$apiurl = 'https://xorpay.com/api/refund/'.$order['api_trade_no'];
		$param = [
			'price' => $order['refundmoney'],
		];
		$param['sign'] = md5($param['price'].$channel['appkey']);

		$data = get_curl($apiurl, http_build_query($param));
		$result = json_decode($data, true);

		if(isset($result['status']) && $result['status']=='ok'){
			return ['code'=>0];
		}else{
			return ['code'=>-1, 'msg'=>$result['info']?$result['info']:'接口返回错误'];
		}
	}

}