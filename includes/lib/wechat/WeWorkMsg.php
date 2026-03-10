<?php
namespace lib\wechat;

use Exception;

class WeWorkMsg
{
    private $token;
    private $crypt;

    public function __construct($token, $aeskey)
    {
        global $conf;
        $this->token = $token;
        $this->crypt = new WechatCrypt($aeskey);
    }

    //验证URL有效性
    public function verifyURL()
    {
        if (!$this->verifySignature($_GET['echostr'])) {
			exit('签名验证失败');
		}
        $msg = $this->crypt->decrypt($_GET['echostr']);
        if(!$msg) exit('消息解密失败');
        exit($msg);
    }

    //获取回调的消息内容
    public function getMessage()
    {
        $xml = file_get_contents('php://input');
        $arr = $this->xml2array($xml);
        if (!$arr) exit('消息体解析失败');
        if (!$this->verifySignature($arr['Encrypt'])) {
			exit('签名验证失败');
		}
        $msgtext = $this->crypt->decrypt($arr['Encrypt']);
        if(!$msgtext) exit('消息解密失败');
        $msg = $this->xml2array($msgtext);
        return $msg;
    }

    //响应消息内容
    public function responseMessage($array, $corpid)
    {
        $xml = $this->array2Xml($array);
        $encrypted = $this->crypt->encrypt($xml, $corpid);
        $timestamp = time();
        $nonce = time();
        $signature = $this->getSignature($timestamp, $nonce, $encrypted);
        $array = [
            'Encrypt' => $encrypted,
            'MsgSignature' => $signature,
            'TimeStamp' => $timestamp,
            'Nonce' => $nonce
        ];
        echo $this->array2Xml($array);
    }

    //验证回调签名
    private function verifySignature($msg_encrypt)
    {
        if (!(isset($_GET['msg_signature']) && isset($_GET['timestamp']) && isset($_GET['nonce']))) {
			return false;
		}

        $signature = $this->getSignature($_GET['timestamp'], $_GET['nonce'], $msg_encrypt);

		return $signature === $_GET['msg_signature'];
    }

    //生成SHA1签名
    private function getSignature($timestamp, $nonce, $encrypt_msg)
    {
        $signatureArray = array($encrypt_msg, $this->token, $timestamp, $nonce);
		sort($signatureArray, SORT_STRING);
        return sha1(implode($signatureArray));
    }

    //转为XML数据
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

    //解析XML数据
    private function xml2array($xml)
    {
        if (!$xml) {
            return false;
        }
		LIBXML_VERSION < 20900 && libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA), JSON_UNESCAPED_UNICODE), true);
    }
}