<?php
namespace lib;

use Exception;

class Transfer
{
    //通用转账
    //type alipay:支付宝,wxpay:微信,qqpay:QQ钱包,bank:银行卡
    public static function submit($type, $channel, $out_biz_no, $payee_account, $payee_real_name, $money, $desc = null){
        global $conf;

        $bizParam = [
            'type' => $type,
            'out_biz_no' => $out_biz_no,
            'payee_account' => $payee_account,
            'payee_real_name' => $payee_real_name,
            'money' => $money,
            'transfer_name' => $desc?$desc:$conf['transfer_name'],
            'transfer_desc' => $desc?$desc:$conf['transfer_desc'],
        ];
        return \lib\Plugin::call('transfer', $channel, $bizParam);
    }

    //转账状态刷新
    public static function status($out_biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $out_biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];
        
        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];

        $result = self::query($order['type'], $channel, $out_biz_no, $order['pay_order_no']);
        if($result['code'] == 0){
            if($result['status'] == 2){
                if($order['status'] == 0){
                    $resCount = $DB->update('transfer', ['status'=>2, 'result'=>$result['errmsg']], ['biz_no' => $out_biz_no]);
                    if($order['uid'] > 0 && $resCount > 0){
                        changeUserMoney($order['uid'], $order['costmoney'], true, '代付退回');
                    }
                }
                $result['msg'] = '转账失败：'.($result['errmsg']?$result['errmsg']:'原因未知');
            }elseif($result['status'] == 1){
                if($order['status'] == 0){
                    $DB->update('transfer', ['status'=>1], ['biz_no' => $out_biz_no]);
                }
                $result['msg'] = '转账成功！';
            }else{
                $result['msg'] = '转账处理中，请稍后查询结果。';
            }
        }
        return $result;
    }

    //转账查询
    //status 0:处理中 1:成功 2:失败
    public static function query($type, $channel, $out_biz_no, $pay_order_no){
        $bizParam = [
            'type' => $type,
            'out_biz_no' => $out_biz_no,
            'orderid' => $pay_order_no
        ];
        return \lib\Plugin::call('transfer_query', $channel, $bizParam);
    }

    //账户余额查询
    public static function balance($type, $channel, $user_id = null){
        $bizParam = [
            'type' => $type,
            'user_id' => $user_id
        ];
        return \lib\Plugin::call('balance_query', $channel, $bizParam);
    }

    //转账凭证查询
    public static function proof($out_biz_no){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $out_biz_no]);
        if(!$order) return ['code'=>-1, 'msg'=>'付款记录不存在'];
        
        $channelinfo = null;
        if($order['uid'] > 0){
            $channelinfo = $DB->findColumn('user', 'channelinfo', ['uid'=>$order['uid']]);
        }
        $channel = \lib\Channel::get($order['channel'], $channelinfo);
        if(!$channel) return ['code'=>-1, 'msg'=>'支付通道不存在'];

        $bizParam = [
            'type' => $order['type'],
            'out_biz_no' => $out_biz_no,
            'orderid' => $order['pay_order_no']
        ];
        return \lib\Plugin::call('transfer_proof', $channel, $bizParam);
    }

    //转账回调处理
    public static function processNotify($out_biz_no, $status, $errmsg = null){
        global $DB;
        $order = $DB->find('transfer', '*', ['biz_no' => $out_biz_no]);
        if(!$order) return;
        if($status == 2){
            if($order['status'] == 0){
                $data = ['status'=>2];
                if($errmsg) $data['result'] = $errmsg;
                $resCount = $DB->update('transfer', $data, ['biz_no' => $out_biz_no]);
                if($order['uid'] > 0 && $resCount > 0){
                    changeUserMoney($order['uid'], $order['costmoney'], true, '代付退回');
                }
            }
        }elseif($status == 1){
            if($order['status'] == 0){
                $DB->update('transfer', ['status'=>1], ['biz_no' => $out_biz_no]);
            }
        }
    }
}