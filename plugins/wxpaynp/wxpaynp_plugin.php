<?php

class wxpaynp_plugin
{
	static public $info = [
		'name'        => 'wxpaynp', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => '微信官方支付V3服务商版', //支付插件显示名称
		'author'      => '微信', //支付插件作者
		'link'        => 'https://pay.weixin.qq.com/partner/public/home', //支付插件作者链接
		'types'       => ['wxpay'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => '公众号/小程序/开放平台AppID',
				'type' => 'input',
				'note' => '',
			],
			'appmchid' => [
				'name' => '服务商商户号',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => '商户API证书序列号',
				'type' => 'input',
				'note' => '',
			],
			'appsecret' => [
				'name' => '商户APIv3密钥',
				'type' => 'input',
				'note' => '',
			],
			'appurl' => [
				'name' => '子商户号',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '服务商类型',
				'type' => 'select',
				'options' => [0=>'普通服务商',1=>'平台收付通'],
			],
		],
		'select' => [ //选择已开启的支付方式
			'1' => '扫码支付',
			'2' => '公众号支付',
			'3' => 'H5支付',
			'4' => '小程序支付',
			'5' => 'APP支付',
		],
		'note' => '<p>请将商户API私钥“apiclient_key.pem”放到 /plugins/wxpaynp/cert/ 文件夹内（或 /plugins/wxpaynp/cert/商户号/ 文件夹内）。</p><p>上方AppID填写已认证的公众号/小程序/开放平台应用皆可，需要在微信支付后台关联对应的AppID账号才能使用。</p><p>点金计划商家小票链接（用于公众号支付跳转回网站）：[siteurl]gold.php</p>', //支付密钥填写说明
		'bindwxmp' => true, //是否支持绑定微信公众号
		'bindwxa' => true, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $submit2, $conf;

		$urlpre = '/';
        if (!empty($conf['localurl_wxpay']) && !strpos($conf['localurl_wxpay'], $_SERVER['HTTP_HOST'])) {
			$urlpre = $conf['localurl_wxpay'];
        }
		
		if(checkwechat()){
			if(in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/jspay/'.TRADE_NO.'/?d=1'];
			}elseif(in_array('4',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/wap/'.TRADE_NO.'/'];
			}elseif(in_array('1',$channel['apptype']) && $conf['wework_payopen'] == 1){
				return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
			}else{
				if(!$submit2){
					return ['type'=>'jump','url'=>'/pay/submit/'.TRADE_NO.'/'];
				}
				return ['type'=>'page','page'=>'wxopen'];
			}
		}elseif(checkmobile()){
			if(in_array('3',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/h5/'.TRADE_NO.'/'];
			}elseif(in_array('5',$channel['apptype']) && strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone OS')!==false){
				return ['type'=>'jump','url'=>$urlpre.'pay/apppay/'.TRADE_NO.'/'];
			}elseif(in_array('2',$channel['apptype']) || in_array('4',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/wap/'.TRADE_NO.'/'];
			}else{
				return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
			}
		}else{
			return ['type'=>'jump','url'=>'/pay/qrcode/'.TRADE_NO.'/'];
		}
	}

	static public function mapi(){
		global $siteurl, $channel, $order, $conf, $device, $mdevice, $method;

		$urlpre = $siteurl;
		if (!empty($conf['localurl_wxpay']) && !strpos($conf['localurl_wxpay'], $_SERVER['HTTP_HOST'])) {
			$urlpre = $conf['localurl_wxpay'];
		}

		if($method=='app'){
			return self::apppay();
		}elseif($method=='jsapi'){
			return self::jspay();
		}elseif($method=='scan'){
			return ['type'=>'error','msg'=>'当前支付通道不支持付款码支付'];
		}
		elseif($mdevice=='wechat'){
			if(in_array('2',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/jspay/'.TRADE_NO.'/?d=1'];
			}elseif(in_array('4',$channel['apptype'])){
				return self::wap();
			}else{
				return ['type'=>'jump','url'=>$siteurl.'pay/submit/'.TRADE_NO.'/'];
			}
		}elseif($device=='mobile'){
			if(in_array('5',$channel['apptype']) && $mdevice == 'app'){
				return self::apppay();
			}elseif(in_array('3',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/h5/'.TRADE_NO.'/'];
			}elseif(in_array('5',$channel['apptype'])){
				return ['type'=>'jump','url'=>$urlpre.'pay/submit/'.TRADE_NO.'/'];
			}elseif(in_array('2',$channel['apptype']) || in_array('4',$channel['apptype'])){
				return self::wap();
			}else{
				return self::qrcode();
			}
		}else{
			return self::qrcode();
		}
	}

	//扫码支付
	static public function qrcode(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		if(in_array('1',$channel['apptype'])){

		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'scene_info' => [
				'payer_client_ip' => $clientip
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$submoneys = combinepay_submoneys($param['amount']['total']);
			if(!$submoneys){
				$result = $client->nativePay($param);
			}else{
				$param = self::combineOrderParams($param, $submoneys);
				$result = $client->combineNativePay($param);
				\lib\Payment::updateOrderCombine(TRADE_NO);
			}
			$code_url = $result['code_url'];
		} catch (Exception $e) {
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$e->getMessage()];
		}

		}elseif(in_array('2',$channel['apptype'])){
			$code_url = $siteurl.'pay/jspay/'.TRADE_NO.'/';
		}elseif(in_array('4',$channel['apptype'])){
			$code_url = $siteurl.'pay/wap/'.TRADE_NO.'/';
		}else{
			return ['type'=>'error','msg'=>'当前支付通道没有开启的支付方式'];
		}
		return ['type'=>'qrcode','page'=>'wxpay_qrcode','url'=>$code_url];
	}

	//JS支付
	static public function jspay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip, $method;

		if(!empty($order['sub_openid'])){
			if(!empty($order['sub_appid'])){
				$channel['sub_appid'] = $order['sub_appid'];
			}else{
				$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
				if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
				if($channel['subappwxmp']){
					$channel['sub_appid'] = $wxinfo['appid'];
				}else{
					$channel['appid'] = $wxinfo['appid'];
				}
			}
			$openid = $order['sub_openid'];
		}else{
			$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
			if(!$wxinfo) return ['type'=>'error','msg'=>'支付通道绑定的微信公众号不存在'];
			if($channel['subappwxmp']){
				$channel['sub_appid'] = $wxinfo['appid'];
			}else{
				$channel['appid'] = $wxinfo['appid'];
			}

			//①、获取用户openid
			try{
				$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
				$openid = $tools->GetOpenid();
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
		}

		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks) return $blocks;

		//②、统一下单
		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'payer' => [
				'sp_openid' => $openid
			],
			'scene_info' => [
				'payer_client_ip' => $clientip
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}
		if($channel['sub_appid']){
			$param['payer']['sub_openid'] = $openid;
			unset($param['payer']['sp_openid']);
		}

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$submoneys = combinepay_submoneys($param['amount']['total']);
			if(!$submoneys){
				$result = $client->jsapiPay($param);
			}else{
				$param = self::combineOrderParams($param, $submoneys);
				$result = $client->combineJsapiPay($param);
				\lib\Payment::updateOrderCombine(TRADE_NO);
			}
			$jsApiParameters = json_encode($result);
		} catch (Exception $e) {
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$e->getMessage()];
		}
		if($method == 'jsapi'){
			return ['type'=>'jsapi','data'=>$result];
		}

		if($_GET['d']=='1'){
			$redirect_url='data.backurl';
		}else{
			$redirect_url='\'/pay/ok/'.TRADE_NO.'/\'';
		}
		return ['type'=>'page','page'=>'wxpay_jspay','data'=>['jsApiParameters'=>$jsApiParameters, 'redirect_url'=>$redirect_url]];
	}

	//手机支付
	static public function wap(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;
		
		if(in_array('4',$channel['apptype']) && !isset($_GET['qrcode'])){
			try{
				$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
				if(!$wxinfo)return ['type'=>'error','msg'=>'支付通道绑定的微信小程序不存在'];
				$code_url = wxminipay_jump_scheme($wxinfo['id'], TRADE_NO);
			}catch(Exception $e){
				return ['type'=>'error','msg'=>$e->getMessage()];
			}
			return ['type'=>'scheme','page'=>'wxpay_mini','url'=>$code_url];
		}else{
			$code_url = $siteurl.'pay/jspay/'.TRADE_NO.'/';
			return ['type'=>'qrcode','page'=>'wxpay_wap','url'=>$code_url];
		}
	}

	//H5支付
	static public function h5(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'scene_info' => [
				'payer_client_ip' => $clientip,
				'h5_info' => [
					'type' => 'Wap',
					'app_name' => $conf['sitename'],
					'app_url' => $siteurl,
				],
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$submoneys = combinepay_submoneys($param['amount']['total']);
			if(!$submoneys){
				$result = $client->h5Pay($param);
			}else{
				$param = self::combineOrderParams($param, $submoneys);
				$result = $client->combineH5Pay($param);
				\lib\Payment::updateOrderCombine(TRADE_NO);
			}
			$redirect_url=$siteurl.'pay/return/'.TRADE_NO.'/';
			$url=$result['h5_url'].'&redirect_url='.urlencode($redirect_url);
			return ['type'=>'jump','url'=>$url];
		} catch (Exception $e) {
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$e->getMessage()];
		}
	}

	//小程序支付
	static public function wxminipay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip;

		$code = isset($_GET['code'])?trim($_GET['code']):exit('{"code":-1,"msg":"code不能为空"}');

		$wxinfo = \lib\Channel::getWeixin($channel['appwxa']);
		if(!$wxinfo)exit('{"code":-1,"msg":"支付通道绑定的微信小程序不存在"}');
		if($channel['subappwxa']){
			$channel['sub_appid'] = $wxinfo['appid'];
		}else{
			$channel['appid'] = $wxinfo['appid'];
		}

		//①、获取用户openid
		try{
			$tools = new \WeChatPay\JsApiTool($wxinfo['appid'], $wxinfo['appsecret']);
			$openid = $tools->AppGetOpenid($code);
		}catch(Exception $e){
			exit('{"code":-1,"msg":"'.$e->getMessage().'"}');
		}
		$blocks = checkBlockUser($openid, TRADE_NO);
		if($blocks)exit('{"code":-1,"msg":"'.$blocks['msg'].'"}');

		//②、统一下单
		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'payer' => [
				'sp_openid' => $openid
			],
			'scene_info' => [
				'payer_client_ip' => $clientip
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}
		if($channel['subappwxa']){
			$param['payer']['sub_openid'] = $openid;
			unset($param['payer']['sp_openid']);
		}

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$submoneys = combinepay_submoneys($param['amount']['total']);
			if(!$submoneys){
				$jsApiParameters = $client->jsapiPay($param);
			}else{
				$param = self::combineOrderParams($param, $submoneys);
				$jsApiParameters = $client->combineJsapiPay($param);
				\lib\Payment::updateOrderCombine(TRADE_NO);
			}
			exit(json_encode(['code'=>0, 'data'=>$jsApiParameters]));
		} catch (Exception $e) {
			exit(json_encode(['code'=>-1, 'msg'=>'微信支付下单失败！'.$e->getMessage()]));
		}
	}

	//APP支付
	static public function apppay(){
		global $siteurl, $channel, $order, $ordername, $conf, $clientip, $method;

		$param = [
			'description' => $ordername,
			'out_trade_no' => TRADE_NO,
			'notify_url' => $conf['localurl'].'pay/notify/'.TRADE_NO.'/',
			'amount' => [
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			],
			'scene_info' => [
				'payer_client_ip' => $clientip
			]
		];
		if($order['profits']>0){
			$param['settle_info'] = ['profit_sharing' => true];
		}
		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$submoneys = combinepay_submoneys($param['amount']['total']);
			if(!$submoneys){
				$result = $client->appPay($param);
			}else{
				$param = self::combineOrderParams($param, $submoneys);
				$result = $client->combineAppPay($param);
				\lib\Payment::updateOrderCombine(TRADE_NO);
			}
			if($method == 'app'){
				return ['type'=>'app','data'=>$result];
			}
			$params = [
				'nonceStr' => $result['noncestr'],
				'package' => $result['package'],
				'partnerId' => $result['partnerid'],
				'prepayId' => $result['prepayid'],
				'timeStamp' => $result['timestamp'],
				'sign' => $result['sign'],
			];
			$code_url = 'weixin://app/'.$result['appid'].'/pay/?'.http_build_query($params);
			return ['type'=>'qrcode','page'=>'wxpay_h5','url'=>$code_url];
		}catch(Exception $e){
			return ['type'=>'error','msg'=>'微信支付下单失败！'.$e->getMessage()];
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

	//异步回调
	static public function notify(){
		global $channel, $order;

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$data = $client->notify();
		} catch (Exception $e) {
			$client->replyNotify(false, $e->getMessage());
			exit;
		}

		if(isset($data['combine_out_trade_no'])){ //合单支付
			if($data['combine_out_trade_no'] == TRADE_NO){
				processNotify($order, $data['combine_out_trade_no'], $data['combine_payer_info']['openid']);
			}
		}else{
			if ($data['trade_state'] == 'SUCCESS') {
				if($data['out_trade_no'] == TRADE_NO){
					processNotify($order, $data['transaction_id'], $data['payer']['sp_openid']);
				}
			}
		}
		$client->replyNotify(true);
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		$param = [
			'transaction_id' => $order['api_trade_no'],
			'out_refund_no' => $order['refund_no'],
			'amount' => [
				'refund' => intval(round($order['refundmoney']*100)),
				'total' => intval(round($order['realmoney']*100)),
				'currency' => 'CNY'
			]
		];

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$result = $client->refund($param);
			$result = ['code'=>0, 'trade_no'=>$result['out_trade_no'], 'refund_fee'=>$result['amount']['refund']];
		} catch (Exception $e) {
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

	//合单退款
	static public function refund_combine($order){
		global $channel;
		if(empty($order))exit();

		$refundmoney = intval(round($order['refundmoney']*100));
		$totalmoney = intval(round($order['realmoney']*100));
		$leftmoney = $refundmoney;
		if($refundmoney>$totalmoney){
			return ['code'=>-1, 'msg'=>'退款金额不能大于订单金额'];
		}

		//查询子单列表
		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\PartnerPaymentService($wechatpay_config);
			$result = $client->combineQueryOrder($order['api_trade_no']);
		} catch (Exception $e) {
			return ['code'=>-1, 'msg'=>$e->getMessage()];
		}

		//循环退款
		$i = 1;
		$success = 0;
		foreach($result['sub_orders'] as $sub_order){
			$money = $sub_order['amount']['total_amount'];
			if($refundmoney<$totalmoney){
				if($leftmoney-$money<0) $money = $leftmoney;
			}
			$param = [
				'transaction_id' => $sub_order['transaction_id'],
				'out_refund_no' => $order['refund_no'].$i,
				'amount' => [
					'refund' => $money,
					'total' => $sub_order['amount']['total_amount'],
					'currency' => $sub_order['amount']['currency'],
				]
			];
	
			try{
				$result = $client->refund($param);
				$success++;
			} catch (Exception $e) {
				return ['code'=>-1, 'msg'=>$e->getMessage()];
			}
			$i++;
			$leftmoney-=$money;
			if($leftmoney<=0)break;
		}

		return ['code'=>0];
	}

	//处理合单支付参数
	static private function combineOrderParams($param, $submoneys){
		global $channel;
		if(strpos($channel['appurl'], ',')){
			$sub_mchids = explode(',', $channel['appurl']);
			shuffle($sub_mchids);
		}

		$sub_orders = [];
        $i = 1;
        foreach($submoneys as $money){
            $sub_order = [
                'attach' => 'combine',
                'amount' => [
                    'total_amount' => $money,
                    'currency' => $param['amount']['currency'],
                ],
                'out_trade_no' => $param['out_trade_no'].$i,
                'description' => $param['description'],
            ];
			if(isset($sub_mchids)){
				$sub_order['sub_mchid'] = $sub_mchids[($i-1)%count($sub_mchids)];
			}
            $sub_orders[] = $sub_order;
            $i++;
        }
		if(!empty($param['scene_info']['h5_info'])) $param['scene_info']['device_id'] = '10001';
        $newparam = [
            'combine_out_trade_no' => $param['out_trade_no'],
            'scene_info' => $param['scene_info'],
            'sub_orders' => $sub_orders,
            'notify_url' => $param['notify_url'],
        ];
		if(isset($param['payer'])){
			$newparam['combine_payer_info']['openid'] = $param['payer']['sp_openid'];
		}
		return $newparam;
	}

	//投诉通知回调
	static public function complainnotify(){
		global $channel;

		$wechatpay_config = require(PAY_ROOT.'inc/config.php');
		try{
			$client = new \WeChatPay\V3\BaseService($wechatpay_config);
			$data = $client->notify();
		} catch (Exception $e) {
			$client->replyNotify(false, $e->getMessage());
			exit;
		}

		$model = \lib\Complain\CommUtil::getModel($channel);
		$model->refreshNewInfo($data['complaint_id'], $data['action_type']);

		$client->replyNotify(true);
	}
}