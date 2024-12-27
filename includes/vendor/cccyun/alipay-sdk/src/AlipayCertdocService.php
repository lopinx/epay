<?php

namespace Alipay;

/**
 * 支付宝实名证件信息比对验证服务类
 * @see https://opendocs.alipay.com/open/01bny6
 */
class AlipayCertdocService extends AlipayService
{
    /**
     * @param $config 支付宝配置信息
     */
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * 实名证件信息比对验证预咨询
     * @param $cert_name 真实姓名
     * @param $cert_no 证件号码
     * @return mixed {"code":"10000","msg":"Success","verify_id":"申请验证ID"}
     */
    public function preconsult($cert_name, $cert_no)
    {
        $apiName = 'alipay.user.certdoc.certverify.preconsult';
        $bizContent = array(
            'user_name' => $cert_name, //真实姓名
            'cert_type' => 'IDENTITY_CARD', //证件类型
            'cert_no' => $cert_no
        );
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 实名证件信息比对验证咨询
     * @param $verify_id 申请验证ID
     * @param $auth_token 用户授权令牌
     * @return mixed {"code":"10000","msg":"Success","passed":"F","fail_reason":"姓名不一致","fail_params":"[\\\"user_name\\\"]"}
     */
    public function consult($verify_id, $auth_token)
    {
        $apiName = 'alipay.user.certdoc.certverify.consult';
        $bizContent = array(
            'verify_id' => $verify_id,
        );
        $params = [
            'auth_token' => $auth_token
        ];
        return $this->aopExecute($apiName, $bizContent, $params);
    }

    /**
     * 跳转支付宝授权页面
     * @param $redirect_uri 回调地址
     * @param $verify_id 申请验证ID
     * @param $state
     * @param $is_get_url 是否只返回url
     * @return void|string
     */
    public function oauth($redirect_uri, $verify_id, $state = null, $is_get_url = false)
    {
        $param = [
            'app_id' => $this->appId,
            'scope' => 'id_verify',
            'redirect_uri' => $redirect_uri,
            'cert_verify_id' => $verify_id
        ];
        if($state) $param['state'] = $state;

        $url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?'.http_build_query($param);

        if ($is_get_url) {
            return $url;
        }

        header("Location: $url");
        exit();
    }

    /**
     * 换取授权访问令牌
     * @param $code 授权码或刷新令牌
     * @param $grant_type 授权方式(authorization_code,refresh_token)
     * @return mixed {"user_id":"支付宝用户的唯一标识","open_id":"支付宝用户的唯一标识","access_token":"访问令牌","expires_in":"3600","refresh_token":"刷新令牌","re_expires_in":"3600"}
     */
    public function getToken($code, $grant_type = 'authorization_code')
    {
        $apiName = 'alipay.system.oauth.token';
        $params = [];
        $params['grant_type'] = $grant_type;
        if($grant_type == 'refresh_token'){
            $params['refresh_token'] = $code;
        }else{
            $params['code'] = $code;
        }
        return $this->aopExecute($apiName, null, $params);
    }
}