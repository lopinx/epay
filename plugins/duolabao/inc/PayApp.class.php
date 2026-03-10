<?php

class PayApp
{
    const GATEWAY = 'https://openapi.duolabao.com';
    private $config = [];

    function __construct($config)
	{
		$this->config = $config;
		if (!$this->config['customerNum'] || !$this->config['shopNum'] || !$this->config['secretKey'] || !$this->config['accessKey']) {
			throw new Exception('参数不完整！');
		}
	}

    public function submit($path, $param){
        $json = json_encode($param);
        $time = time();
        $token = $this->get_token($path, $json, $time);
        $headers = [
            'Content-Type: application/json',
            'accessKey: ' . $this->config['accessKey'],
            'timestamp: ' . $time,
            'token: ' . $token
        ];
        $response = $this->Curl($path, $headers, $json);
        $result = json_decode($response, true);
        if($result['result'] == 'success'){
            return $result['data'];
        }elseif(isset($result['error'])){
            throw new Exception('['.$result['error']['errorCode'].']'.$result['error']['errorMsg']);
        }else{
            throw new Exception('接口请求失败');
        }
    }

    public function verifyNotify()
	{
		$headers = array(); 
		foreach ($_SERVER as $key => $value) { 
			if ('HTTP_' == substr($key, 0, 5)) { 
				$headers[str_replace('_', '-', substr($key, 5))] = $value; 
			}
		}
		$signString = "secretKey={$this->config['secretKey']}&timestamp={$headers['TIMESTAMP']}";
		$token = strtoupper(sha1($signString));
		if ($token !== $headers['TOKEN']) {
			return false;
		}
		return true;
	}

    private function get_token($path, $body, $time){
        $sign_data = [
			'secretKey' => $this->config['secretKey'],
			'timestamp' => $time,
			'path'      => $path,
			'body'      => $body,
		];
		$o = '';
		foreach ($sign_data as $k => $v) {
			 $o .= "{$k}={$v}&";
		}
		$o = substr($o , 0 , -1);
		$token = strtoupper(sha1($o));
        return $token;
    }

    private function Curl($path, $headers, $post = null)
	{
		$url = self::GATEWAY . $path;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$httpheader[] = "Accept: */*";
		$httpheader[] = "Accept-Encoding: gzip,deflate";
		$httpheader[] = "Accept-Language: zh-CN,zh;q=0.9";
		$httpheader[] = "Connection: keep-alive";
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		if ($post) {
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($httpheader, $headers));
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36');
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}