<?php

namespace lib\ProfitSharing;

use Exception;

class Wxpay implements IProfitSharing
{
    
    static $paytype = 'wxpay';

    private $channel;
    private $service;
    private $ecommerce;

    function __construct($channel){
		$this->channel = $channel;
        $wechatpay_config = require(PLUGIN_ROOT.$channel['plugin'].'/inc/config.php');
        $this->ecommerce = $wechatpay_config['ecommerce'];
        $this->service = new \WeChatPay\V3\ProfitsharingService($wechatpay_config);
	}

    //请求分账
    public function submit($trade_no, $api_trade_no, $account, $name, $money){
        $type = self::get_wxpay_account_type($account);
        if($this->ecommerce){
            $param = [
                'transaction_id' => $api_trade_no,
                'out_order_no' => $trade_no,
                'receivers' => [
                    [
                        'type' => $type,
                        'receiver_account' => $account,
                        'amount' => intval(round($money*100)),
                        'description' => '订单分账'
                    ]
                ],
                'finish' => true,
            ];
        }else{
            $param = [
                'transaction_id' => $api_trade_no,
                'out_order_no' => $trade_no,
                'receivers' => [
                    [
                        'type' => $type,
                        'account' => $account,
                        'amount' => intval(round($money*100)),
                        'description' => '订单分账'
                    ]
                ],
                'unfreeze_unsplit' => true,
            ];
        }
        try{
            $result = $this->service->submit($param);
            return ['code'=>0, 'msg'=>'请求分账成功', 'settle_no'=>$result['order_id']];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //查询分账结果
    public function query($trade_no, $api_trade_no, $settle_no){
        $reason_desc = ['ACCOUNT_ABNORMAL'=>'分账接收账户异常', 'NO_RELATION'=>'分账关系已解除', 'RECEIVER_HIGH_RISK'=>'高风险接收方', 'RECEIVER_REAL_NAME_NOT_VERIFIED'=>'接收方未实名', 'NO_AUTH'=>'分账权限已解除', 'RECEIVER_RECEIPT_LIMIT'=>'接收方已达收款限额', 'PAYER_ACCOUNT_ABNORMAL'=>'分出方账户异常', 'INVALID_REQUEST'=>'描述参数设置失败'];

        try{
            $result = $this->service->query($trade_no, $api_trade_no);
            if(isset($result['state']) && $result['state'] == 'FINISHED' || isset($result['status']) && $result['status'] == 'FINISHED'){
                $receiver = $result['receivers'][0];
                if($receiver['result'] == 'SUCCESS'){
                    return ['code'=>0, 'status'=>1];
                }elseif($receiver['result'] == 'CLOSED'){
                    return ['code'=>0, 'status'=>2, 'reason'=>'['.$receiver['fail_reason'].']'.$reason_desc[$receiver['fail_reason']]];
                }else{
                    return ['code'=>0, 'status'=>0];
                }
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
            $this->service->unfreeze($trade_no, $api_trade_no);
            return ['code'=>0, 'msg'=>'解冻剩余资金成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //分账回退
    public function return($trade_no, $api_trade_no, $account, $money){
        return ['code'=>-1,'msg'=>'分账到个人账户不支持回退'];
    }

    //添加分账接收方
    public function addReceiver($account, $name = null){
        $type = self::get_wxpay_account_type($account);
        try{
            $this->service->addReceiver($type, $account, $name);
            return ['code'=>0, 'msg'=>'添加分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    //删除分账接收方
    public function deleteReceiver($account){
        $type = self::get_wxpay_account_type($account);
        try{
            $this->service->deleteReceiver($type, $account);
            return ['code'=>0, 'msg'=>'删除分账接收方成功'];
        } catch (Exception $e) {
            return ['code'=>-1, 'msg'=>$e->getMessage()];
        }
    }

    private static function get_wxpay_account_type($account){
        if(is_numeric($account))$type = 'MERCHANT_ID';
	    else $type = 'PERSONAL_OPENID';
        return $type;
    }
}