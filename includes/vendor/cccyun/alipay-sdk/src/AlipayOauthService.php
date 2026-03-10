<?php

namespace Alipay;

/**
 * 支付宝快捷登录服务类
 * @see https://opendocs.alipay.com/open/repo-01480o
 */
class AlipayOauthService extends AlipayService
{
    /**
     * @param $config 支付宝配置信息
     */
    public function __construct($config)
    {
        if(isset($config['app_auth_token'])) unset($config['app_auth_token']);
        parent::__construct($config);
    }

    /**
     * 跳转支付宝授权页面
     * @param $redirect_uri 回调地址
     * @param $state
     * @param $is_get_url 是否只返回url
     * @return void|string
     */
    public function oauth($redirect_uri, $state = null, $is_get_url = false)
    {
        $param = [
            'app_id' => $this->appId,
            'scope' => 'auth_base',
            'redirect_uri' => $redirect_uri,
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

    /**
     * 支付宝会员授权信息查询
     * @param $accessToken 用户授权令牌
     * @return mixed {"code":"10000","msg":"Success","user_id":"支付宝用户的userId","avatar":"用户头像地址","city":"市名称","nick_name":"用户昵称","province":"省份名称","gender":"性别MF"}
     */
    public function userinfo($accessToken)
    {
        $apiName = 'alipay.user.info.share';
        $params = [
            'auth_token' => $accessToken
        ];

        return $this->aopExecute($apiName, null, $params);
    }


    /**
     * 跳转支付宝第三方应用授权页面
     * @param $redirect_uri 回调地址
     * @param $state
     * @param $is_get_url 是否只返回url
     * @return void|string
     */
    public function appOauth($redirect_uri, $state = null, $is_get_url = false)
    {
        $param = [
            'app_id' => $this->appId,
            'redirect_uri' => $redirect_uri,
        ];
        if($state) $param['state'] = $state;

        $url = 'https://openauth.alipay.com/oauth2/appToAppAuth.htm?'.http_build_query($param);

        if ($is_get_url) {
            return $url;
        }

        header("Location: $url");
        exit();
    }

    /**
     * 跳转支付宝指定应用授权页面
     * @param $redirect_uri 回调地址
     * @param $app_types 对商家应用的限制类型
     * @param $state
     * @return mixed
     */
    public function appOauthAssign($redirect_uri, $app_types, $state = null)
    {
        $param = [
            'platformCode' => 'O',
            'taskType' => 'INTERFACE_AUTH',
            'agentOpParam' => [
                'redirectUri' => $redirect_uri,
                'appTypes' => $app_types,
                'isvAppId' => $this->appId,
                'state' => $state
            ],
        ];

        $biz_data = json_encode($param, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $pc_url = 'https://b.alipay.com/page/message/tasksDetail?bizData='.rawurlencode($biz_data);
        $app_url = 'alipays://platformapi/startapp?appId=2021003130652097&page=pages%2Fauthorize%2Findex%3FbizData%3D'.rawurlencode($biz_data);

        return [$pc_url, $app_url];
    }

    /**
     * 换取授权访问令牌
     * @param $code 授权码或刷新令牌
     * @param $grant_type 授权方式(authorization_code,refresh_token)
     * @return mixed {"user_id":"授权商户的user_id","auth_app_id":"授权商户的appid","app_auth_token":"应用授权令牌","app_refresh_token":"刷新令牌","re_expires_in":"3600"}
     */
    public function getAppToken($code, $grant_type = 'authorization_code')
    {
        $apiName = 'alipay.open.auth.token.app';
        $bizContent = [
            'grant_type' => $grant_type,
        ];
        if($grant_type == 'refresh_token'){
            $bizContent['refresh_token'] = $code;
        }else{
            $bizContent['code'] = $code;
        }
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 查询授权商家信息
     * @param $appAuthToken 应用授权令牌
     * @return mixed {"user_id":"授权商户的user_id","auth_app_id":"授权商户的appid","expires_in":31536000,"auth_methods":[],"auth_start":"授权生效时间","auth_end":"授权失效时间","status":"valid/invalid","is_by_app_auth":true}
     */
    public function appQuery($appAuthToken)
    {
        $apiName = 'alipay.open.auth.token.app.query';
        $bizContent = [
            'app_auth_token' => $appAuthToken,
        ];
        return $this->aopExecute($apiName, $bizContent);
    }
}