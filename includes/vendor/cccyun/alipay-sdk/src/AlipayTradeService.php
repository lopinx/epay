<?php

namespace Alipay;

/**
 * 支付宝交易服务类
 */
class AlipayTradeService extends AlipayService
{
    //互联网直付通模式子商户ID
    private $smid;

    /**
     * @param $config 支付宝配置信息
     */
    public function __construct($config)
    {
        parent::__construct($config);
        if (isset($config['smid'])) {
            $this->smid = $config['smid'];
        }
        if (isset($config['notify_url'])) {
            $this->notifyUrl = $config['notify_url'];
        }
        if (isset($config['return_url'])) {
            $this->returnUrl = $config['return_url'];
        }
    }

    /**
     * 付款码支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","open_id":"买家支付宝userid","buyer_logon_id":"买家支付宝账号"}
     * @see https://opendocs.alipay.com/open/02ekfp?ref=api&scene=32
     */
    public function scanPay($bizContent)
    {
        $apiName = 'alipay.trade.pay';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 扫码支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"out_trade_no":"商户订单号","qr_code":"二维码链接"}
     * @see https://opendocs.alipay.com/open/02ekfg?ref=api&scene=19
     */
    public function qrPay($bizContent)
    {
        $apiName = 'alipay.trade.precreate';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * JS支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号"}
     * @see https://opendocs.alipay.com/open/02ekfj?ref=api
     */
    public function jsPay($bizContent)
    {
        $apiName = 'alipay.trade.create';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * APP支付
     * @param $bizContent 请求参数的集合
     * @return string SDK请求串
     * @see https://opendocs.alipay.com/open/02e7gq?ref=api&scene=20
     */
    public function appPay($bizContent)
    {
        $apiName = 'alipay.trade.app.pay';
        return $this->aopSdkExecute($apiName, $bizContent);
    }

    /**
     * 电脑网站支付
     * @param $bizContent 请求参数的集合
     * @return string html表单
     * @see https://opendocs.alipay.com/open/028r8t?ref=api&scene=22
     */
    public function pagePay($bizContent)
    {
        $apiName = 'alipay.trade.page.pay';
        $bizContent['product_code'] = 'FAST_INSTANT_TRADE_PAY';
        return $this->aopPageExecute($apiName, $bizContent);
    }

    /**
     * 手机网站支付
     * @param $bizContent 请求参数的集合
     * @return string html表单
     * @see https://opendocs.alipay.com/open/02ivbs?ref=api&scene=21
     */
    public function wapPay($bizContent)
    {
        $apiName = 'alipay.trade.wap.pay';
        $bizContent['product_code'] = 'QUICK_WAP_WAY';
        return $this->aopPageExecute($apiName, $bizContent);
    }

    /**
     * 交易查询
     * @param $trade_no 支付宝交易号
     * @param $out_trade_no 商户订单号
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","open_id":"买家支付宝userid","buyer_logon_id":"买家支付宝账号","trade_status":"TRADE_SUCCESS","total_amount":88.88}
     */
    public function query($trade_no = null, $out_trade_no = null)
    {
        $apiName = 'alipay.trade.query';
        $bizContent = [];
        if ($trade_no) {
            $bizContent['trade_no'] = $trade_no;
        }
        if ($out_trade_no) {
            $bizContent['out_trade_no'] = $out_trade_no;
        }
        return $this->aopExecute($apiName, $bizContent);
    }
    
    /**
     * 交易是否成功
     * @param $trade_no 支付宝交易号
     * @param $out_trade_no 商户订单号
     * @return bool
     */
    public function queryResult($trade_no = null, $out_trade_no = null)
    {
        $result = $this->query($trade_no, $out_trade_no);
        if (isset($result['code']) && $result['code'] == '10000') {
            if ($result['trade_status'] == 'TRADE_SUCCESS' || $result['trade_status'] == 'TRADE_FINISHED' || $result['trade_status'] == 'TRADE_CLOSED') {
                return true;
            }
        }
        return false;
    }

    /**
     * 交易退款
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","buyer_user_id":"买家支付宝userid","buyer_logon_id":"买家支付宝账号","fund_change":"Y","refund_fee":88.88}
     * @see https://opendocs.alipay.com/open/02ekfk?ref=api
     */
    public function refund($bizContent)
    {
        $apiName = 'alipay.trade.refund';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 交易退款查询
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","out_request_no":"退款请求号","refund_status":"REFUND_SUCCESS","total_amount":88.88,"refund_amount":88.88}
     */
    public function refundQuery($bizContent)
    {
        $apiName = 'alipay.trade.fastpay.refund.query';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 交易撤销
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号","retry_flag":"N是否需要重试","action":"close本次撤销触发的交易动作"}
     */
    public function cancel($bizContent)
    {
        $apiName = 'alipay.trade.cancel';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 交易关闭
     * @param $bizContent 请求参数的集合
     * @return mixed {"trade_no":"支付宝交易号","out_trade_no":"商户订单号"}
     */
    public function close($bizContent)
    {
        $apiName = 'alipay.trade.close';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 查询对账单下载地址
     * @param $bizContent 请求参数的集合
     * @return mixed {"bill_download_url":"账单下载地址"}
     */
    public function downloadurlQuery($bizContent)
    {
        $apiName = 'alipay.data.dataservice.bill.downloadurl.query';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 支付回调验签
     * @param $params 支付宝返回的信息
     * @return bool
     */
    public function check($params){
        $result = $this->client->verify($params);
        if($result){
            $result = $this->queryResult($params['trade_no']);
        }
        return $result;
    }

    /**
     * 互联网直付通交易额外参数
     * @param &$bizContent
     * @param $settle_period_time 最晚结算周期
     * @see https://opendocs.alipay.com/open/direct-payment/qadp9d
     */
    public function directPayParams(&$bizContent, $settle_period_time = '1d')
    {
        if (empty($this->smid)) {
            throw new \Exception("子商户SMID不能为空");
        }
        if(strpos($this->smid, ',')){
            $smids = explode(',', $this->smid);
			$this->smid = $smids[array_rand($smids)];
        }
        $bizContent['sub_merchant'] = ['merchant_id' => $this->smid];
        $bizContent['settle_info'] = [
            'settle_period_time' => $settle_period_time,
            'settle_detail_infos' => [
                [
                    'trans_in_type' => 'defaultSettle',
                    'amount' => $bizContent['total_amount']
                ]
            ]
        ];
    }

    /**
     * 互联网直付通确认结算
     * @param $trade_no 支付宝交易号
     * @param $settle_amount 结算金额
     * @return mixed {"trade_no":"支付宝交易号","out_request_no":"确认结算请求流水号","settle_amount":"结算金额"}
     * @see https://opendocs.alipay.com/open/direct-payment/gkvknf
     */
    public function settle_confirm($trade_no, $settle_amount)
    {
        $apiName = 'alipay.trade.settle.confirm';
        $out_request_no = date("YmdHis").rand(11111,99999);
        $bizContent = array(
            'out_request_no' => $out_request_no,
            'trade_no' => $trade_no,
            'settle_info' => [
                'settle_detail_infos' => [
                    [
                        'trans_in_type' => 'defaultSettle',
                        'amount' => $settle_amount
                    ]
                ]
            ],
        );
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 合并支付预创建
     * @param $bizContent 请求参数的集合
     * @return mixed {"out_merge_no":"合单订单号","pre_order_no":"预下单号"}
     * @see https://opendocs.alipay.com/open/028xr9
     */
    public function mergePrecreatePay($bizContent)
    {
        $apiName = 'alipay.trade.merge.precreate';
        $params = null;
        if (!empty($this->returnUrl)) {
            $params['return_url'] = $this->returnUrl;
        }
        return $this->aopExecute($apiName, $bizContent, $params);
    }

    /**
     * 手机网站合单支付
     * @param $pre_order_no 预下单号
     * @return string html表单
     * @see https://opendocs.alipay.com/open/028xra
     */
    public function wapMergePay($bizContent)
    {
        $apiName = 'alipay.trade.wap.merge.pay';
        return $this->aopPageExecute($apiName, $bizContent);
    }

    /**
     * APP合单支付
     * @param $pre_order_no 预下单号
     * @return string SDK请求串
     * @see https://opendocs.alipay.com/open/028py8
     */
    public function appMergePay($bizContent)
    {
        $apiName = 'alipay.trade.app.merge.pay';
        return $this->aopSdkExecute($apiName, $bizContent);
    }

    /**
     * 小程序合单支付
     * @param $bizContent 请求参数的集合
     * @return mixed {"out_merge_no":"外部合并单号","merge_no":"合并交易号","order_detail_results":[]}
     * @see https://opendocs.alipay.com/open/0a0yaq
     */
    public function mergeCreate($bizContent)
    {
        $apiName = 'alipay.trade.merge.create';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 线上资金授权冻结
     * @param $bizContent 请求参数的集合
     * @return string SDK请求串
     * @see https://opendocs.alipay.com/open/repo-0243e2
     */
    public function preAuthFreeze($bizContent)
    {
        $apiName = 'alipay.fund.auth.order.app.freeze';
        return $this->aopSdkExecute($apiName, $bizContent);
    }

    /**
     * 资金授权解冻
     * @param $bizContent 请求参数的集合
     * @return mixed
     */
    public function preAuthUnfreeze($bizContent)
    {
        $apiName = 'alipay.fund.auth.order.unfreeze';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 资金授权撤销
     * @param $bizContent 请求参数的集合
     * @return mixed
     */
    public function preAuthCancel($bizContent)
    {
        $apiName = 'alipay.fund.auth.operation.cancel';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 资金授权操作查询接口
     * @param $bizContent 请求参数的集合
     * @return mixed
     */
    public function preAuthQuery($bizContent)
    {
        $apiName = 'alipay.fund.auth.operation.detail.query';
        return $this->aopExecute($apiName, $bizContent);
    }

    /**
     * 资金转账页面支付接口
     * @param $bizContent 请求参数的集合
     * @return mixed
     */
    public function transPagePay($bizContent)
    {
        $apiName = 'alipay.fund.trans.page.pay';
        return $this->aopPageExecute($apiName, $bizContent);
    }

    /**
     * 现金红包无线支付接口
     * @param $bizContent 请求参数的集合
     * @return mixed
     */
    public function transAppPay($bizContent)
    {
        $apiName = 'alipay.fund.trans.app.pay';
        return $this->aopSdkExecute($apiName, $bizContent);
    }
}