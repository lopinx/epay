<?php

namespace lib\ProfitSharing;

use Exception;

class Alipay implements IProfitSharing
{

    static $paytype = 'alipay';

    private $channel;
    private $service;

    function __construct($channel){
		$this->channel = $channel;
        $alipay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $this->service = new \Alipay\AlipaySettleService($alipay_config);
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $account, $name, $money){
        $type = self::get_alipay_account_type($account);

        try{
            $result = $this->service->order_settle($api_trade_no, $type, $account, $money);
            return ['code'=>1, 'msg'=>'分账成功', 'settle_no'=>$result['settle_no']];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        try{
            $result = $this->service->order_settle_query($settle_no);
            $receiver = $result['royalty_detail_list'][0];
            if($receiver['state'] == 'SUCCESS'){
                return ['code'=>0, 'status'=>1];
            }elseif($receiver['state'] == 'FAIL'){
                return ['code'=>0, 'status'=>2, 'reason'=>'['.$receiver['error_code'].']'.$receiver['error_desc']];
            }else{
                return ['code'=>0, 'status'=>0];
            }
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //解冻剩余资金
    public function unfreeeze($trade_no, $api_trade_no){
        try{
            $this->service->order_settle_unfreeze($api_trade_no);
            return ['code'=>0, 'msg'=>'解冻剩余资金成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $account, $money){
        $type = self::get_alipay_account_type($account);

        try{
            $this->service->order_settle_refund($api_trade_no, $type, $account, $money);
            return ['code'=>0, 'msg'=>'退分账成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        $type = self::get_alipay_account_type($account);

        try{
            $this->service->relation_bind($type, $account, $name);
            return ['code'=>0, 'msg'=>'添加分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //删除分账接收方
    public function deleteReceiver($account){
        $type = self::get_alipay_account_type($account);

        try{
            $this->service->relation_unbind($type, $account);
            return ['code'=>0, 'msg'=>'删除分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    private static function get_alipay_account_type($account){
        if(is_numeric($account) && substr($account,0,4)=='2088' && strlen($account)==16)$type = 'userId';
	    else $type = 'loginName';
        return $type;
    }
}