<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'transferList':
	$sql=" 1=1";
	if(isset($_POST['uid']) && !empty($_POST['uid'])) {
		$uid = intval($_POST['uid']);
		$sql.=" AND `uid`='$uid'";
	}
	if(isset($_POST['type']) && !empty($_POST['type'])) {
		$type = intval($_POST['type']);
		$sql.=" AND `type`='$type'";
	}
	if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
		$dstatus = intval($_POST['dstatus']);
		$sql.=" AND `status`={$dstatus}";
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$value = daddslashes($_POST['value']);
		$sql.=" AND (`biz_no`='{$value}' OR `account` like '%{$value}%' OR `username` like '%{$value}%')";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_transfer WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_transfer WHERE{$sql} order by biz_no desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'transfer_query':
	$biz_no=trim($_GET['biz_no']);
	$result = \lib\Transfer::status($biz_no);
	exit(json_encode($result));
break;
case 'transfer_result':
	$biz_no=trim($_GET['biz_no']);
    $row = $DB->find('transfer', 'biz_no,result', ['biz_no' => $biz_no]);
	if(!$row) exit('{"code":-1,"msg":"付款记录不存在！"}');
	$result = ['code'=>0,'msg'=>$row['result']?$row['result']:'未知'];
	exit(json_encode($result));
break;
case 'balance_query':
	$type = $_POST['type'];
	$channel = isset($_POST['channel'])?intval($_POST['channel']):$conf['transfer_'.$type];
	$channel = \lib\Channel::get($channel);
	if(!$channel)exit('{"code":-1,"msg":"当前支付通道信息不存在"}');
	$user_id = isset($_POST['user_id'])?$_POST['user_id']:null;
	$result = \lib\Transfer::balance($type, $channel, $user_id);
	exit(json_encode($result));
break;
case 'setTransferStatus':
	$biz_no=$_POST['biz_no'];
	$status=intval($_POST['status']);
	if($DB->exec("UPDATE pre_transfer SET status='$status' WHERE biz_no='$biz_no'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"修改失败['.$DB->error().']"}');
break;
case 'delTransfer':
	$biz_no=$_POST['biz_no'];
	if($DB->exec("DELETE FROM pre_transfer WHERE biz_no='$biz_no'")!==false)exit('{"code":0,"msg":"succ"}');
	else exit('{"code":-1,"msg":"删除失败['.$DB->error().']"}');
break;
case 'refundTransfer':
	$biz_no=$_POST['biz_no'];
	$order = $DB->find('transfer', '*', ['biz_no' => $biz_no]);
    if(!$order) exit('{"code":-1,"msg":"付款记录不存在！"}');
	if($DB->exec("UPDATE pre_transfer SET status='2' WHERE biz_no='$biz_no'")){
		if($order['uid'] > 0){
			changeUserMoney($order['uid'], $order['costmoney'], true, '代付退回');
		}
	}
	exit('{"code":0,"msg":"已成功将￥'.$order['costmoney'].'推给商户'.$order['uid'].'"}');
break;
case 'transfer_proof':
	$biz_no=trim($_POST['biz_no']);
	$result = \lib\Transfer::proof($biz_no);
	exit(json_encode($result));
break;
case 'operation': //批量操作订单
	$status=is_numeric($_POST['status'])?intval($_POST['status']):exit('{"code":-1,"msg":"请选择操作"}');
	$checkbox=$_POST['checkbox'];
	$i=0;
	foreach($checkbox as $biz_no){
		if($status==3)$DB->exec("DELETE FROM pre_transfer WHERE biz_no='$biz_no'");
		else $DB->exec("update pre_transfer set status='$status' where biz_no='$biz_no' limit 1");
		$i++;
	}
	exit('{"code":0,"msg":"成功改变'.$i.'条订单状态"}');
break;
default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}