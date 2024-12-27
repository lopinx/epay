<?php

namespace QQPay;

/**
 * QQ钱包支付服务类
 * @see https://mp.qpay.tenpay.cn/buss/wiki/38/1188
 */
class PaymentService extends BaseService
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
     * 统一下单
     * @param $params 下单参数
     * @return mixed
     */
    public function unifiedOrder($params)
    {
        $url = 'https://qpay.qq.com/cgi-bin/pay/qpay_unified_order.cgi';
        if (empty($params['out_trade_no'])) {
            throw new \InvalidArgumentException('缺少统一支付接口必填参数out_trade_no');
        }
        if (empty($params['body'])) {
            throw new \InvalidArgumentException('缺少统一支付接口必填参数body');
        }
        if (empty($params['total_fee'])) {
            throw new \InvalidArgumentException('缺少统一支付接口必填参数total_fee');
        }
        if (empty($params['trade_type'])) {
            throw new \InvalidArgumentException('缺少统一支付接口必填参数trade_type');
        }
        return $this->execute($url, $params);
    }

    /**
     * NATIVE支付
     * @param $params 下单参数
     * @return mixed {"code_url":"二维码链接","prepay_id":"预支付会话标识"}
     */
    public function nativePay($params)
    {
        $params['trade_type'] = 'NATIVE';
        return $this->unifiedOrder($params);
    }

    /**
     * JSAPI支付
     * @param $params 下单参数
     * @return mixed {"tokenId":"预支付会话标识","appInfo":"标记业务及渠道"}
     */
    public function jsapiPay($params)
    {
        $params['trade_type'] = 'JSAPI';
        $result = $this->unifiedOrder($params);
        return ['tokenId' => $result['prepay_id'], 'appInfo' => 'appid#' . $this->appId . '|bargainor_id#' . $this->mchId . '|channel#wallet'];
    }

    /**
     * APP支付
     * @param $params 下单参数
     * @return mixed APP支付json数据
     */
    public function appPay($params)
    {
        $params['trade_type'] = 'APP';
        $result = $this->unifiedOrder($params);
        return $this->getAppParameters($result['prepay_id']);
    }

    /**
     * 获取APP支付的参数
     * @param $prepay_id 预支付交易会话标识
     * @return array
     */
    private function getAppParameters($prepay_id)
    {
        $params = [
            'appId' => $this->appId,
            'nonce' => $this->getNonceStr(),
            'tokenId' => $prepay_id,
            'pubAcc' => '',
            'bargainorId' => $this->mchId,
        ];
        $params['sig'] = $this->makeAppSign($params);
        $params['sigType'] = 'HMAC-SHA1';
        $params['timeStamp'] = time();
        return $params;
    }

    /**
     * 生成APP支付签名
     * @param $data
     * @return string
     */
    private function makeAppSign()
    {
        ksort($data);
        $signStr = '';
        foreach ($data as $k => $v) {
            $signStr .= $k . '=' . $v . '&';
        }
        $signStr = trim($signStr, '&');
        $sign = base64_encode(hash_hmac("sha1", $signStr, $this->appKey.'&', true));
        return $sign;
    }

    /**
     * 付款码支付
     * @param $params 下单参数
     * @return mixed {"trade_state":"SUCCESS","total_fee":888,"cash_fee":888,"transaction_id":"QQ钱包订单号","out_trade_no":"商户订单号","time_end":"支付完成时间","trade_state_desc":"交易状态描述","openid":"用户标识"}
     */
    public function microPay($params)
    {
        $url = 'https://qpay.qq.com/cgi-bin/pay/qpay_micro_pay.cgi';
        if (empty($params['out_trade_no'])) {
            throw new \InvalidArgumentException('缺少付款码支付接口必填参数out_trade_no');
        }
        if (empty($params['body'])) {
            throw new \InvalidArgumentException('缺少付款码支付接口必填参数body');
        }
        if (empty($params['total_fee'])) {
            throw new \InvalidArgumentException('缺少付款码支付接口必填参数total_fee');
        }
        if (empty($params['auth_code'])) {
            throw new \InvalidArgumentException('缺少付款码支付接口必填参数auth_code');
        }
        return $this->execute($url, $params);
    }

    /**
     * 撤销订单
     * @param $out_trade_no 商户订单号
     * @return mixed
     */
    public function reverse($out_trade_no)
    {
        $url = 'https://api.qpay.qq.com/cgi-bin/pay/qpay_reverse.cgi';
        $params = [
            'out_trade_no' => $out_trade_no,
            'op_user_id' => $this->opUserId,
            'op_user_passwd' => md5($this->opUserPwd)
        ];
        return $this->execute($url, $params, true);
    }

    /**
     * 查询订单，QQ钱包订单号、商户订单号至少填一个
     * @param $transaction_id QQ钱包订单号
     * @param $out_trade_no 商户订单号
     * @return mixed
     */
    public function orderQuery($transaction_id = null, $out_trade_no = null)
    {
        $url = 'https://qpay.qq.com/cgi-bin/pay/qpay_order_query.cgi';
        $params = [];
        if ($transaction_id) {
            $params['transaction_id'] = $transaction_id;
        } elseif ($out_trade_no) {
            $params['out_trade_no'] = $out_trade_no;
        }
        return $this->execute($url, $params);
    }

    /**
     * 判断订单是否已完成
     * @param $transaction_id QQ钱包订单号
     * @return bool
     */
    public function orderQueryResult($transaction_id)
    {
        try {
            $data = $this->orderQuery($transaction_id);
            return $data['trade_state'] == 'SUCCESS' || $data['trade_state'] == 'REFUND';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 关闭订单
     * @param $out_trade_no 商户订单号
     * @return mixed
     */
    public function closeOrder($out_trade_no)
    {
        $url = 'https://qpay.qq.com/cgi-bin/pay/qpay_close_order.cgi';
        $params = [
            'out_trade_no' => $out_trade_no
        ];
        return $this->execute($url, $params);
    }

    /**
     * 申请退款
     * @param $params
     * @return mixed
     */
    public function refund($params)
    {
        $url = 'https://api.qpay.qq.com/cgi-bin/pay/qpay_refund.cgi';
        if (empty($params['transaction_id']) && empty($params['out_trade_no'])) {
            throw new \InvalidArgumentException('out_trade_no、transaction_id至少填一个');
        }
        if (empty($params['out_refund_no'])) {
            throw new \InvalidArgumentException('out_refund_no参数不能为空');
        }
        if (empty($params['refund_fee'])) {
            throw new \InvalidArgumentException('refund_fee参数不能为空');
        }
        $params += [
            'op_user_id' => $this->opUserId,
            'op_user_passwd' => md5($this->opUserPwd)
        ];
        return $this->execute($url, $params, true);
    }

    /**
     * 查询退款
     * @param $params
     * @return mixed
     */
    public function refundQuery($params)
    {
        $url = 'https://qpay.qq.com/cgi-bin/pay/qpay_refund_query.cgi';
        if (empty($params['transaction_id']) && empty($params['out_trade_no']) && empty($params['out_refund_no']) && empty($params['refund_id'])) {
            throw new \InvalidArgumentException('退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个');
        }
        return $this->execute($url, $params);
    }

    /**
     * 下载对账单
     * @param $params
     * @return mixed
     */
    public function downloadBill($params)
    {
        $url = 'https://qpay.qq.com/cgi-bin/sp_download/qpay_mch_statement_down.cgi';
        if (empty($params['bill_date'])) {
            throw new \InvalidArgumentException('bill_date参数不能为空');
        }
        if (empty($params['bill_type'])) {
            throw new \InvalidArgumentException('bill_type参数不能为空');
        }
        return $this->download($url, $params);
    }

    /**
     * 下载资金账单
     * @param $params
     * @return mixed
     */
    public function downloadFundFlow($params)
    {
        $url = 'https://qpay.qq.com/cgi-bin/sp_download/qpay_mch_acc_roll.cgi';
        if (empty($params['bill_date'])) {
            throw new \InvalidArgumentException('bill_date参数不能为空');
        }
        if (empty($params['acc_type'])) {
            throw new \InvalidArgumentException('acc_type参数不能为空');
        }
        return $this->download($url, $params);
    }

    /**
     * 支付结果通知
     * @return bool|mixed
     */
    public function notify()
    {
        $xml = file_get_contents("php://input");
        if (empty($xml)) {
            throw new \Exception('NO_DATA');
        }
        $result = $this->xml2array($xml);
        if (!$result) {
            throw new \Exception('XML_ERROR');
        }
        if (!$this->checkSign($result)) {
            throw new \Exception('签名校验失败');
        }
        if (!isset($result['transaction_id'])) {
            throw new \Exception('缺少订单号参数');
        }
        if (!$this->orderQueryResult($result['transaction_id'])) {
            throw new \Exception('订单未完成');
        }
        return $result;
    }

    /**
     * 回复通知
     * @param $isSuccess 是否成功
     * @param $msg 失败原因
     */
    public function replyNotify($isSuccess = true, $msg = '')
    {
        $data = [];
        if ($isSuccess) {
            $data['return_code'] = 'SUCCESS';
        } else {
            $data['return_code'] = 'FAIL';
            $data['return_msg'] = $msg;
        }
        $xml = $this->array2Xml($data);
        echo $xml;
    }
}
