<?php

/**
 * @see https://www.yuque.com/hlwl/cziks
 */
class HlpayClient
{
    //接口地址
    private $gateway_url = 'https://api.hbhlpay.com';

    //商户编号
    private $ins_id;

	//应用APPID
    private $app_id;
    
    //应用私钥
    private $merchant_private_key;

    //平台公钥
    private $platform_public_key;

    private $sign_type = 'RSA2';

    public function __construct($ins_id, $app_id, $merchant_private_key, $platform_public_key)
    {
        $this->ins_id = $ins_id;
        $this->app_id = $app_id;
        $this->merchant_private_key = $merchant_private_key;
        $this->platform_public_key = $platform_public_key;
    }

    //请求API接口并解析返回数据
    public function execute($path, $bizContent)
    {
        $requrl = $this->gateway_url . $path;
        $params = [
            'ins_id' => $this->ins_id,
            'app_id' => $this->app_id,
            'sign_type' => $this->sign_type,
            'timestamp' => time(),
            'biz_content' => json_encode($bizContent)
        ];
        $params['sign'] = $this->generateSign($params);
        $response = get_curl($requrl, http_build_query($params));
        $result = json_decode($response, true);
		if(isset($result['code']) && $result['code']==1){
			return $result['data'];
		}elseif(isset($result['msg'])){
			throw new Exception($result['msg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
    }

    //获取待签名字符串
	private function getSignContent($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!=='' && $v!==null){
				$signstr .= $k.'='.$v.'&';
			}
		}
		$signstr = substr($signstr,0,-1);
		return $signstr;
	}

    //请求参数签名
	private function generateSign($param){
		return $this->rsaPrivateSign($this->getSignContent($param));
	}

    //验签方法
	public function verifySign($param){
		if(empty($param['sign'])) return false;
		return $this->rsaPubilcSign($this->getSignContent($param), $param['sign']);
	}

	//应用私钥签名
	private function rsaPrivateSign($data){
		$priKey = $this->merchant_private_key;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
		$pkeyid = openssl_pkey_get_private($res);
		if(!$pkeyid){
			throw new Exception('签名失败，应用私钥不正确');
		}
		openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
		$signature = base64_encode($signature);
		return $signature;
	}

	//平台公钥验签
	private function rsaPubilcSign($data, $signature){
		$pubKey = $this->platform_public_key;
        $res = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
		$pubkeyid = openssl_pkey_get_public($res);
		if(!$pubkeyid){
			throw new Exception('验签失败，平台公钥不正确');
		}
		$result = openssl_verify($data, base64_decode($signature), $pubkeyid, OPENSSL_ALGO_SHA256);
		return $result === 1;
	}
}
