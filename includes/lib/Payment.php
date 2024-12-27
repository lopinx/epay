<?php
namespace lib;

use Exception;

class Payment {

    //生成待签名字符串
    static private function getSignContent($data){
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            if(is_array($v) || isEmpty($v) || $k == 'sign' || $k == 'sign_type') continue;
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = substr($signStr, 0, -1);
        return $signStr;
    }

    //生成签名
    static public function makeSign($data, $md5key) {
        $sign_type = $data['sign_type'] ? $data['sign_type'] : 'MD5';
        $signStr = self::getSignContent($data);
        if($sign_type == 'RSA'){
            global $conf;
            $private_key = base64ToPem($conf['private_key'], 'PRIVATE KEY');
            $pkey = openssl_pkey_get_private($private_key);
            if(!$pkey) return false;
            openssl_sign($signStr, $sign, $pkey, OPENSSL_ALGO_SHA256);
            return base64_encode($sign);
        }else{
            $sign = md5($signStr . $md5key);
            return $sign;	
        }
    }

    //验证签名
    static public function verifySign($data, $md5key, $publicKey) {
        if(!isset($data['sign'])) throw new Exception('缺少签名参数');
        $sign_type = $data['sign_type'] ? $data['sign_type'] : 'MD5';
        if($sign_type == 'RSA'){
            $public_key = base64ToPem($publicKey, 'PUBLIC KEY');
            $pkey = openssl_pkey_get_public($public_key);
            if(!$pkey) throw new Exception('签名校验失败，商户公钥错误');
            $signStr = self::getSignContent($data);
            $result = openssl_verify($signStr, base64_decode($data['sign']), $pkey, OPENSSL_ALGO_SHA256);
            return $result === 1;
        }else{
            $sign = self::makeSign($data, $md5key);
            return $sign === $data['sign'];
        }
    }

    // 页面支付返回信息
    static public function echoDefault($result){
        global $cdnpublic,$order,$conf,$sitename,$ordername;
        $type = $result['type'];
        if(!$type) return false;
        switch($type){
            case 'jump': //跳转
                $html_text = '<script>window.location.replace(\''.$result['url'].'\');</script>';
                if(isset($result['submit']) && $result['submit']){
                    submitTemplate($html_text);
                }else{
                    echo $html_text;
                }
                break;
            case 'html': //显示html
                $html_text = $result['data'];
                if(isset($result['submit']) && $result['submit'] && substr($html_text, 0, 6) == '<form '){
                    submitTemplate($html_text);
                }else{
                    echo $html_text;
                }
                break;
            case 'json': //显示JSON
                echo json_encode($result['data']);
                break;
            case 'page': //显示指定页面
                include_once SYSTEM_ROOT.'txprotect.php';
                if(isset($result['data'])) extract($result['data']);
                if($conf['pageordername']==1)$order['name']=$ordername?$ordername:'onlinepay';
                include PAYPAGE_ROOT.$result['page'].'.php';
                break;
            case 'qrcode': //扫码页面
            case 'scheme': //跳转urlscheme页面
                if($result['page'] == 'wxpay_mini') $result['page'] = 'wxpay_h5';
                include_once SYSTEM_ROOT.'txprotect.php';
                $code_url = $result['url'];
                if($conf['pageordername']==1)$order['name']=$ordername?$ordername:'onlinepay';
                if($conf['wework_payopen'] == 1 && ($result['page'] == 'wxpay_wap' && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger')===false || $result['page'] == 'wxpay_qrcode' && checkmobile())){
                    $code_url_wxkf = self::getWxkfPayUrl($code_url);
                    if($code_url_wxkf){
                        $code_url = $code_url_wxkf;
                        include PAYPAGE_ROOT.'wxpay_h5.php';
                        break;
                    }
                }
                include PAYPAGE_ROOT.$result['page'].'.php';
                break;
            case 'return': //同步回调
                returnTemplate($result['url']);
                break;
            case 'error': //错误提示
                sysmsg($result['msg']);
                break;
            default:break;
        }
    }

    // API支付返回信息
    static public function echoJson($result){
        global $order,$siteurl;
        if(!$result) return false;
        $type = $result['type'];
        if(!$type) return false;
        if(defined('API_INIT')){
            $json = ['code'=>0, 'trade_no'=>TRADE_NO];
            switch($type){
                case 'jump': //跳转URL
                    $json['pay_type'] = 'jump';
                    $json['pay_info'] = $result['url'];
                    break;
                case 'html': //显示html跳转
                    $json['pay_type'] = 'html';
                    $json['pay_info'] = $result['data'];
                    break;
                case 'qrcode': //扫码支付
                    $json['pay_type'] = 'qrcode';
                    $json['pay_info'] = $result['url'];
                    break;
                case 'scheme': //小程序H5跳转
                    $json['pay_type'] = 'urlscheme';
                    $json['pay_info'] = $result['url'];
                    break;
                case 'jsapi': //JSAPI支付
                    $json['pay_type'] = 'jsapi';
                    $json['pay_info'] = $result['data'];
                    break;
                case 'app': //APP支付
                    $json['pay_type'] = 'app';
                    $json['pay_info'] = $result['data'];
                    break;
                case 'scan': //付款码支付
                    $json['pay_type'] = 'scan';
                    $json['pay_info'] = $result['data'];
                    break;
                case 'error':
                    $json['code'] = -2;
                    $json['msg'] = $result['msg'];
                    break;
                default:
                    $json['pay_type'] = 'jump';
                    $json['pay_info'] = $siteurl.'pay/submit/'.TRADE_NO.'/';
                    break;
            }
            if($json['code'] == 0){
                if(is_array($json['pay_info'])) $json['pay_info'] = json_encode($json['pay_info']);
                $json['timestamp'] = time().'';
                $json['sign_type'] = 'RSA';
                $json['sign'] = self::makeSign($json, null);
            }
            exit(json_encode($json));
        }else{
            $json = ['code'=>1, 'trade_no'=>TRADE_NO];
            switch($type){
                case 'jump': //跳转URL
                    $json['payurl'] = $result['url'];
                    break;
                case 'html': //显示html跳转
                    $json['html'] = $result['data'];
                    break;
                case 'qrcode': //扫码支付
                    $json['qrcode'] = $result['url'];
                    break;
                case 'scheme': //小程序H5跳转
                    $json['urlscheme'] = $result['url'];
                    break;
                case 'error':
                    $json['code'] = -2;
                    $json['msg'] = $result['msg'];
                    break;
                default:
                    $json['payurl'] = $siteurl.'pay/submit/'.TRADE_NO.'/';
                    break;
            }
            exit(json_encode($json));
        }
    }

    // 订单回调处理
    static public function processOrder($isnotify, $order, $api_trade_no, $buyer){
        global $DB,$conf,$siteurl;
        if($order['status']==0 || $order['status']==4){
            if($DB->exec("UPDATE `pre_order` SET `status`=1 WHERE `trade_no`='".$order['trade_no']."'")){

                $data = ['endtime'=>'NOW()', 'date'=>'CURDATE()'];
                if(!empty($api_trade_no)) $data['api_trade_no'] = $api_trade_no;
                if(!empty($buyer)) $data['buyer'] = $buyer;
                if($order['settle']>0) $data['settle'] = $order['settle'];
                $DB->update('order', $data, ['trade_no'=>$order['trade_no']]);
                $order['api_trade_no'] = $api_trade_no;

                processOrder($order, $isnotify);
            }
        }elseif(empty($order['api_trade_no']) && !empty($api_trade_no)){
            $data = ['api_trade_no'=>$api_trade_no];
            if(!empty($buyer)) $data['buyer'] = $buyer;
            $DB->update('order', $data, ['trade_no'=>$order['trade_no']]);
        }elseif(empty($order['buyer']) && !empty($buyer)){
            $data['buyer'] = $buyer;
            $DB->update('order', $data, ['trade_no'=>$order['trade_no']]);
        }
        if($isnotify && $order['settle']>0){
            $DB->update('order', ['settle'=>$order['settle']], ['trade_no'=>$order['trade_no']]);
        }
        if(!$isnotify){
            include_once SYSTEM_ROOT.'txprotect.php';
            if($order['status'] == 2){
                $jumpurl = '/payerr.html';
                returnTemplate($jumpurl);
                return;
            }
            // 支付完成5分钟后禁止跳转回网站
            if(!empty($order['endtime']) && time() - strtotime($order['endtime']) > 300){
                $jumpurl = '/payok.html';
            }else{
                $url=creat_callback($order);
                $jumpurl = $url['return'];
            }
            returnTemplate($jumpurl);
        }
    }

    // 更新订单信息
    static public function updateOrder($trade_no, $api_trade_no, $buyer = null, $status = null){
        global $DB;
        $data = ['api_trade_no'=>$api_trade_no];
        if(!empty($buyer)) $data['buyer'] = $buyer;
        if($status) $data['status'] = $status;
        $DB->update('order', $data, ['trade_no'=>$trade_no]);
    }

    // 更新订单扩展信息
    static public function updateOrderExt($trade_no, $data){
        global $DB;
        $DB->update('order', ['ext'=>serialize($data)], ['trade_no'=>$trade_no]);
    }

    // 更新合单状态
    static public function updateOrderCombine($trade_no){
        global $DB;
        $DB->update('order', ['combine'=>1], ['trade_no'=>$trade_no]);
    }

    // 更新订单分账接收人
    static public function updateOrderProfits($order, $plugin){
        global $DB;
        $support_plugins = \lib\ProfitSharing\CommUtil::$plugins;
        if(in_array($plugin, $support_plugins)){
            $psreceiver = $DB->getRow("SELECT * FROM `pre_psreceiver` WHERE `channel`='{$order['channel']}' AND `uid`='{$order['uid']}' AND `status`=1");
            if(!$psreceiver) $psreceiver = $DB->getRow("SELECT * FROM `pre_psreceiver` WHERE `channel`='{$order['channel']}' AND `uid` IS NULL AND `status`=1");
            if($psreceiver){
                if(!$psreceiver['minmoney'] || $order['realmoney']>=$psreceiver['minmoney']){
                    $DB->update('order', ['profits'=>$psreceiver['id']], ['trade_no'=>$order['trade_no']]);
                    return intval($psreceiver['id']);
                }
            }
        }
        return 0;
    }

    // 更新订单分账接收人2
    static public function updateOrderProfits2($order, $plugin){
        return;
        global $DB;
        $support_plugins = \lib\ProfitSharing\CommUtil::$plugins2;
        if(in_array($plugin, $support_plugins)){
            $psreceiver = $DB->getRow("SELECT * FROM `pre_psreceiver2` WHERE `channel`='{$order['channel']}' AND `uid`='{$order['uid']}' AND `status`=1");
            if(!$psreceiver) $psreceiver = $DB->getRow("SELECT * FROM `pre_psreceiver2` WHERE `channel`='{$order['channel']}' AND `uid` IS NULL AND `status`=1");
            if($psreceiver){
                if(!$psreceiver['minmoney'] || $order['realmoney']>=$psreceiver['minmoney']){
                    $DB->update('order', ['profits2'=>$psreceiver['id']], ['trade_no'=>$order['trade_no']]);
                    return intval($psreceiver['id']);
                }
            }
        }
        return 0;
    }

    //支付宝直付通确认结算
    public static function alipaydSettle($trade_no, $api_trade_no, $realmoney, $combine = 0, $ext = null){
        global $channel;
        $alipay_config = require(PLUGIN_ROOT.'alipayd/inc/config.php');
        $alipaySevice = new \Alipay\AlipayTradeService($alipay_config);
        if($combine == 1){
            if(!$ext) throw new Exception('子单支付结果数据不存在');
            $sub_orders = unserialize($ext);
            $failnum = 0;
            $errmsg = '';
            foreach($sub_orders as &$sub_order){
                if($sub_order['settle'] == 0){
                    try{
                        $alipaySevice->settle_confirm($sub_order['trade_no'], $sub_order['total_amount']);
                        $sub_order['settle'] == 1;
                    }catch(Exception $e){
                        $failnum++;
                        $errmsg .= $e->getMessage().',';
                    }
                }
            }
            \lib\Payment::updateOrderExt($trade_no, $sub_orders);
            if($failnum > 0) throw new Exception('部分子单结算失败，失败数量：'.$failnum.'，失败原因：'.$errmsg);
            return true;
        }
        return $alipaySevice->settle_confirm($api_trade_no, $realmoney);
    }

    //微信收付通确认结算
    public static function wxpaynpSettle($trade_no, $api_trade_no, $profits){
        global $channel;
        $wechatpay_config = require(PLUGIN_ROOT.'/wxpaynp/inc/config.php');
        if($wechatpay_config['ecommerce']){
            if(!$profits){
                $client = new \WeChatPay\V3\ProfitsharingService($wechatpay_config);
                return $client->unfreeze($trade_no, $api_trade_no);
            }else{
                throw new Exception('当前订单需要分账，请进入分账订单页面确认分账');
            }
        }else{
            throw new Exception('非电商收付通订单');
        }
    }

    //支付宝预授权资金支付
    public static function alipayPreAuthPay($trade_no){
        global $channel, $order, $conf;
        $alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $alipaySevice = new \Alipay\AlipayTradeService($alipay_config);
        $bizContent = [
            'out_order_no' => $trade_no,
            'out_request_no' => $trade_no,
        ];
        $result = $alipaySevice->preAuthQuery($bizContent);
        //print_r($result);exit;
        if(!isset($result['auth_no'])) throw new Exception('预授权订单查询失败');
        if($result['rest_amount'] == 0) throw new Exception('剩余冻结金额为0');
        if($result['order_status'] == 'AUTHORIZED'){
            $auth_no = $result['auth_no'];
            $ordername = !empty($conf['ordername'])?ordername_replace($conf['ordername'],$order['name'],$order['uid'],$trade_no):$order['name'];
            $bizContent = [
                'out_trade_no' => $trade_no,
                'total_amount' => $result['rest_amount'],
                'subject' => $ordername,
                'product_code' => 'PREAUTH_PAY',
                'auth_no' => $auth_no,
                'auth_confirm_mode' => 'COMPLETE'
            ];
            return $alipaySevice->scanPay($bizContent);
        }else{
            throw new Exception('该笔订单非已授权状态，无需支付');
        }
    }

    //支付宝预授权资金解冻
    public static function alipayUnfreeze($trade_no){
        global $channel;
        $alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $alipaySevice = new \Alipay\AlipayTradeService($alipay_config);
        $bizContent = [
            'out_order_no' => $trade_no,
            'out_request_no' => $trade_no,
        ];
        $result = $alipaySevice->preAuthQuery($bizContent);
        //print_r($result);exit;
        if(!isset($result['auth_no'])) throw new Exception('预授权订单查询失败');
        if($result['rest_amount'] == 0) throw new Exception('剩余冻结金额为0');
        if($result['order_status'] == 'AUTHORIZED'){
            $auth_no = $result['auth_no'];
            $bizContent = [
                'auth_no' => $auth_no,
                'out_request_no' => date("YmdHis").rand(11111,99999),
                'amount' => $result['rest_amount'],
                'remark' => '解冻资金'
            ];
            return $alipaySevice->preAuthUnfreeze($bizContent);
        }else{
            throw new Exception('该笔订单非已授权状态，无需解冻');
        }
    }

    //支付宝红包转账
    public static function alipayRedPacketTransfer($payee_user_id, $money, $order_id){
        global $channel, $conf;
        $out_biz_no = date("YmdHis").rand(11111,99999);
        $alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $alipaySevice = new \Alipay\AlipayTransferService($alipay_config);
        $alipaySevice->redPacketTansfer($out_biz_no, $money, $payee_user_id, $conf['sitename'], $order_id);
    }

    //支付宝红包资金退回
    public static function alipayRedPacketRefund($trade_no, $money){
        global $channel;
        $out_biz_no = date("YmdHis").rand(11111,99999);
        $alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $alipaySevice = new \Alipay\AlipayTransferService($alipay_config);
        $alipaySevice->redPacketRefund($out_biz_no, $trade_no, $money);
    }

    //加锁设置订单扩展数据
    public static function lockPayData($trade_no, $func){
        global $DB;
        $DB->beginTransaction();
        $data = $DB->getColumn("SELECT ext FROM pre_order WHERE trade_no=:trade_no FOR UPDATE", [':trade_no'=>$trade_no]);
        if($data) {
            $DB->rollBack();
            return unserialize($data);
        }
        try{
            $data = $func();
        }catch(\Exception $e){
            $DB->rollBack();
            throw $e;
        }
        if($data){
            $DB->update('order', ['ext'=>serialize($data)], ['trade_no' => $trade_no]);
        }
        $DB->commit();
        return $data;
    }

    //获取微信客服跳转链接
    public static function getWxkfPayUrl($pay_url){
        global $order, $DB, $conf;

        $cookiesid = $_COOKIE['mysid'];
        if(!$cookiesid||!preg_match('/^[0-9a-z]{32}$/i', $cookiesid)){
            $cookiesid = getSid();
            setcookie("mysid", $cookiesid, time() + 2592000, '/');
        }

        if($conf['wework_paykfid'] > 0){
            $wxkfaccount = $DB->getRow("SELECT * FROM pre_wxkfaccount WHERE id=:id", [':id'=>$conf['wework_paykfid']]);
        }elseif($conf['wework_paymsgmode'] == 1){
            $usekflist = $DB->getAll("SELECT DISTINCT aid FROM pre_wxkflog WHERE `sid`=:sid AND addtime>=:addtime AND status=1", [':sid'=>$cookiesid, ':addtime'=>date("Y-m-d H:i:s", strtotime('-48 hours'))]);
            $usekfids = [0];
            foreach($usekflist as $usekf){
                $usekfids[] = intval($usekf['aid']);
            }
            $wxkfaccount = $DB->getRow("SELECT A.* FROM pre_wxkfaccount A LEFT JOIN pre_wework B ON A.wid=B.id WHERE A.id NOT IN (".implode(",", $usekfids).") AND B.status=1 ORDER BY A.usetime ASC LIMIT 1");
        }else{
            $wxkfaccount = $DB->getRow("SELECT A.* FROM pre_wxkfaccount A LEFT JOIN pre_wework B ON A.wid=B.id WHERE B.status=1 ORDER BY A.usetime ASC LIMIT 1");
        }
        if(!$wxkfaccount) return false;

        $DB->insert('wxkflog', ['trade_no'=>$order['trade_no'], 'aid'=>$wxkfaccount['id'], 'sid'=>$cookiesid, 'payurl'=>$pay_url, 'addtime'=>'NOW()']);
        $scene_param = 'orderid='.$order['trade_no'].'&money='.$order['realmoney'];
        try{
            if(!empty($wxkfaccount['url'])){
                $kfurl = $wxkfaccount['url'];
                $DB->update('wxkfaccount', ['usetime'=>'NOW()'], ['id'=>$wxkfaccount['id']]);
            }else{
                $wework = new wechat\WeWorkAPI($wxkfaccount['wid']);
                $kfurl = $wework->getKFURL($wxkfaccount['openkfid'], 'pay');
                $DB->update('wxkfaccount', ['url'=>$kfurl, 'usetime'=>'NOW()'], ['id'=>$wxkfaccount['id']]);
            }
            $kfurl = 'weixin://biz/ww/kefu/' . $kfurl . '&schema=1';
            $kfurl .= '&scene_param='.urlencode($scene_param);
            return $kfurl;
        }catch(\Exception $e){
            sysmsg($e->getMessage());
        }
    }
}
