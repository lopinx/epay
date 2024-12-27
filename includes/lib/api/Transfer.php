<?php
namespace lib\api;

use Exception;

class Transfer
{
    public static function submit(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        if($conf['settle_type']==1){
            $today=date("Y-m-d").' 00:00:00';
            $order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$pid} and tid<>2 and status=1 and endtime>='$today'");
            if(!$order_today) $order_today = 0;
            $enable_money=round($userrow['money']-$order_today,2);
            if($enable_money<0)$enable_money=0;
        }else{
            $enable_money=$userrow['money'];
        }
        if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];

        $type = $queryArr['type'];
        $out_biz_no = trim($queryArr['out_biz_no']);
        $account = htmlspecialchars(trim($queryArr['account']));
        $name = htmlspecialchars(trim($queryArr['name']));
        $money = trim($queryArr['money']);
        $desc = htmlspecialchars(trim($queryArr['remark']));

        if(empty($type))throw new Exception('代付方式(type)不能为空');
        if(empty($out_biz_no)) $out_biz_no = date("YmdHis").rand(11111,99999);
        if(empty($account))throw new Exception('收款人账号(account)不能为空');
        if(empty($name))throw new Exception('收款人姓名(name)不能为空');
        if(empty($money))throw new Exception('转账金额(money)不能为空');
        if(strlen($out_biz_no)!=19 || !is_numeric($out_biz_no))throw new Exception('交易号输入不规范');
        if($desc && mb_strlen($desc)>32)throw new Exception('转账备注最多32个字');
        if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)throw new Exception('转账金额输入不规范');

        $need_money = round($money + $money*$conf['transfer_rate']/100,2);
        if($need_money>$enable_money)throw new Exception('需支付金额大于可转账余额');
        if($conf['transfer_minmoney']>0 && $money<$conf['transfer_minmoney'])throw new Exception('单笔最小代付金额限制为'.$conf['transfer_minmoney'].'元');
        if($conf['transfer_maxmoney']>0 && $money>$conf['transfer_maxmoney'])throw new Exception('单笔最大代付金额限制为'.$conf['transfer_maxmoney'].'元');
        if($userrow['settle']==0)throw new Exception('您的商户出现异常，无法使用代付功能');
        if($conf['transfer_maxlimit']>0){
            $a_count = $DB->getColumn('SELECT count(*) FROM pre_transfer WHERE uid=:uid AND type=:type AND account=:account AND paytime>=:paytime', [':uid'=>$pid, ':type'=>$type, ':account'=>$account, ':paytime'=>date('Y-m-d').' 00:00:00']);
            if($a_count >= $conf['transfer_maxlimit']){
                throw new Exception('您今天向该账号的转账次数已达到上限');
            }
        }

        if($type=='alipay'){
            $channelid = $conf['transfer_alipay'];
        }elseif($type=='wxpay'){
            $channelid = $conf['transfer_wxpay'];
        }elseif($type=='qqpay'){
            if (!is_numeric($account) || strlen($account)<6 || strlen($account)>10)throw new Exception('QQ号码格式错误');
            $channelid = $conf['transfer_qqpay'];
        }elseif($type=='bank'){
            $channelid = $conf['transfer_bank'];
        }else{
            throw new Exception('type参数错误');
        }

        if(!$channelid) throw new Exception('未开启此转账方式');
        $channel = \lib\Channel::get($channelid, $userrow['channelinfo']);
	    if(!$channel)throw new Exception('当前支付通道信息不存在',4);

        $result = \lib\Transfer::submit($type, $channel, $out_biz_no, $account, $name, $money, $desc);
        $result['out_biz_no'] = $out_biz_no;

        if($result['code']==0){
            $data = ['biz_no'=>$out_biz_no, 'uid'=>$pid, 'type'=>$type, 'channel'=>$channelid, 'account'=>$account, 'username'=>$name, 'money'=>$money, 'costmoney'=>$need_money, 'paytime'=>'NOW()', 'pay_order_no'=>$result['orderid'], 'status'=>$result['status'], 'desc'=>$desc];
            if($DB->insert('transfer', $data)!==false){
                changeUserMoney($pid, $need_money, false, '代付');
            }
            if($result['status'] == 1){
                $result['msg']='转账成功！转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
            }else{
                $result['msg']='提交成功！转账处理中。转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
            }
            $result['cost_money'] = $need_money;
        }
        return $result;
    }

    public static function query(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        $out_biz_no = trim($queryArr['out_biz_no']);
        if(empty($out_biz_no)) throw new Exception('转账交易号(out_biz_no)不能为空');

        $order = $DB->find('transfer', '*', ['biz_no'=>$out_biz_no, 'uid'=>$pid]);
        if(!$order) throw new Exception('当前转账订单不存在');

        if($order['status'] == 1){
            $result = ['code'=>0, 'msg'=>'转账成功！', 'status'=>1, 'amount'=>$order['money'], 'cost_money'=>$order['costmoney'], 'paydate'=>$order['paytime'], 'remark'=>$order['desc']];
        }elseif($order['status'] == 2){
            $errmsg = ($order['result']?$order['result']:'原因未知');
            $result = ['code'=>0, 'msg'=>'转账失败：'.($order['result']?$order['result']:'原因未知'), 'status'=>2, 'amount'=>$order['money'], 'cost_money'=>$order['money'], 'paydate'=>$order['paytime'], 'remark'=>$order['desc'], 'errmsg'=>$errmsg];
        }else{
            $result = \lib\Transfer::status($out_biz_no);
            $result['remark'] = $order['desc'];
            $result['cost_money'] = $order['costmoney'];
        }

        return $result;
    }

    public static function proof(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        $out_biz_no = trim($queryArr['out_biz_no']);
        if(empty($out_biz_no)) throw new Exception('转账交易号(out_biz_no)不能为空');

        $order = $DB->find('transfer', '*', ['biz_no'=>$out_biz_no, 'uid'=>$pid]);
        if(!$order) throw new Exception('当前转账订单不存在');

        $result = \lib\Transfer::proof($out_biz_no);

        return $result;
    }

    public static function balance(){
        global $conf, $DB, $userrow, $queryArr;

        $pid=intval($queryArr['pid']);
        $groupconfig = getGroupConfig($userrow['gid']);
        $conf = array_merge($conf, $groupconfig);
        if(!$conf['user_transfer']) throw new Exception('管理员未开启代付功能');
        if($userrow['transfer'] == 0) throw new Exception('商户未开启代付API接口');

        if($conf['settle_type']==1){
            $today=date("Y-m-d").' 00:00:00';
            $order_today=$DB->getColumn("SELECT SUM(realmoney) from pre_order where uid={$pid} and tid<>2 and status=1 and endtime>='$today'");
            if(!$order_today) $order_today = 0;
            $enable_money=round($userrow['money']-$order_today,2);
            if($enable_money<0)$enable_money=0;
        }else{
            $enable_money=$userrow['money'];
        }
        if(!$conf['transfer_rate'])$conf['transfer_rate'] = $conf['settle_rate'];

        $result = ['code'=>0, 'available_money'=>strval($enable_money), 'transfer_rate'=>$conf['transfer_rate']];

        return $result;
    }
}