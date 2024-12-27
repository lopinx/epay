<?php

namespace Alipay;

/**
 * 支付宝分账服务类
 * @see https://opendocs.alipay.com/open/repo-0038ln
 */
class AlipaySettleService extends AlipayService
{
    /**
     * @param $config 支付宝配置信息
     */
    public function __construct($config)
    {
        parent::__construct($config);
    }

    /**
     * 分账关系绑定
     * @param $type 分账接收方方类型(userId,loginName,openId)
     * @param $account 分账接收方账号
     * @param $name 分账接收方真实姓名
     * @return bool
     */
    public function relation_bind($type, $account, $name) {
        $apiName = 'alipay.trade.royalty.relation.bind';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $receiver = [
            'type' => $type,
            'account' => $account,
        ];
        if(!empty($name)) $receiver['name'] = $name;
        $bizContent = array(
            'receiver_list' => [
                $receiver
            ],
            'out_request_no' => $out_request_no,
        );
        $this->aopExecute($apiName, $bizContent);
        return true;
    }

    /**
     * 分账关系解绑
     * @param $type 分账接收方方类型(userId,loginName,openId)
     * @param $account 分账接收方账号
     * @return bool
     */
    public function relation_unbind($type, $account) {
        $apiName = 'alipay.trade.royalty.relation.unbind';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $receiver = [
            'type' => $type,
            'account' => $account,
        ];
        $bizContent = array(
            'receiver_list' => [
                $receiver
            ],
            'out_request_no' => $out_request_no,
        );
        $this->aopExecute($apiName, $bizContent);
        return true;
    }

    /**
     * 分账关系查询
     * @param $page_num 页码
     * @param $page_size 每页条数
     * @return array
     */
    public function relation_batchquery($page_num = 1, $page_size = 20) {
        $apiName = 'alipay.trade.royalty.relation.batchquery';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $bizContent = array(
            'page_num' => $page_num,
            'page_size' => $page_size,
            'out_request_no' => $out_request_no,
        );
        $result = $this->aopExecute($apiName, $bizContent);
        return $result['receiver_list'];
    }

    /**
     * 分账请求
     * @param $trade_no 支付宝订单号
     * @param $type 收入方账户类型(userId,cardAliasNo,loginName,openId)
     * @param $account 收入方账户
     * @param $money 分账的金额
     * @return mixed {"trade_no":"支付宝交易号","settle_no":"支付宝分账单号"}
     */
    public function order_settle($trade_no, $type, $account, $money) {
        $apiName = 'alipay.trade.order.settle';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $receiver = [
            'trans_in_type' => $type,
            'trans_in' => $account,
            'amount' => $money
        ];
        $bizContent = array(
            'out_request_no' => $out_request_no,
            'trade_no' => $trade_no,
            'royalty_parameters' => [
                $receiver
            ],
            'extend_params' => [
                'royalty_finish' => 'true'
            ]
        );
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 解冻剩余资金
     * @param $trade_no 支付宝订单号
     * @return mixed {"trade_no":"支付宝交易号","settle_no":"支付宝分账单号"}
     */
    public function order_settle_unfreeze($trade_no) {
        $apiName = 'alipay.trade.order.settle';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $bizContent = array(
            'out_request_no' => $out_request_no,
            'trade_no' => $trade_no,
            'extend_params' => [
                'royalty_finish' => 'true'
            ],
        );
        return $this->aopExecute($apiName, $bizContent);
    }
    
    /**
     * 分账查询
     * @param $settle_no 支付宝分账单号
     * @return mixed {"out_request_no":"商户分账请求单号","operation_dt":"分账受理时间","royalty_detail_list":[{"operation_type":"transfer","execute_dt":"分账执行时间","trans_out":"2088111111111111","trans_out_type":"userId","trans_in":"2088111111112222","trans_in_type":"userId","amount":10,"state":"FAIL","error_code":"TXN_RESULT_ACCOUNT_BALANCE_NOT_ENOUGH","error_desc":"分账余额不足"}]}
     */
    public function order_settle_query($settle_no) {
        $apiName = 'alipay.trade.order.settle.query';
        $bizContent = array(
            'settle_no' => $settle_no,
        );
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 分账比例查询
     * @return mixed {"user_id":"2088XXXX1234","max_ratio":80}
     */
    public function rate_query() {
        $apiName = 'alipay.trade.royalty.rate.query';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $bizContent = array(
            'out_request_no' => $out_request_no,
        );
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 退分账
     * @param $trade_no 支付宝交易号
     * @param $type 支出方账户类型(userId,loginName)
     * @param $account 支出方账户
     * @param $money 分账的金额
     */
    public function order_settle_refund($trade_no, $type, $account, $money) {
        $apiName = 'alipay.trade.refund';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $receiver = [
            'royalty_type' => 'transfer',
            'trans_out_type' => $type,
            'trans_out' => $account,
            'amount' => $money
        ];
        $bizContent = array(
            'trade_no' => $trade_no,
            'refund_amount' => '0',
            'out_request_no' => $out_request_no,
            'refund_royalty_parameters' => [
                $receiver
            ],
        );
        $this->aopExecute($apiName, $bizContent);
        return true;
    }
}