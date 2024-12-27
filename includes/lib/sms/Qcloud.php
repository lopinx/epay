<?php

namespace lib\sms;

class Qcloud
{
	private static $apiurl = 'https://yun.tim.qq.com/v5/tlssmssvr/sendsms';
	private $appid;
	private $appkey;

	public function __construct($appid, $appkey)
	{
		$this->appid = $appid;
		$this->appkey = $appkey;
	}

	/**
	 * 指定模板单发短信
	 * @param string $mobile 手机号码
	 * @param string $tpl_id 模板ID
	 * @param array $params 模板参数
	 * @param string $sign 签名内容
	 * @return bool
	 */
	public function send($mobile, $tpl_id, $params, $sign)
	{
		if (empty($this->appid) || empty($this->appkey)) return false;
		$time = time();
		$random = rand(100000, 999999);
		$url = self::$apiurl . "?sdkappid=" . $this->appid . "&random=" . $random;
		$data = [
			'tel' => [
				'nationcode' => '86',
				'mobile' => $mobile
			],
			'params' => $params,
			'time' => $time,
			'tpl_id' => intval($tpl_id),
			'sign' => $sign,
			'sig' => $this->getSig($random, $time, $mobile),
		];
		try {
			$res = $this->curlPost($url, json_encode($data));
			$arr = json_decode($res, true);
			return $arr;
		} catch (\Exception $e) {
			return ['result' => -1, 'errmsg' => $e->getMessage()];
		}
	}

	//生成签名
	private function getSig($random, $time, $mobile)
	{
		$signstr = 'appkey=' . $this->appkey . '&random=' . $random . '&time=' . $time . '&mobile=' . $mobile;
		return hash("sha256", $signstr);
	}

	//发起POST请求
	private function curlPost($url, $data)
	{
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Content-Type: application/json; charset=utf8";
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$res = curl_exec($ch);
		if (curl_errno($ch) > 0) {
			$errmsg = curl_error($ch);
			curl_close($ch);
			throw new \Exception($errmsg);
		}
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		if ($httpCode != 200) {
			throw new \Exception('http_code=' . $httpCode);
		}
		return $res;
	}
}
