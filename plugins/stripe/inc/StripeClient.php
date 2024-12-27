<?php

namespace Stripe;

use Exception;

class StripeClient
{
    const VERSION = '10.17.0';
    const DEFAULT_API_BASE = 'https://api.stripe.com';

    private $api_key;

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function request($method, $path, $params = null)
    {
        $url = self::DEFAULT_API_BASE . $path;
        [$httpCode, $response] = $this->curl($method, $url, $params);
        $arr = json_decode($response, true);
        if($httpCode >= 200 && $httpCode < 300 && $arr) {
            return $arr;
        }elseif(isset($arr['error'])){
            throw new Exception($arr['error']['message']);
        }else{
            throw new Exception('返回数据解析失败');
        }
    }

    private function getHttpHeaders()
    {
        $uaString = 'Stripe/v1 PhpBindings/' . self::VERSION;

        $curlVersion = curl_version();
        $clientInfo = [
            'httplib' => 'curl ' . $curlVersion['version'],
            'ssllib' => $curlVersion['ssl_version'],
        ];

        $langVersion = PHP_VERSION;
        $uname = function_exists('php_uname') ? php_uname() : '(disabled)';

        $ua = [
            'bindings_version' => self::VERSION,
            'lang' => 'php',
            'lang_version' => $langVersion,
            'publisher' => 'stripe',
            'uname' => $uname,
        ];
        if ($clientInfo) {
            $ua = array_merge($clientInfo, $ua);
        }

        return [
            'X-Stripe-Client-User-Agent: ' . json_encode($ua),
            'User-Agent: ' . $uaString,
            'Authorization: Bearer ' . $this->api_key,
        ];
    }

    private function curl($method, $url, $params = null)
    {
        $httpheaders = $this->getHttpHeaders();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheaders);

        if ($method == 'post') {
            if(is_array($params)) $params = http_build_query($params);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        } elseif($method == 'delete') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            if($params && count($params) > 0) {
                $url .= '?' . http_build_query($params);
            }
        } elseif($method == 'get') {
            if($params && count($params) > 0) {
                $url .= '?' . http_build_query($params);
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        return [$httpCode, $response];
    }
}