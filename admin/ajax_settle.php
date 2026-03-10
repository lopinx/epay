<?php
include("../includes/common.php");
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

if(!checkRefererHost())exit('{"code":403}');

@header('Content-Type: application/json; charset=UTF-8');

switch($act){
case 'settleList':
	$sql=" 1=1";
	if(isset($_POST['batch']) && !empty($_POST['batch'])) {
		$batch = daddslashes($_POST['batch']);
		$sql.=" AND `batch`='$batch'";
	}
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
		$sql.=" AND (`account` like '%{$value}%' OR `username` like '%{$value}%')";
	}
	$offset = intval($_POST['offset']);
	$limit = intval($_POST['limit']);
	$total = $DB->getColumn("SELECT count(*) from pre_settle WHERE{$sql}");
	$list = $DB->getAll("SELECT * FROM pre_settle WHERE{$sql} order by id desc limit $offset,$limit");

	exit(json_encode(['total'=>$total, 'rows'=>$list]));
break;

case 'create_batch':
	$count=$DB->getColumn("SELECT count(*) from pre_settle where status=0");
	if($count==0)exit('{"code":-1,"msg":"当前不存在待结算的记录"}');
	$batch=date("Ymd").rand(111,999);
	$allmoney = 0;
	$rs=$DB->query("SELECT * from pre_settle where status=0");
	while($row = $rs->fetch())
	{
		$DB->exec("UPDATE pre_settle SET batch='$batch',status=2 WHERE id='{$row['id']}'");
		$allmoney+=$row['realmoney'];
	}
	$DB->insert('batch', ['batch'=>$batch, 'allmoney'=>$allmoney, 'count'=>$count, 'time'=>'NOW()', 'status'=>0]);

	exit(json_encode(['code'=>0, 'msg'=>'succ', 'batch'=>$batch, 'count'=>$count, 'allmoney'=>$allmoney]));
break;
case 'complete_batch':
	$batch=trim($_POST['batch']);
	$DB->exec("UPDATE pre_settle SET status=1 WHERE batch='$batch'");
	exit('{"code":0,"msg":"succ"}');
break;
case 'setSettleStatus':
	$id=intval($_GET['id']);
	$status=intval($_GET['status']);
	if($status==4){
		$row = $DB->find('settle', 'uid,money', ['id'=>$id]);
		if(!$row) exit('{"code":200}');
		if($DB->exec("DELETE FROM pre_settle WHERE id='$id'")){
			changeUserMoney($row['uid'], $row['money'], true, '结算失败退回');
			exit('{"code":200}');
		}
		else{
			exit('{"code":400,"msg":"删除记录失败！['.$DB->error().']"}');
		}
	}else{
		if($status==1){
			$sql = "update pre_settle set status='$status',endtime='$date',result=NULL where id='$id'";

			$row = $DB->find('settle', 'uid,money,realmoney,account', ['id'=>$id]);
			\lib\MsgNotice::send('settle', $row['uid'], ['money'=>$row['money'], 'realmoney'=>$row['realmoney'], 'time'=>date('Y-m-d H:i:s'), 'account'=>$row['account']]);
		}else{
			$sql = "update pre_settle set status='$status',endtime=NULL where id='$id'";
		}
		if($DB->exec($sql)!==false)
			exit('{"code":200}');
		else
			exit('{"code":400,"msg":"修改记录失败！['.$DB->error().']"}');
	}
break;
case 'opslist':
	$status=$_POST['status'];
	$checkbox=$_POST['checkbox'];
	$i=0;
	foreach($checkbox as $id){
		if($status==4){
			$sql = "DELETE FROM pre_settle WHERE id='$id'";
		}elseif($status==1){
			$sql = "update pre_settle set status='$status',endtime='$date',result=NULL where id='$id'";
		}else{
			$sql = "update pre_settle set status='$status',endtime=NULL where id='$id'";
		}
		$DB->exec($sql);
		$i++;
	}
	exit('{"code":0,"msg":"成功改变'.$i.'条记录状态"}');
break;
case 'settle_result':
	$id=intval($_POST['id']);
	$row=$DB->getRow("select result from pre_settle where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前结算记录不存在！"}');
	$result = ['code'=>0,'msg'=>'succ','result'=>$row['result']];
	exit(json_encode($result));
break;
case 'settle_setresult':
	$id=intval($_POST['id']);
	$result=trim($_POST['result']);
	$row=$DB->getRow("select * from pre_settle where id='$id' limit 1");
	if(!$row)
		exit('{"code":-1,"msg":"当前结算记录不存在！"}');
	$sds = $DB->exec("UPDATE pre_settle SET result='$result' WHERE id='$id'");
	if($sds!==false)
		exit('{"code":0,"msg":"修改成功！"}');
	else
		exit('{"code":-1,"msg":"修改失败！'.$DB->error().'"}');
break;
case 'settle_info':
	$id=intval($_GET['id']);
	$rows=$DB->getRow("select * from pre_settle where id='$id' limit 1");
	if(!$rows)
		exit('{"code":-1,"msg":"当前结算记录不存在！"}');
	$data = '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算方式</div><select class="form-control" id="pay_type" default="'.$rows['type'].'">'.($conf['settle_alipay']?'<option value="1">支付宝</option>':null).''.($conf['settle_wxpay']?'<option value="2">微信</option>':null).''.($conf['settle_qqpay']?'<option value="3">QQ钱包</option>':null).''.($conf['settle_bank']?'<option value="4">银行卡</option>':null).'</select></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">结算账号</div><input type="text" id="pay_account" value="'.$rows['account'].'" class="form-control" required/></div></div>';
	$data .= '<div class="form-group"><div class="input-group"><div class="input-group-addon">真实姓名</div><input type="text" id="pay_name" value="'.$rows['username'].'" class="form-control" required/></div></div>';
	$data .= '<input type="submit" id="save" onclick="saveInfo('.$id.')" class="btn btn-primary btn-block" value="保存">';
	$result=array("code"=>0,"msg"=>"succ","data"=>$data,"pay_type"=>$rows['type']);
	exit(json_encode($result));
break;
case 'settle_save':
	$id=intval($_POST['id']);
	$pay_type=intval($_POST['pay_type']);
	$pay_account=htmlspecialchars(trim($_POST['pay_account']));
	$pay_name=htmlspecialchars(trim($_POST['pay_name']));
	$data = ['type'=>$pay_type, 'account'=>$pay_account, 'username'=>$pay_name];
	if($DB->update('settle', $data, ['id'=>$id])!==false)
		exit('{"code":0,"msg":"修改记录成功！"}');
	else
		exit('{"code":-1,"msg":"修改记录失败！'.$DB->error().'"}');
break;
case 'paypwd_check':
	if(isset($_SESSION['paypwd']) && $_SESSION['paypwd']==$conf['admin_paypwd'])
		exit('{"code":0,"msg":"ok"}');
	else
		exit('{"code":-1,"msg":"error"}');
break;
case 'paypwd_input':
	$paypwd=trim($_POST['paypwd']);
	if(!$conf['admin_paypwd'])exit('{"code":-1,"msg":"你还未设置支付密码"}');
	if($paypwd == $conf['admin_paypwd']){
		$_SESSION['paypwd'] = $paypwd;
		exit('{"code":0,"msg":"ok"}');
	}else{
		exit('{"code":-1,"msg":"支付密码错误！"}');
	}
break;
case 'paypwd_reset':
	unset($_SESSION['paypwd']);
	exit('{"code":0,"msg":"ok"}');
break;

case 'transfer':
	$id = isset($_POST['id'])?intval($_POST['id']):exit('{"code":-1,"msg":"ID不能为空"}');
	$type = isset($_POST['type'])?intval($_POST['type']):exit('{"code":-1,"msg":"type不能为空"}');

	$payee_err_code = [ //收款方原因导致的失败编码
		'PAYEE_NOT_EXIST','PAYEE_ACCOUNT_STATUS_ERROR','CARD_BIN_ERROR','PAYEE_CARD_INFO_ERROR','PERM_AML_NOT_REALNAME_REV','PAYEE_USER_INFO_ERROR','PAYEE_ACC_OCUPIED','PERMIT_NON_BANK_LIMIT_PAYEE','PAYEE_TRUSTEESHIP_ACC_OVER_LIMIT','PAYEE_ACCOUNT_NOT_EXSIT','PAYEE_USERINFO_STATUS_ERROR','TRUSTEESHIP_RECIEVE_QUOTA_LIMIT','EXCEED_LIMIT_UNRN_DM_AMOUNT','INVALID_CARDNO','RELEASE_USER_FORBBIDEN_RECIEVE','PAYEE_USER_TYPE_ERROR','PAYEE_NOT_RELNAME_CERTIFY','PERMIT_LIMIT_PAYEE',

		'OPENID_ERROR','NAME_MISMATCH','V2_ACCOUNT_SIMPLE_BAN','MONEY_LIMIT','EXCEED_PAYEE_ACCOUNT_LIMIT','PAYEE_ACCOUNT_ABNORMAL','APPID_OR_OPENID_ERR',

		'REALNAME_CHECK_ERROR','RE_USER_NAME_CHECK_ERROR','ERR_TJ_BLACK','USER_FROZEN','TRANSFER_FAIL','TRANSFER_FEE_LIMIT_ERROR',

		'ACCOUNT_FROZEN','REAL_NAME_CHECK_FAIL','NAME_NOT_CORRECT','OPENID_INVALID','TRANSFER_QUOTA_EXCEED','DAY_RECEIVED_QUOTA_EXCEED','MONTH_RECEIVED_QUOTA_EXCEED','DAY_RECEIVED_COUNT_EXCEED','ID_CARD_NOT_CORRECT','ACCOUNT_NOT_EXIST','TRANSFER_RISK','REALNAME_ACCOUNT_RECEIVED_QUOTA_EXCEED','RECEIVE_ACCOUNT_NOT_PERMMIT','PAYEE_ACCOUNT_ABNORMAL','BLOCK_B2C_USERLIMITAMOUNT_BSRULE_MONTH','BLOCK_B2C_USERLIMITAMOUNT_MONTH',
	];

	if(!isset($_SESSION['paypwd']) || $_SESSION['paypwd']!==$conf['admin_paypwd'])exit('{"code":-1,"msg":"支付密码错误，请返回重新进入该页面"}');

	$row=$DB->getRow("SELECT * FROM pre_settle WHERE id='{$id}' limit 1");
	if(!$row)exit('{"code":-1,"msg":"记录不存在"}');
	if($row['type']!=$type)exit('{"code":-1,"msg":"转账类型不正确"}');

	if($row['transfer_status']==1)exit('{"code":0,"ret":2,"result":"转账订单号:'.$row['transfer_result'].' 支付时间:'.$row['transfer_date'].'"}');

	if($type == 1){
		$app = 'alipay';
		$channel = \lib\Channel::get($conf['transfer_alipay']);
	}elseif($type == 2){
		$app = 'wxpay';
		$channel = \lib\Channel::get($conf['transfer_wxpay']);
	}elseif($type == 3){
		$app = 'qqpay';
		$channel = \lib\Channel::get($conf['transfer_qqpay']);
	}elseif($type == 4){
		$app = 'bank';
		$channel = \lib\Channel::get($conf['transfer_bank']);
	}
	if(!$channel)exit('{"code":-1,"msg":"当前支付通道信息不存在"}');

	$out_biz_no = date("Ymd").'000'.$id;
	$result = \lib\Transfer::submit($app, $channel, $out_biz_no, $row['account'], $row['username'], $row['realmoney']);

	if($result['code']==0){
		$data['code']=0;
		$data['ret']=1;
		$data['result']='转账订单号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
		$DB->update('settle', ['status'=>1, 'endtime'=>'NOW()', 'transfer_status'=>1, 'transfer_result'=>$result["orderid"], 'transfer_date'=>$result["paydate"]], ['id'=>$id]);

		\lib\MsgNotice::send('settle', $row['uid'], ['money'=>$row['money'], 'realmoney'=>$row['realmoney'], 'time'=>date('Y-m-d H:i:s'), 'account'=>$row['account']]);
	} else {
		if(in_array($result['errcode'], $payee_err_code)){
			$data['code']=0;
			$data['ret']=0;
			$data['result']='转账失败 '.$result['msg'];
			$DB->update('settle', ['status'=>3, 'result'=>$result["msg"], 'transfer_status'=>2, 'transfer_result'=>$data['result']], ['id'=>$id]);
		}else{
			$data['code']=-1;
			$data['msg']=$result['msg'];
		}
	}
	exit(json_encode($data));
break;

default:
	exit('{"code":-4,"msg":"No Act"}');
break;
}