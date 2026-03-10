<?php
namespace easypay;
use Exception;

/**
 * @see https://www.yuque.com/pandans/ws1g9s/ot6775
 * @see https://mtest.eycard.cn/doc/web/#/285
 */
class PayApp
{
	private $pay_gateway = 'https://platform.eycard.cn:8443';
	private $refund_gateway = 'https://platform.eycard.cn:6111';
	private $signType = 'RSA';
	private $orgId;
	private $orgMercode;
	private $orgTermno;
	private $public_key_path = PAY_ROOT.'cert/pay.pem';
	private $private_key_path = PAY_ROOT.'cert/mch.key';

	public function __construct($orgId, $orgMercode, $orgTermno)
	{
		$this->orgId = $orgId;
		$this->orgMercode = $orgMercode;
		$this->orgTermno = $orgTermno;
	}

	//发起API请求
	public function paySubmit($path, $orgTrace, $data){
		$requrl = $this->pay_gateway . $path;
		$sign = $this->generateSign($data);
		$params = [
			'orgId' => $this->orgId,
			'orgMercode' => $this->orgMercode,
			'orgTermno' => $this->orgTermno,
			'orgTrace' => $orgTrace,
			'sign' => $sign,
			'signType' => $this->signType,
			'data' => $data,
		];

		$response = get_curl($requrl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($response, true);
		if(isset($result['sysRetcode']) && $result['sysRetcode']=='000000'){
			if(!empty($result['sign'])){
				if(!$this->verifySign($result['data'], $result['sign'])){
					throw new Exception('返回数据验签失败');
				}
			}
			return $result['data'];
		}elseif(isset($result['sysRetmsg'])){
			throw new Exception('['.$result['sysRetcode'].']'.$result['sysRetmsg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}

	//发起API请求
	public function refundSubmit($path, $orgTrace, $bizData){
		$requrl = $this->refund_gateway . $path;
		$sign = $this->generateSign($bizData);
		$params = [
			'orgId' => $this->orgId,
			'orgMercode' => $this->orgMercode,
			'orgTermno' => $this->orgTermno,
			'orgTrace' => $orgTrace,
			'sign' => $sign,
			'signType' => $this->signType,
			'bizData' => $bizData,
		];

		$response = get_curl($requrl, json_encode($params), 0, 0, 0, 0, 0, ['Content-Type: application/json; charset=utf-8']);
		$result = json_decode($response, true);
		if(isset($result['sysRetCode']) && $result['sysRetCode']=='000000'){
			if(!empty($result['sign'])){
				if(!$this->verifySign($result['bizData'], $result['sign'])){
					throw new Exception('返回数据验签失败');
				}
			}
			return $result['bizData'];
		}elseif(isset($result['sysRetMsg'])){
			throw new Exception('['.$result['sysRetCode'].']'.$result['sysRetMsg']);
		}else{
			throw new Exception('返回数据解析失败');
		}
	}


	//获取待签名字符串
	private function getSignContent($param){
		ksort($param);
		$signstr = '';
	
		foreach($param as $k => $v){
			if($k != "sign" && $v!=''){
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
	public function verifySign($param, $sign){
		if(empty($sign)) return false;
		return $this->rsaPubilcSign($this->getSignContent($param), $sign);
	}

	//商户私钥签名
	private function rsaPrivateSign($data){
		$res = file_get_contents($this->private_key_path);
		$pkeyid = openssl_pkey_get_private($res);
		if(!$pkeyid){
			throw new Exception('签名失败，商户私钥不正确');
		}
		openssl_sign($data, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
		$signature = base64_encode($signature);
		return $signature;
	}

	//平台公钥验签
	private function rsaPubilcSign($data, $signature){
		$res = file_get_contents($this->public_key_path);
		$pubkeyid = openssl_pkey_get_public($res);
		if(!$pubkeyid){
			throw new Exception('验签失败，易生公钥不正确');
		}
		$result = openssl_verify($data, base64_decode($signature), $pubkeyid, OPENSSL_ALGO_SHA256);
		return $result;
	}

	//公钥加密
    public function rsaPublicEncrypt($content) {
        $res = file_get_contents($this->public_key_path);
		$pubKey = openssl_pkey_get_public($res);
		if(!$pubKey){
			throw new Exception('加密失败，易生公钥不正确');
		}
        $encrypted = '';
		openssl_public_encrypt($content, $encrypted, $pubKey, OPENSSL_PKCS1_PADDING);
		return base64_encode($encrypted);
    }

	//私钥解密
    public function rsaPrivateDecrypt($content) {
        $res = file_get_contents($this->private_key_path);
		$priKey = openssl_pkey_get_private($res);
		if(!$priKey){
			throw new Exception('解密失败，商户私钥不正确');
		}
        $decrypted = '';
		openssl_private_decrypt(base64_decode($content), $decrypted, $priKey, OPENSSL_PKCS1_PADDING);
		return $decrypted;
    }

}