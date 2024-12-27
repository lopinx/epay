<?php

class paypal_plugin
{
	static public $info = [
		'name'        => 'paypal', //支付插件英文名称，需和目录名称一致，不能有重复
		'showname'    => 'PayPal', //支付插件显示名称
		'author'      => 'PayPal', //支付插件作者
		'link'        => 'https://www.paypal.com/', //支付插件作者链接
		'types'       => ['paypal'], //支付插件支持的支付方式，可选的有alipay,qqpay,wxpay,bank
		'inputs' => [ //支付插件要求传入的参数以及参数显示名称，可选的有appid,appkey,appsecret,appurl,appmchid
			'appid' => [
				'name' => 'ClientId',
				'type' => 'input',
				'note' => '',
			],
			'appkey' => [
				'name' => 'ClientSecret',
				'type' => 'input',
				'note' => '',
			],
			'appswitch' => [
				'name' => '模式选择',
				'type' => 'select',
				'options' => [0=>'线上模式',1=>'沙盒模式'],
			],
		],
		'select' => null,
		'note' => '', //支付密钥填写说明
		'bindwxmp' => false, //是否支持绑定微信公众号
		'bindwxa' => false, //是否支持绑定微信小程序
	];

	static public function submit(){
		global $siteurl, $channel, $order, $ordername, $sitename, $conf, $DB;

		require_once(PAY_ROOT."inc/PayPalClient.php");

		$parameter = [
            'intent'            => 'CAPTURE',
            'purchase_units'    => [
                [
                    'amount'        => [
                        'currency_code' => 'USD',
                        'value'         => $order['realmoney'],
                    ],
                    'description'   => $order['name'],
					'custom_id'     => TRADE_NO,
                    'invoice_id'    => TRADE_NO,
                ],
            ],
            'application_context'=> [
                'cancel_url'    => $siteurl.'pay/cancel/'.TRADE_NO.'/',
                'return_url'    => $siteurl.'pay/return/'.TRADE_NO.'/',
            ],
        ];

		try {
			$approvalUrl = \lib\Payment::lockPayData(TRADE_NO, function() use($channel, $parameter) {
				$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
				$result = $client->createOrder($parameter);

				$approvalUrl = null;
				foreach($result['links'] as $link){
					if($link['rel'] == 'approve'){
						$approvalUrl = $link['href'];
					}
				}
				if(empty($approvalUrl)){
					throw new Exception('获取支付链接失败');
				}
				return $approvalUrl;
			});

			return ['type'=>'jump','url'=>$approvalUrl];
		}
		catch (Exception $ex) {
			sysmsg('PayPal下单失败：'.$ex->getMessage());
		}
	}

	//同步回调
	static public function return(){
		global $channel, $order;

		require_once(PAY_ROOT."inc/PayPalClient.php");
		
		if (isset($_GET['token']) && isset($_GET['PayerID'])) {
		
			$token = $_GET['token'];
			try {
				$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
				$result = $client->captureOrder($token);
			} catch (Exception $ex) {
				return ['type'=>'error','msg'=>'支付订单失败 '.$ex->getMessage()];
			}

			$captures = $result['purchase_units'][0]['payments']['captures'][0];
			$amount = $captures['seller_receivable_breakdown']['gross_amount']['value'];
			$trade_no = $captures['id'];
			$out_trade_no = $captures['invoice_id'];
			$buyer = $result['payer']['email_address'];

			if($out_trade_no == TRADE_NO){
				processReturn($order, $trade_no, $buyer);
			}else{
				return ['type'=>'error','msg'=>'订单信息校验失败'];
			}
		} else {
			return ['type'=>'error','msg'=>'PayPal返回参数错误'];
		}
	}

	static public function cancel(){
		return ['type'=>'page','page'=>'error'];
	}

	static public function webhook(){
		global $channel, $order;
		$json = file_get_contents('php://input');
		$arr = json_decode($json, true);
		if(!$arr || empty($arr['event_type'])){
            exit('事件类型为空');
        }
		if(!in_array($arr['event_type'], ['PAYMENT.CAPTURE.COMPLETED'])){
            exit('其他事件('.$arr['event_type'].':'.$arr['summary'].')');
        }
		if(empty($channel['appsecret'])){
			exit('未配置webhookid');
		}

		$crc32 = crc32($json);
        if (empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_ID']) || empty($_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME']) || empty($crc32)) {
			exit('签名数据为空');
        }
        $sign_string = $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'].'|'.$_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'].'|'.$channel['appsecret'].'|'.$crc32;

        // 通过PAYPAL-CERT-URL头信息去拿公钥
        $public_key = openssl_pkey_get_public(get_curl($_SERVER['HTTP_PAYPAL_CERT_URL']));
        $details = openssl_pkey_get_details($public_key);
        $verify = openssl_verify($sign_string, base64_decode($_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG']), $details['key'], 'SHA256');
        if($verify != 1)
        {
			exit('签名验证失败');
        }

		$resource = $arr['resource'];
		$amount = $resource['amount']['value'];
		$trade_no = $resource['id'];
		$out_trade_no = $resource['invoice_id'];
	}

	//退款
	static public function refund($order){
		global $channel;
		if(empty($order))exit();

		require_once(PAY_ROOT."inc/PayPalClient.php");

		$parameter = [
            'amount'    => [
                'currency_code'  => 'USD',
                'value'     => $order['refundmoney'],
            ],
        ];

		try{
			$client = new PayPalClient($channel['appid'], $channel['appkey'], $channel['appswitch']);
			$res = $client->refundPayment($order['api_trade_no'], $parameter);
			$result = ['code'=>0, 'trade_no'=>$res['id'], 'refund_fee'=>$res['amount']['value']];
		}catch(Exception $e){
			$result = ['code'=>-1, 'msg'=>$e->getMessage()];
		}
		return $result;
	}

}