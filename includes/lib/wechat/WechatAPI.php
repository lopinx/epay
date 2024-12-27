<?php
namespace lib\wechat;

use Exception;

class WechatAPI
{
    private $wid;
    private $accessToken;

    public function __construct($id)
    {
        $this->wid = $id;
    }

    public function getAccessToken($force = false)
    {
        global $DB;
        if(!empty($this->accessToken)) return $this->accessToken;
        $DB->beginTransaction();
        try{
            $row = $DB->getRow("SELECT * FROM pre_weixin WHERE id='{$this->wid}' LIMIT 1 FOR UPDATE");
            if(!$row) throw new Exception('记录不存在');
            if($row['access_token'] && strtotime($row['expiretime']) - 200 >= time() && !$force){
                $DB->rollback();
                $this->accessToken = $row['access_token'];
                return $this->accessToken;
            }
            $appid = $row['appid'];
            $secret = $row['appsecret'];
            $url = "https://api.weixin.qq.com/cgi-bin/stable_token";
            $post = json_encode(['grant_type'=>'client_credential', 'appid'=>$appid, 'secret'=>$secret]);
            $output = get_curl($url, $post);
		    $res = json_decode($output, true);
            if (isset($res['access_token'])) {
                $this->accessToken = $res['access_token'];
                $expire_time = time() + $res['expires_in'];
                $DB->exec("UPDATE pre_weixin SET access_token=:access_token,updatetime=NOW(),expiretime=:expiretime WHERE id=:id", [':access_token'=>$this->accessToken, ':expiretime'=>date("Y-m-d H:i:s", $expire_time), ':id'=>$this->wid]);
            }elseif(isset($res['errmsg'])){
                throw new Exception('AccessToken获取失败：'.$res['errmsg']);
            }else{
                throw new Exception('AccessToken获取失败');
            }
            $DB->commit();
            return $this->accessToken;
        }catch(Exception $e){
            $DB->rollback();
		    throw $e;
        }
    }

    public function generate_scheme($path, $query, $expire = 600)
    {
        $access_token = $this->getAccessToken();
        $url = "https://api.weixin.qq.com/wxa/generatescheme?access_token=".$access_token;
        $data = ['jump_wxa'=>['path'=>$path, 'query'=>$query]];
        if($expire>0){
            $data['is_expire'] = true;
            $data['expire_time'] = time()+$expire;
        }
        $output = get_curl($url, json_encode($data));
        $res = json_decode($output, true);
        if ($res && $res['errcode'] == 0) {
            return $res['openlink'];
        }else{
            throw new Exception('urlscheme生成失败：'.$res['errmsg']);
        }
    }

    //发送微信公众号模板消息
    public function sendTemplateMessage($openid, $template_id, $jumpurl, $data){
        $access_token = $this->getAccessToken();
        $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.$access_token;
        $post = [
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => $jumpurl,
            'data' => $data
        ];
        $response = get_curl($url, json_encode($post));
        $res = json_decode($response, true);
        if ($res && $res['errcode'] == 0) {
            return true;
        }else{
            throw new Exception('模板消息发送失败：'.$res['errmsg']);
        }
    }
}