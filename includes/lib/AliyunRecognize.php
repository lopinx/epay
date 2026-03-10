<?php
namespace lib;

use Exception;

class AliyunRecognize
{
	private $AccessKeyId;
	private $AccessKeySecret;
	private $Endpoint = 'ocr-api.cn-hangzhou.aliyuncs.com'; //API接入域名
	private $Version = '2021-07-07'; //API版本号

	function __construct($AccessKeyId, $AccessKeySecret){
		$this->AccessKeyId = $AccessKeyId;
		$this->AccessKeySecret = $AccessKeySecret;
	}
	
	//身份证识别
	public function RecognizeIdcard($file_path){
		$arr = $this->request(__FUNCTION__, file_get_contents($file_path), true);
		return json_decode($arr['Data'],true);
	}

	//护照识别
	public function RecognizePassport($file_path){
		$arr = $this->request(__FUNCTION__, file_get_contents($file_path), true);
		return json_decode($arr['Data'],true);
	}

	//银行卡识别
	public function RecognizeBankCard($file_path){
		$arr = $this->request(__FUNCTION__, file_get_contents($file_path), true);
		return json_decode($arr['Data'],true);
	}

	//营业执照识别
	public function RecognizeBusinessLicense($file_path){
		$arr = $this->request(__FUNCTION__, file_get_contents($file_path), true);
		return json_decode($arr['Data'],true);
	}

	//银行开户许可证识别
	public function RecognizeBankAccountLicense($file_path){
		$arr = $this->request(__FUNCTION__, file_get_contents($file_path), true);
		return json_decode($arr['Data'],true);
	}

	//签名方法
	private function aliyunSignature($parameters, $accessKeySecret, $method)
	{
		ksort($parameters);
		$canonicalizedQueryString = '';
		foreach ($parameters as $key => $value) {
			if($value === null || $value instanceof \CURLFile) continue;
			$canonicalizedQueryString .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
		}
		$stringToSign = $method . '&%2F&' . $this->percentencode(substr($canonicalizedQueryString, 1));
		$signature = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&", true));

		return $signature;
	}
	private function percentEncode($str)
	{
		$search = ['+', '*', '%7E'];
		$replace = ['%20', '%2A', '~'];
		return str_replace($search, $replace, urlencode($str));
	}
	//请求方法（当需要返回列表等数据时，returnData=true）
	private function request($action, $file = null, $returnData=false){
		if(empty($this->AccessKeyId)||empty($this->AccessKeySecret))return false;
		$url='https://'.$this->Endpoint.'/';
		$data=array(
			'Action' => $action,
			'Format' => 'JSON',
			'Version' => $this->Version,
			'AccessKeyId' => $this->AccessKeyId,
			'SignatureMethod' => 'HMAC-SHA1',
			'Timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
			'SignatureVersion' => '1.0',
			'SignatureNonce' => $this->random(8));
		$data['Signature'] = $this->aliyunSignature($data, $this->AccessKeySecret, 'POST');
		$url.='?'.http_build_query($data);
		$ch=curl_init($url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		if($file !== null){
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/octet-stream']);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $file);
		}
		$json=curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if($httpCode>=200 && $httpCode<300){
			if($returnData==true){
				$arr = json_decode($json,true);
				if(!$arr) throw new Exception('无法解析返回数据');
				return $arr;
			}else{
				return true;
			}
		}else{
			$arr=json_decode($json,true);
			if(isset($arr['Code']) && isset($arr['Message'])){
				if(strpos($arr['Message'],' server string to sign is:')!==false){
					$arr['Message'] = substr($arr['Message'],0,strpos($arr['Message'],' server string to sign is:')-1);
				}
				throw new Exception('['.$arr['Code'].'] '.$arr['Message']);
			}else{
				throw new Exception('无法解析返回数据');
			}
		}
	}
	private function random($length, $numeric = 0) {
		$seed = base_convert(md5(microtime().$_SERVER['DOCUMENT_ROOT']), 16, $numeric ? 10 : 35);
		$seed = $numeric ? (str_replace('0', '', $seed).'012340567890') : ($seed.'zZ'.strtoupper($seed));
		$hash = '';
		$max = strlen($seed) - 1;
		for($i = 0; $i < $length; $i++) {
			$hash .= $seed[mt_rand(0, $max)];
		}
		return $hash;
	}

}