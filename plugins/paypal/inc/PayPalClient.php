<?php

class PayPalClient
{
    private static $api_url = [
        0 => 'https://api-m.paypal.com',
        1 => 'https://api-m.sandbox.paypal.com',
    ];

    private $gateway_url;
    private $client_id;
    private $client_secret;
    private $access_token;

    public function __construct($client_id, $client_secret, $mode)
    {
        $this->gateway_url = self::$api_url[$mode];
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token = $this->getAccessToken();
    }

    //获取Token
    public function getAccessToken(){
        $path = '/v1/oauth2/token';
        $post = 'grant_type=client_credentials';
        $result = $this->curl($this->gateway_url . $path, $post, true);
        $this->access_token = $result['access_token'];
        return $this->access_token;
    }

    //创建订单
    public function createOrder($params){
        $path = '/v2/checkout/orders';
        return $this->curl($this->gateway_url . $path, $params);
    }

    //支付订单
    public function captureOrder($id){
        $path = '/v2/checkout/orders/'.$id.'/capture';
        return $this->curl($this->gateway_url . $path, '');
    }

    //查询订单
    public function orderDetail($id){
        $path = '/v2/checkout/orders/'.$id;
        return $this->curl($this->gateway_url . $path);
    }

    //查询支付
    public function paymentDetail($capture_id){
        $path = '/v2/payments/captures/'.$capture_id;
        return $this->curl($this->gateway_url . $path);
    }

    //退款
    public function refundPayment($capture_id, $params){
        $path = '/v2/payments/captures/'.$capture_id.'/refund';
        return $this->curl($this->gateway_url . $path, $params);
    }

    //退款查询
    public function refundDetail($refund_id){
        $path = '/v2/payments/refunds/'.$refund_id;
        return $this->curl($this->gateway_url . $path);
    }

    private function curl($url, $data = null, $auth = false)
    {
        $header[] = 'Accept: application/json';
        $header[] = 'Content-Type: application/json';
        if(!empty($this->access_token))
        {
            $header[] = 'Authorization: Bearer '.$this->access_token;
        }
        if($data !== null && is_array($data)){
            $data = json_encode($data);
        }

        $curlVersion = curl_version();
        $ua = 'PayPalSDK/PayPal-PHP-SDK 1.14.0 (platform-ver='.PHP_VERSION.'; os='.str_replace(' ', '_', php_uname('s') . ' ' . php_uname('r')).'; machine='.php_uname('m').'; curl='.$curlVersion['version'].')';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if($data !== null){
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        // 获取token
        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->client_id.':'.$this->client_secret);
        }
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception('curl error: '.curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $result = json_decode($response, true);
        if($httpCode>=200 && $httpCode<300){
            return $result;
        }else{
            if(isset($result['error_description'])){
                throw new Exception('['.$result['error'].']'.$result['error_description']);
            }elseif(isset($result['message'])){
                throw new Exception('['.$result['name'].']'.$result['message']);
            }else{
                throw new Exception('返回数据解析失败(httpCode='.$httpCode.')');
            }
        }
    }
}