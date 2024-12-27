<?php
/**
 * 企业微信回调页面
 */
include("./includes/common.php");

$msg_paybtn = '>>>点击此处开始支付<<<';
$msg_contact_prepend = '支付未到账等问题可';
$msg_contact_btn = '联系人工客服';


$weworkMsg = new \lib\wechat\WeWorkMsg($conf['wework_token'], $conf['wework_aeskey']);

if(isset($_GET['echostr'])) {
    $weworkMsg->verifyURL();
}

$msg = $weworkMsg->getMessage();

if(!isset($msg['MsgType'])) exit('消息内容异常');

if($msg['MsgType'] == 'event' && $msg['Event'] == 'kf_msg_or_event'){
    $kfid = $msg['OpenKfId'];
    $token = $msg['Token'];
    $wxkfaccount = $DB->find('wxkfaccount', 'id,wid', ['openkfid'=>$kfid]);
    if(!$wxkfaccount) exit('该微信客服账号不存在:'.$kfid);
    
    $wework = new \lib\wechat\WeWorkAPI($wxkfaccount['wid']);
    try{
        $msg_list = $wework->lockGetMsg($kfid, $token);
    }catch(Exception $e){
        $errmsg = $e->getMessage();
        $CACHE->save('wxkferrmsg', ['errmsg'=>$errmsg, 'time'=>$date], 86400);
        exit($errmsg);
    }
    //print_r($msg_list);
    foreach($msg_list as $row){
        if($row['msgtype'] == 'event' && $row['event']['event_type'] == 'enter_session' && $row['event']['scene'] == 'pay'){
            //用户进入客服聊天界面，发送确认支付菜单消息
            try{
                parse_str(urldecode($row['event']['scene_param']), $scene_param);
                if(isset($scene_param['orderid']) && isset($scene_param['money'])){
                    $wxkflog = $DB->find('wxkflog', 'trade_no,payurl', ['trade_no' => $scene_param['orderid']]);
                    if($wxkflog){
                        $head_content = '您的订单金额：'.$scene_param['money'].'元';
                        $menu_list = [];
                        $menu_list[] = ['type'=>'text', 'text'=>['content'=>'\n','no_newline'=>1]];
                        if($conf['wework_paymsgmode'] == 1){
                            if(strpos($wxkflog['payurl'], 'wxpay://')!==false){
                                $menu_list[] = ['type'=>'text', 'text'=>['content'=>$wxkflog['payurl']]];
                            }else{
                                $menu_list[] = ['type'=>'view', 'view'=>['url'=>$wxkflog['payurl'], 'content'=>$msg_paybtn]];
                            }
                        }else{
                            $menu_list[] = ['type'=>'click', 'click'=>['id'=>$scene_param['orderid'], 'content'=>$msg_paybtn]];
                        }
                        if(!empty($conf['wework_contact'])){
                            $menu_list[] = ['type'=>'text', 'text'=>['content'=>'\n','no_newline'=>1]];
                            $menu_list[] = ['type'=>'text', 'text'=>['content'=>$msg_contact_prepend,'no_newline'=>1]];
                            $menu_list[] = ['type'=>'view', 'view'=>['url'=>$conf['wework_contact'], 'content'=>$msg_contact_btn]];
                        }elseif(!empty($conf['wework_remark'])){
                            $menu_list[] = ['type'=>'text', 'text'=>['content'=>'\n','no_newline'=>1]];
                        }
                        $tail_content = $conf['wework_remark'];
                        if(!empty($tail_content) && strpos($tail_content, '[qq]')!==false){
                            $order_uid = $DB->findColumn('order', 'uid', ['trade_no' => $scene_param['orderid']]);
		                    $tail_content = str_replace('[qq]', $DB->findColumn('user', 'qq', ['uid'=>$order_uid]), $tail_content);
                        }
                        if(!empty($row['event']['welcome_code'])){
                            $wework->sendWelcomeMenuMsg($row['event']['welcome_code'], $head_content, $menu_list, $tail_content);
                        }else{
                            $wework->sendMenuMsg($row['event']['external_userid'], $row['event']['open_kfid'], $head_content, $menu_list, $tail_content);
                        }
                        $DB->update('wxkflog', ['status'=>1, 'addtime'=>'NOW()'], ['trade_no'=>$wxkflog['trade_no']]);
                    }else{
                        if(!empty($row['event']['welcome_code'])){
                            $wework->sendWelcomeTextMsg($row['event']['welcome_code'], '订单不存在。');
                        }else{
                            $wework->sendTextMsg($row['event']['external_userid'], $row['event']['open_kfid'], '订单不存在。');
                        }
                    }
                }else{
                    if(!empty($row['event']['welcome_code'])){
                        $wework->sendWelcomeTextMsg($row['event']['welcome_code'], '订单参数有误。');
                    }else{
                        $wework->sendTextMsg($row['event']['external_userid'], $row['event']['open_kfid'], '订单参数有误。');
                    }
                }
            }catch(Exception $e){
                $errmsg = $e->getMessage();
                $CACHE->save('wxkferrmsg', ['errmsg'=>$errmsg, 'time'=>$date], 86400);
                echo $errmsg."\r\n";
            }
        }elseif($row['msgtype'] == 'text' && $row['text']['content'] == $msg_paybtn){
            //用户回复菜单消息，发送支付链接
            $orderid = $row['text']['menu_id'];
            if(!empty($orderid)){
                $payurl = $DB->findColumn('wxkflog', 'payurl', ['trade_no' => $orderid]);
                if($payurl){
                    $wework->sendTextMsg($row['external_userid'], $row['open_kfid'], $payurl);
                }else{
                    $wework->sendTextMsg($row['external_userid'], $row['open_kfid'], '订单支付链接不存在。');
                }
            }else{
                $wework->sendTextMsg($row['external_userid'], $row['open_kfid'], '订单参数有误。');
            }
        }
    }
    echo 'success';
}