<?php

namespace lib\ProfitSharing;

use Exception;

class CommUtil
{
    public static $plugins = ['alipay','alipaysl','alipayd','wxpayn','wxpaynp','yeepay'];
    public static $plugins2 = ['adapay'];

    public static function getModel($channel){
        if($channel['plugin'] == 'alipay' || $channel['plugin'] == 'alipaysl' || $channel['plugin'] == 'alipayd'){
            return new Alipay($channel);
        }elseif($channel['plugin'] == 'wxpayn' || $channel['plugin'] == 'wxpaynp'){
            return new Wxpay($channel);
        }elseif($channel['plugin'] == 'yeepay'){
            return new Yeepay($channel);
        }
        return false;
    }

    //订单分账定时任务
    public static function task(){
        global $DB;
        $limit = 10; //每次查询分账的订单数量
        for($i=0;$i<$limit;$i++){
            $srow=$DB->getRow("SELECT A.*,B.channel,B.account,B.name,C.uid,C.subchannel FROM pre_psorder A INNER JOIN pre_psreceiver B ON B.id=A.rid LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.status=1 ORDER BY A.id ASC LIMIT 1");
            if(!$srow)break;
            self::process_item($srow);
        }
    
        $limit = 10; //每次提交分账的订单数量
        for($i=0;$i<$limit;$i++){
            $srow=$DB->getRow("SELECT A.*,B.channel,B.account,B.name,C.uid,C.subchannel FROM pre_psorder A INNER JOIN pre_psreceiver B ON B.id=A.rid LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.status=0 AND TimeStampDiff(SECOND, A.addtime, NOW())>=60 ORDER BY A.id ASC LIMIT 1");
            if(!$srow)break;
            self::process_item($srow);
        }
    }

    //处理一个订单分账任务
    public static function process_item($row){
        global $DB;
        $id = $row['id'];
        $channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
        if(!$channel) return;
        $model = self::getModel($channel);
        // status:0-待分账,1-已提交,2-成功,3-失败
        if($row['status']==0){
            if($row['money'] == 0){
                $DB->update('psorder', ['status'=>3,'result'=>'分账金额为0'], ['id'=>$id]);
                echo $row['trade_no'].' 分账金额为0<br/>';
                return;
            }
            $result = $model->submit($row['trade_no'], $row['api_trade_no'], $row['account'], $row['name'], $row['money']);
            if($result['code'] == 0){
                $DB->update('psorder', ['status'=>1,'settle_no'=>$result['settle_no']], ['id'=>$id]);
            }elseif($result['code'] == 1){
                $DB->update('psorder', ['status'=>2,'settle_no'=>$result['settle_no']], ['id'=>$id]);
            }elseif($result['code'] == -1){
                $DB->update('psorder', ['status'=>3,'result'=>$result['msg']], ['id'=>$id]);
            }
            echo $row['trade_no'].' '.$result['msg'].'<br/>';
        }elseif($row['status']==1){
            $result = $model->query($row['trade_no'], $row['api_trade_no'], $row['settle_no']);
            if($result['code']==0){
                if($result['status']==1){
                    $DB->update('psorder', ['status'=>2], ['id'=>$id]);
                    $result = '分账成功';
                }elseif($result['status']==2){
                    $DB->update('psorder', ['status'=>3,'result'=>$result['reason']], ['id'=>$id]);
                    $result = '分账失败:'.$result['reason'];
                }else{
                    $result = '正在分账';
                }
                echo $row['trade_no'].' '.$result.'<br/>';
            }else{
                echo $row['trade_no'].' 查询失败:'.$result['msg'].'<br/>';
            }
        }
    }


    public static function addReceiver_adapay($channel, $member_id, $data){
        $pay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        require(PLUGIN_ROOT.$channel['plugin'].'/inc/Build.class.php');

        $account_info = [
            'card_id' => $data['card_id'],
            'card_name' => $data['card_name'],
            'cert_id' => $data['cert_id'],
            'cert_type' => '00',
            'tel_no' => $data['tel_no'],
            'bank_acct_type' => $data['bank_type'],
        ];
    
        try{
            $adapay = \AdaPay::config($pay_config);
            $adapay->createMember($member_id);
            $result = $adapay->createSettleAccount($member_id, $account_info);
            return ['code'=>0, 'msg'=>'添加分账接收方成功', 'settleid'=>$result['id']];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    public static function editReceiver_adapay($channel, $member_id, $data, $settle_account_id){
        $pay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        require(PLUGIN_ROOT.$channel['plugin'].'/inc/Build.class.php');

        $account_info = [
            'card_id' => $data['card_id'],
            'card_name' => $data['card_name'],
            'cert_id' => $data['cert_id'],
            'cert_type' => '00',
            'tel_no' => $data['tel_no'],
            'bank_acct_type' => $data['bank_type'],
        ];
    
        try{
            $adapay = \AdaPay::config($pay_config);
            $adapay->deleteSettleAccount($member_id, $settle_account_id);
            $result = $adapay->createSettleAccount($member_id, $account_info);
            return ['code'=>0, 'msg'=>'添加分账接收方成功', 'settleid'=>$result['id']];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    public static function deleteReceiver_adapay($channel, $member_id, $settle_account_id){
        $pay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        require(PLUGIN_ROOT.$channel['plugin'].'/inc/Build.class.php');

        try{
            $adapay = \AdaPay::config($pay_config);
            $adapay->deleteSettleAccount($member_id, $settle_account_id);
            return ['code'=>0, 'msg'=>'删除分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }
}