<?php

class SwiftpassClient
{

    //版本号
    private $version = '2.0';

    //签名类型
    private $signType = 'MD5';

    //网关地址
    private $gatewayUrl = 'https://pay.swiftpass.cn/pay/gateway';
    
    //商户号
    private $mchId;

    //商户MD5密钥
    private $key;

    //商户RSA私钥
    private $rsaPrivateKey;

    //平台RSA公钥
    private $rsaPublicKey;

    /**
     * @param $config 支付配置信息
     */
    public function __construct($config)
    {
        $this->signType = $config['sign_type'];
        if (empty($config['mchid'])) {
            throw new \InvalidArgumentException("商户号不能为空");
        }
        $this->mchId = $config['mchid'];
        if ($this->signType == 'MD5') {
            if (empty($config['key'])) {
                throw new \InvalidArgumentException("商户密钥不能为空");
            }
            $this->key = $config['key'];
        }
        if (substr($this->signType, 0, 3) == 'RSA') {
            if (empty($config['rsa_private_key']) || empty($config['rsa_public_key'])) {
                throw new \InvalidArgumentException("商户私钥/平台公钥不能为空");
            }
            $this->rsaPrivateKey = $config['rsa_private_key'];
            $this->rsaPublicKey = $config['rsa_public_key'];
        }
        if (!empty($config['gateway_url'])) {
            $this->gatewayUrl = $config['gateway_url'];
        }
    }

    /**
     * 发起请求并解析返回结果
     */
    public function requestApi($params)
    {
        if (empty($params['service'])) {
            throw new \InvalidArgumentException("service参数不能为空");
        }
        $publicParams = [
            'mch_id' => $this->mchId,
            'version' => $this->version,
            'sign_type' => $this->signType,
            'nonce_str' => $this->getNonceStr()
        ];
        $params = array_merge($publicParams, $params);
        $params['sign'] = $this->makeSign($params);
        
        $xml = $this->array2Xml($params);
        $response = $this->curl($this->gatewayUrl, $xml);
        $result = $this->xml2array($response);
        if (isset($result['status']) && $result['status'] == '0') {
            if (!$this->verifySign($result)) {
                throw new Exception('返回数据签名校验失败');
            }
            if (isset($result['result_code']) && $result['result_code'] == '0') {
                return $result;
            } else {
                throw new \Exception('['.$result['err_code'].']'.$result['err_msg']);
            }
        } elseif(isset($result['message'])) {
            throw new \Exception($result['message']);
        } else {
            throw new \Exception('返回数据解析失败');
        }
    }

    /**
     * 支付结果通知
     * @return bool|mixed
     */
    public function notify()
    {
        $xml = file_get_contents("php://input");
        if (empty($xml)) {
            throw new \Exception('no_data');
        }
        $result = $this->xml2array($xml);
        if (!$result) {
            throw new \Exception('xml_error');
        }
        if (!$this->verifySign($result)) {
            throw new \Exception('sign_error');
        }
        return $result;
    }

    /**
     * 验证签名
     */
    private function verifySign($params)
    {
        if(!isset($params['sign'])) return false;
        $signStr = $this->getSignContent($params);
        if (substr($this->signType, 0, 3) == 'RSA') {
            return $this->rsaPublicVerify($signStr, $params['sign']);
		} else {
            $sign = md5($signStr . '&key=' . $this->key);
            $sign = strtoupper($sign);
            return $params['sign'] === $sign;
		}
    }

    /**
     * 生成签名
     * @param $params
     * @return string
     */
    private function makeSign($params)
    {
        $signStr = $this->getSignContent($params);
        if (substr($this->signType, 0, 3) == 'RSA') {
            $sign = $this->rsaPrivateSign($signStr);
		} else {
            $sign = md5($signStr . '&key=' . $this->key);
            $sign = strtoupper($sign);
		}
        return $sign;
    }

    /**
     * 生成待签名字符串
     */
    private function getSignContent($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if($k != 'sign' && !$this->isEmpty($v)){
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr = substr($signStr, 0, -1);
        return $signStr;
    }

    /**
     * 商户RSA私钥签名
     */
    private function rsaPrivateSign($data)
    {
        $key = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($this->rsaPrivateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        $privatekey = openssl_get_privatekey($key);
        if (!$privatekey) {
            throw new \Exception("签名失败，商户RSA私钥错误");
        }
        if ($this->signType == 'RSA_1_1') {
			openssl_sign($data, $sign, $privatekey);
		} else if ($this->signType == 'RSA_1_256') {
			openssl_sign($data, $sign, $privatekey, OPENSSL_ALGO_SHA256);
		}
        return base64_encode($sign);
    }

    /**
     * 平台公钥验签
     */
    private function rsaPublicVerify($data, $sign)
    {
        $key = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($this->rsaPublicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        $publickey = openssl_get_publickey($key);
        if (!$publickey) {
            throw new \Exception("验签失败，平台RSA公钥错误");
        }
        if ($this->signType == 'RSA_1_1') {
			$result = openssl_verify($data, base64_decode($sign), $publickey);
		} else if ($this->signType == 'RSA_1_256') {
			$result = openssl_verify($data, base64_decode($sign), $publickey, OPENSSL_ALGO_SHA256);
		}
        return $result === 1;
    }

    /**
     * 校验某字符串或可被转换为字符串的数据，是否为 NULL 或均为空白字符.
     *
     * @param string|null $value
     *
     * @return bool
     */
    private function isEmpty($value)
    {
        return $value === null || trim($value) === '';
    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return 产生的随机字符串
     */
    private function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 转为XML数据
     * @param array $data 源数据
     * @return string
     */
    private function array2Xml($data)
    {
        if (!is_array($data)) {
            return false;
        }
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= (is_numeric($val) ? "<{$key}>{$val}</{$key}>" : "<{$key}><![CDATA[{$val}]]></{$key}>");
        }
        return $xml . '</xml>';
    }

    /**
     * 解析XML数据
     * @param string $xml 源数据
     * @return mixed
     */
    private function xml2array($xml)
    {
        if (!$xml) {
            return false;
        }
        if(function_exists('libxml_disable_entity_loader')) {
			libxml_disable_entity_loader(true);
		}
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }

    /**
     * 以post方式提交xml到对应的接口url
     * @param string $url  url
     * @param string $xml  需要post的xml数据
     * @param int $second   url执行超时时间
     * @return string
     */
    private function curl($url, $xml, $second = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        if (curl_errno($ch) > 0) {
            $errmsg = curl_error($ch);
            curl_close($ch);
            throw new \Exception('call http err :'.$errmsg, 0);
        }
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpStatusCode != 200) {
            curl_close($ch);
            throw new \Exception('call http err httpcode='.$httpStatusCode, $httpStatusCode);
        }
        curl_close($ch);
        return $data;
    }
}