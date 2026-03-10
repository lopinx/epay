<?php

namespace WeChatPay;

/**
 * 分账服务类
 * @see https://pay.weixin.qq.com/wiki/doc/api/allocation.php?chapter=26_1
 */
class ProfitsharingService extends BaseService
{
    public function __construct($config)
    {
        parent::__construct($config);

        $this->publicParams = [
            'appid'      => $this->appId,
            'mch_id'     => $this->mchId,
            'nonce_str'  => $this->getNonceStr(),
            'sign_type'  => 'HMAC-SHA256',
        ];
        if (!empty($this->subMchId)) {
            $this->publicParams['sub_mch_id'] = $this->subMchId;
        }
        if (!empty($this->subAppId)) {
            $this->publicParams['sub_appid'] = $this->subAppId;
        }
    }

    /**
     * 添加分账接收方
     * @param $account 分账接收方账号
     * @param $name 用户姓名(填写后校验)
     * @return mixed
     */
    public function addReceiver($account, $name = null)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver';
        $receiver = [
            'type' => 'PERSONAL_OPENID',
            'account' => $account,
            'relation_type' => 'SERVICE_PROVIDER'
        ];
        if(!empty($name)) $receiver['name'] = $name;
        $params = [
            'receiver' => json_encode($receiver, JSON_UNESCAPED_UNICODE)
        ];
        return $this->execute($url, $params);
    }

    /**
     * 删除分账接收方
     * @param $account 分账接收方账号
     * @return mixed
     */
    public function deleteReceiver($account)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingremovereceiver';
        $receiver = [
            'type' => 'PERSONAL_OPENID',
            'account' => $account
        ];
        $params = [
            'receiver' => json_encode($receiver, JSON_UNESCAPED_UNICODE)
        ];
        return $this->execute($url, $params);
    }

    /**
     * 请求单次分账
     * @param $out_order_no 商户分账单号
     * @param $transaction_id 微信订单号
     * @param $openid 分账接收方账号
     * @param $name 用户姓名(填写后校验)
     * @param int $amount 分账金额(分)
     * @return mixed {"transaction_id":"微信订单号","out_order_no":"商户分账单号","order_id":"微信分账单号","status":"分账单状态","receivers":""}
     */
    public function submit($out_order_no, $transaction_id, $openid, $name, $amount)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharing';
        $receiver = [
            'type' => 'PERSONAL_OPENID',
            'account' => $openid,
            'amount' => $amount,
            'description' => '订单分账'
        ];
        if(!empty($name)) $receiver['name'] = $name;
        $params = [
            'out_order_no' => $out_order_no,
            'transaction_id' => $transaction_id,
            'receivers' => json_encode([$receiver], JSON_UNESCAPED_UNICODE)
        ];
        return $this->execute($url, $params, true);
    }

    /**
     * 查询分账结果
     * @param $out_order_no 商户分账单号
     * @param $transaction_id 微信订单号
     * @return mixed {"transaction_id":"微信订单号","out_order_no":"商户分账单号","order_id":"微信分账单号","status":"分账单状态","receivers":""}
     */
    public function query($out_order_no, $transaction_id)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingquery';
        $params = [
            'out_order_no' => $out_order_no,
            'transaction_id' => $transaction_id
        ];
        return $this->execute($url, $params);
    }

    /**
     * 解冻剩余资金
     * @param $out_order_no 商户分账单号
     * @param $transaction_id 微信订单号
     * @return mixed {"transaction_id":"微信订单号","out_order_no":"商户分账单号","order_id":"微信分账单号"}
     */
    public function unfreeze($out_order_no, $transaction_id)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish';
        $params = [
            'out_order_no' => $out_order_no,
            'transaction_id' => $transaction_id,
            'description' => '分账已完成'
        ];
        return $this->execute($url, $params, true);
    }

    /**
     * 查询订单待分账金额
     * @param $transaction_id 微信订单号
     * @return mixed {"transaction_id":"微信订单号","unsplit_amount":"订单剩余待分金额"}
     */
    public function orderAmountQuery($transaction_id)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingorderamountquery';
        $params = [
            'transaction_id' => $transaction_id,
        ];
        return $this->execute($url, $params);
    }

    /**
     * 分账回退
     * @param $params 请求参数
     * @return mixed
     */
    public function return($params)
    {
        $url = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingreturn';
        if (empty($params['order_id']) && empty($params['out_order_no'])) {
            throw new \InvalidArgumentException('order_id、out_order_no至少填一个');
        }
        if (empty($params['out_return_no'])) {
            throw new \InvalidArgumentException('out_return_no参数不能为空');
        }
        return $this->execute($url, $params, true);
    }

    /**
     * 分账回退结果查询
     * @param $params 请求参数
     * @return mixed
     */
    public function returnQuery($out_return_no, $out_order_no)
    {
        $url = 'https://api.mch.weixin.qq.com/pay/profitsharingreturnquery';
        $params = [
            'out_order_no' => $out_order_no,
            'out_return_no' => $out_return_no
        ];
        return $this->execute($url, $params);
    }

}