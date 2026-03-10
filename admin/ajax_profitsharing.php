<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){

case 'orderList':
	$sql=" 1=1";
	if(isset($_POST['rid']) && !empty($_POST['rid'])) {
		$rid = intval($_POST['rid']);
		$sql.=" AND `rid`='$rid'";
	}
	if(isset($_POST['dstatus']) && $_POST['dstatus']>-1) {
		$dstatus = intval($_POST['dstatus']);
		$sql.=" AND `status`={$dstatus}";
	}
	if(isset($_POST['value']) && !empty($_POST['value'])) {
		$sql.=" AND `{$_POST['column']}`='{$_POST['value']}'";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_psorder WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_psorder WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'get_receiver':
	$id=intval($_GET['id']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	exit(json_encode(['code'=>0, 'data'=>$row]));
break;

case 'add_receiver':
	$data = [
		'channel' => intval($_POST['channel']),
		'uid' => !empty($_POST['uid'])?intval($_POST['uid']):null,
		'account' => trim($_POST['account']),
		'name' => trim($_POST['name']),
		'rate' => !empty($_POST['rate'])?trim($_POST['rate']):'30',
		'minmoney' => trim($_POST['minmoney']),
		'status' => 0,
		'addtime' => 'NOW()'
	];
	if(!$data['channel'] || !$data['account'])exit('{"code":-1,"msg":"必填项不能为空"}');
	if(!empty($data['uid']) && !$DB->find('user', 'uid', ['uid'=>$data['uid']]))exit('{"code":-1,"msg":"商户ID不存在"}');
	if(!\lib\Channel::get($data['channel']))exit('{"code":-1,"msg":"支付通道不存在"}');
	$rows = $DB->getRow("SELECT * FROM `pre_psreceiver` WHERE `channel`='{$data['channel']}' AND ".($data['uid']?"`uid`='{$data['uid']}'":"`uid` IS NULL")."");
	if($rows)exit('{"code":-1,"msg":"该支付通道&UID已存在分账规则，每次支付只能同时给1个人分账"}');
	if($DB->insert('psreceiver', $data)){
		exit('{"code":0,"msg":"新增分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"新增分账规则失败['.$DB->error().']"}');
	}
break;

case 'edit_receiver':
	$id=intval($_POST['id']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	$data = [
		'channel' => intval($_POST['channel']),
		'uid' => !empty($_POST['uid'])?intval($_POST['uid']):null,
		'account' => trim($_POST['account']),
		'name' => trim($_POST['name']),
		'rate' => !empty($_POST['rate'])?trim($_POST['rate']):30,
		'minmoney' => trim($_POST['minmoney']),
	];
	if(!$data['channel'] || !$data['account'])exit('{"code":-1,"msg":"必填项不能为空"}');
	if(!empty($data['uid']) && !$DB->find('user', 'uid', ['uid'=>$data['uid']]))exit('{"code":-1,"msg":"商户ID不存在"}');
	if(!\lib\Channel::get($data['channel']))exit('{"code":-1,"msg":"支付通道不存在"}');
	if($row['status']==1 && ($data['account']!=$row['account'] || $data['name']!=$row['name'] || $data['channel']!=$row['channel']))exit('{"code":-1,"msg":"请先将状态改为已关闭再修改信息"}');
	$rows = $DB->getRow("SELECT * FROM `pre_psreceiver` WHERE `channel`='{$data['channel']}' AND ".($data['uid']?"`uid`='{$data['uid']}'":"`uid` IS NULL")." AND id!='$id'");
	if($rows)exit('{"code":-1,"msg":"该支付通道&UID已存在分账规则，每次支付只能同时给1个人分账"}');
	if($DB->update('psreceiver', $data, ['id'=>$id])!==false){
		exit('{"code":0,"msg":"修改分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"修改分账规则失败['.$DB->error().']"}');
	}
break;

case 'set_receiver':
	$id=intval($_POST['id']);
	$status=intval($_POST['status']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	if($row['uid'])$channelinfo = $DB->getColumn("SELECT `channelinfo` FROM `pre_user` WHERE `uid`='{$row['uid']}' LIMIT 1");
	$channel = \lib\Channel::get($row['channel'], $channelinfo);
	if(!$channel) exit('{"code":-1,"msg":"当前支付通道不存在！"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	if($status == 1){
		$result = $model->addReceiver($row['account'], $row['name']);
	}elseif($status == 0){
		$result = $model->deleteReceiver($row['account'], $row['name']);
	}
	if($result['code'] == 0){
		$DB->update('psreceiver', ['status'=>$status], ['id'=>$id]);
		exit('{"code":0,"msg":"状态修改成功！"}');
	}else{
		exit(json_encode($result));
	}
break;

case 'del_receiver':
	$id=intval($_POST['id']);
	$row=$DB->find('psreceiver', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	if($row['status']==1){
		if($row['uid'])$channelinfo = $DB->getColumn("SELECT `channelinfo` FROM `pre_user` WHERE `uid`='{$row['uid']}' LIMIT 1");
		$channel = \lib\Channel::get($row['channel'], $channelinfo);
		$model = \lib\ProfitSharing\CommUtil::getModel($channel);
		if($channel){
			$model->deleteReceiver($row['account'], $row['name']);
		}
	}
	if($DB->delete('psreceiver', ['id'=>$id])){
		exit('{"code":0,"msg":"删除分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"删除分账规则失败['.$DB->error().']"}');
	}
break;


case 'get_receiver2':
	$id=intval($_GET['id']);
	$row=$DB->find('psreceiver2', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	exit(json_encode(['code'=>0, 'data'=>$row]));
break;

case 'add_receiver2':
	$data = [
		'channel' => intval($_POST['channel']),
		'uid' => !empty($_POST['uid'])?intval($_POST['uid']):null,
		'bank_type' => '2',
		'card_id' => trim($_POST['card_id']),
		'card_name' => trim($_POST['card_name']),
		'cert_id' => trim($_POST['cert_id']),
		'tel_no' => trim($_POST['tel_no']),
		'rate' => !empty($_POST['rate'])?trim($_POST['rate']):'30',
		'minmoney' => trim($_POST['minmoney']),
		'status' => 1,
		'addtime' => 'NOW()'
	];
	if(!$data['channel'] || !$data['card_id'] || !$data['card_name'] || !$data['cert_id'] || !$data['tel_no'])exit('{"code":-1,"msg":"必填项不能为空"}');
	if(!empty($data['uid']) && !$DB->find('user', 'uid', ['uid'=>$data['uid']]))exit('{"code":-1,"msg":"商户ID不存在"}');
	$channel = \lib\Channel::get($data['channel']);
	if(!$channel)exit('{"code":-1,"msg":"支付通道不存在"}');
	if(!is_numeric($data['tel_no']) || strlen($data['tel_no'])!=11)exit('{"code":-1,"msg":"手机号码不正确"}');
	if(!is_idcard($data['cert_id']))exit('{"code":-1,"msg":"身份证号码不正确"}');
	$rows = $DB->getRow("SELECT * FROM `pre_psreceiver2` WHERE `channel`='{$data['channel']}' AND ".($data['uid']?"`uid`='{$data['uid']}'":"`uid` IS NULL")."");
	if($rows)exit('{"code":-1,"msg":"该支付通道&UID已存在分账规则，每次支付只能同时给1个人分账"}');

	$DB->beginTransaction();
	if($id = $DB->insert('psreceiver2', $data)){
		$result = \lib\ProfitSharing\CommUtil::addReceiver_adapay($channel, $id, $data);
		if($result['code'] != 0){
			$DB->rollBack();
			exit(json_encode($result));
		}
		$DB->update('psreceiver2', ['settleid' => $result['settleid']], ['id'=>$id]);
		$DB->commit();
		exit('{"code":0,"msg":"新增分账规则成功！"}');
	}else{
		$DB->rollBack();
		exit('{"code":-1,"msg":"新增分账规则失败['.$DB->error().']"}');
	}
break;

case 'edit_receiver2':
	$id=intval($_POST['id']);
	$row=$DB->find('psreceiver2', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	$data = [
		'channel' => intval($_POST['channel']),
		'uid' => !empty($_POST['uid'])?intval($_POST['uid']):null,
		'bank_type' => '2',
		'card_id' => trim($_POST['card_id']),
		'card_name' => trim($_POST['card_name']),
		'cert_id' => trim($_POST['cert_id']),
		'tel_no' => trim($_POST['tel_no']),
		'rate' => !empty($_POST['rate'])?trim($_POST['rate']):30,
		'minmoney' => trim($_POST['minmoney']),
	];
	if(!$data['channel'] || !$data['card_id'] || !$data['card_name'] || !$data['cert_id'] || !$data['tel_no'])exit('{"code":-1,"msg":"必填项不能为空"}');
	if(!empty($data['uid']) && !$DB->find('user', 'uid', ['uid'=>$data['uid']]))exit('{"code":-1,"msg":"商户ID不存在"}');
	$channel = \lib\Channel::get($data['channel']);
	if(!$channel)exit('{"code":-1,"msg":"支付通道不存在"}');
	if(!is_numeric($data['tel_no']) || strlen($data['tel_no'])!=11)exit('{"code":-1,"msg":"手机号码不正确"}');
	if(!is_idcard($data['cert_id']))exit('{"code":-1,"msg":"身份证号码不正确"}');
	$rows = $DB->getRow("SELECT * FROM `pre_psreceiver2` WHERE `channel`='{$data['channel']}' AND ".($data['uid']?"`uid`='{$data['uid']}'":"`uid` IS NULL")." AND id!='$id'");
	if($rows)exit('{"code":-1,"msg":"该支付通道&UID已存在分账规则，每次支付只能同时给1个人分账"}');

	if($data['card_id']!=$row['card_id'] || $data['card_name']!=$row['card_name'] || $data['cert_id']!=$row['cert_id']){
		$result = \lib\ProfitSharing\CommUtil::editReceiver_adapay($channel, $row['id'], $data, $row['settleid']);
		if($result['code'] != 0){
			exit(json_encode($result));
		}
		$data['settleid'] = $result['settleid'];
	}
	if($DB->update('psreceiver2', $data, ['id'=>$id])!==false){
		exit('{"code":0,"msg":"修改分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"修改分账规则失败['.$DB->error().']"}');
	}
break;

case 'set_receiver2':
	$id=intval($_POST['id']);
	$status=intval($_POST['status']);
	$row=$DB->find('psreceiver2', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	$DB->update('psreceiver2', ['status'=>$status], ['id'=>$id]);
	exit('{"code":0,"msg":"状态修改成功！"}');
break;

case 'del_receiver2':
	$id=intval($_POST['id']);
	$row=$DB->find('psreceiver2', '*', ['id'=>$id]);
	if(!$row) exit('{"code":-1,"msg":"当前分账规则不存在！"}');
	$channel = \lib\Channel::get($row['channel']);
	if($channel){
		\lib\ProfitSharing\CommUtil::deleteReceiver_adapay($channel, $row['id'], $row['settleid']);
	}
	if($DB->delete('psreceiver2', ['id'=>$id])){
		exit('{"code":0,"msg":"删除分账规则成功！"}');
	}else{
		exit('{"code":-1,"msg":"删除分账规则失败['.$DB->error().']"}');
	}
break;


case 'submit':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,B.account,B.name,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=0&&$row['status']!=3)exit('{"code":-1,"msg":"只有待分账的订单才能提交分账"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$result = $model->submit($row['trade_no'], $row['api_trade_no'], $row['account'], $row['name'], $row['money']);
	if($result['code'] == 0){
		$DB->update('psorder', ['status'=>1,'settle_no'=>$result['settle_no']], ['id'=>$id]);
	}elseif($result['code'] == 1){
		$DB->update('psorder', ['status'=>2,'settle_no'=>$result['settle_no']], ['id'=>$id]);
	}elseif($result['code'] == -2){
		//$DB->update('psorder', ['status'=>3,'result'=>$result['msg']], ['id'=>$id]);
	}
	exit(json_encode($result));
break;

case 'query':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,B.account,B.name,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=1)exit('{"code":-1,"msg":"只有已提交的订单才能查询结果"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$result = $model->query($row['trade_no'], $row['api_trade_no'], $row['settle_no']);
	if($result['code']==0){
		if($result['status']==1){
			$DB->update('psorder', ['status'=>2], ['id'=>$id]);
		}elseif($result['status']==2){
			$DB->update('psorder', ['status'=>3,'result'=>$result['reason']], ['id'=>$id]);
		}
	}
	exit(json_encode($result));
break;

case 'unfreeeze':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,B.account,B.name,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=0)exit('{"code":-1,"msg":"只有待分账的订单才能取消分账"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$result = $model->unfreeeze($row['trade_no'], $row['api_trade_no']);
	if($result['code'] == 0){
		$DB->update('psorder', ['status'=>4], ['id'=>$id]);
	}
	exit(json_encode($result));
break;

case 'return':
	$id=intval($_POST['id']);
	$row = $DB->getRow("SELECT A.*,B.channel,B.account,B.name,C.uid,C.subchannel FROM pre_psorder A LEFT JOIN pre_psreceiver B ON A.rid=B.id LEFT JOIN pre_order C ON C.trade_no=A.trade_no WHERE A.id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=2)exit('{"code":-1,"msg":"只有分账成功的订单才能回退"}');
	$channel = $row['subchannel'] > 0 ? \lib\Channel::getSub($row['subchannel']) : \lib\Channel::get($row['channel'], $row['uid']?$DB->findColumn('user', 'channelinfo', ['uid'=>$row['uid']]):null);
	if(!$channel) exit('{"code":-1,"msg":"通道信息不存在"}');
	$model = \lib\ProfitSharing\CommUtil::getModel($channel);
	$result = $model->return($row['trade_no'], $row['api_trade_no'], $row['account'], $row['money']);
	if($result['code'] == 0){
		$DB->update('psorder', ['status'=>4], ['id'=>$id]);
	}
	exit(json_encode($result));
break;

case 'editmoney':
	$id=intval($_POST['id']);
	$money=trim($_POST['money']);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))exit('{"code":-1,"msg":"金额输入错误"}');
	$row = $DB->getRow("SELECT * FROM pre_psorder WHERE id=:id", [':id'=>$id]);
	if(!$row)exit('{"code":-1,"msg":"订单不存在"}');
	if($row['status']!=0)exit('{"code":-1,"msg":"只有待分账的订单才能修改金额"}');
	$DB->update('psorder', ['money'=>$money], ['id'=>$id]);
	exit('{"code":0,"msg":"succ"}');
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}