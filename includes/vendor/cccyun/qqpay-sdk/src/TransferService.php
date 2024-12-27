<?php

namespace QQPay;

/**
 * QQ钱包转账服务类
 * @see https://mp.qpay.tenpay.cn/buss/wiki/206/1214
 */
class TransferService extends BaseService
{
    public function __construct($config)
    {
        parent::__construct($config);

        $this->publicParams = [
            'mch_id'     => $this->mchId,
            'nonce_str'  => $this->getNonceStr(),
        ];
    }

    /**
     * 企业付款到余额
     * @param $out_trade_no 商户订单号
     * @param $uin 收款QQ号码
     * @param $name 用户姓名(填写后校验)
     * @param $amount 金额
     * @param $memo 备注
     * @return mixed {"out_trade_no":"商户订单号","transaction_id":"QQ钱包订单号"}
     */
    public function transfer($out_trade_no, $uin, $name, $amount, $memo)
    {
        $url = 'https://api.qpay.qq.com/cgi-bin/epay/qpay_epay_b2c.cgi';
        $params = [
            'input_charset' => 'UTF-8',
            'out_trade_no' => $out_trade_no,
            'uin' => $uin,
            'fee_type' => 'CNY',
            'total_fee' => $amount,
            'memo' => $memo,
            'check_real_name' => '0'
        ];
        if (!empty($name)) {
            $params['check_name'] = 'FORCE_CHECK';
            $params['re_user_name'] = $name;
        }
        $params += [
            'op_user_id' => $this->opUserId,
            'op_user_passwd' => md5($this->opUserPwd),
            'spbill_create_ip' => $_SERVER['SERVER_ADDR']
        ];
        return $this->execute($url, $params, true);
    }

    /**
     * 查询企业付款
     * @param $out_trade_no 商户订单号
     * @return mixed {"out_trade_no":"商户订单号","detail_id":"微信付款单号","status":"转账状态","reason":"失败原因","openid":"用户openid","transfer_name":"用户姓名","payment_amount":"付款金额","transfer_time":"转账时间","payment_time":"付款成功时间","desc":"付款备注"}
     */
    public function transferQuery($out_trade_no)
    {
        $url = 'https://qpay.qq.com/cgi-bin/pay/qpay_epay_query.cgi';
        $params = [
            'out_trade_no' => $out_trade_no
        ];
        return $this->execute($url, $params);
    }

}
