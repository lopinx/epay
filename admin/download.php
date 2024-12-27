<?php
include("../includes/common.php");

if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$act=isset($_GET['act'])?daddslashes($_GET['act']):null;

function display_type($type){
	if($type==1)
		return '支付宝';
	elseif($type==2)
		return '微信';
	elseif($type==3)
		return 'QQ钱包';
	elseif($type==4)
		return '银行卡';
	else
		return 1;
}

function display_status($status){
	if($status==1){
		return '已支付';
	}elseif($status==2){
		return '已退款';
	}elseif($status==3){
		return '已冻结';
	}else{
		return '未支付';
	}
}

function text_encoding($text){
	return mb_convert_encoding($text, "GB2312", "UTF-8");
}

switch($act){
case 'settle':
$type = isset($_GET['type'])?trim($_GET['type']):'common';
$batch=$_GET['batch'];
$remark = text_encoding($conf['transfer_desc']);

if($type == 'mybank'){
	$data="收款方名称,收款方账号,收款方开户行名称,收款行联行号,金额,附言/用途\r\n";
	
	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' and (type=1 or type=4) order by id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=text_encoding($row['username']).','.$row['account'].','.($row['type']=='1'?'支付宝':'').',,'.$row['realmoney'].','.$remark."\r\n";
	}

}elseif($type == 'alipay'){
	$data="支付宝批量付款文件模板\r\n";
	$data.="序号（必填）,收款方支付宝账号（必填）,收款方姓名（必填）,金额（必填，单位：元）,备注（选填）\r\n";

	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' and type=1 order by id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=$i.','.$row['account'].','.text_encoding($row['username']).','.$row['realmoney'].','.$remark."\r\n";
	}

}elseif($type == 'wxpay'){
	if(!$conf['transfer_wxpay'])sysmsg(mb_convert_encoding("未开启微信企业付款", "UTF-8", "GB2312"));
	$channel = \lib\Channel::get($conf['transfer_wxpay']);
	if(!$channel)sysmsg(mb_convert_encoding("当前支付通道信息不存在", "UTF-8", "GB2312"));
	$wxinfo = \lib\Channel::getWeixin($channel['appwxmp']);
	if(!$wxinfo)sysmsg(mb_convert_encoding("支付通道绑定的微信公众号不存在", "UTF-8", "GB2312"));

	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' and type=2 order by id asc");
	$i=0;
	$table="商家明细单号（必填）,收款用户openid（必填）,收款用户姓名（选填）,收款用户身份证（选填）,转账金额（必填，单位：元）,转账备注（必填）\r\n";
	$allmoney = 0;
	while($row = $rs->fetch())
	{
		$i++;
		$table.=$batch.$i.','.$row['account'].','.text_encoding($row['username']).',,'.$row['realmoney'].','.$remark."\r\n";
		$allmoney+=$row['realmoney'];
	}

	$data="微信支付批量转账到零钱模版（勿删）\r\n";
	$data.="商家批次单号（必填）,".$batch."\r\n";
	$data.="批次名称（必填）,批量转账".$batch."\r\n";
	$data.="转账appid（必填）,".$wxinfo['appid']."\r\n";
	$data.="转账总金额（必填，单位：元）,".$allmoney."\r\n";
	$data.="转账总笔数（必填）,".$i."\r\n";
	$data.="批次备注（必填）,批量转账".$batch."\r\n";
	$data.=",\r\n";
	$data.="转账明细（勿删）\r\n";
	$data.=$table;

}else{
	$data="序号,收款方式,收款账号,收款人姓名,付款金额（元）,付款理由\r\n";
	$rs=$DB->query("SELECT * from pre_settle where batch='$batch' order by type asc,id asc");
	$i=0;
	while($row = $rs->fetch())
	{
		$i++;
		$data.=$i.','.display_type($row['type']).','.$row['account'].','.text_encoding($row['username']).','.$row['realmoney'].','.$remark."\r\n";
	}

}

$file_name='pay_'.$type.'_'.$batch.'.csv';
$file_size=strlen($data);
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $data;
break;

case 'ustat':
$startday = trim($_GET['startday']);
$endday = trim($_GET['endday']);
$method = trim($_GET['method']);
$type = intval($_POST['type']);
if(!$startday || !$endday)exit("<script language='javascript'>alert('param error');history.go(-1);</script>");
$data = [];
$columns = ['uid'=>'商户ID', 'total'=>'总计'];

if($method == 'type'){
	$paytype = [];
	$rs = $DB->getAll("SELECT id,name,showname FROM pre_type WHERE status=1");
	foreach($rs as $row){
		$paytype[$row['id']] = text_encoding($row['showname']);
		if($type == 4){
			$columns['type_'.$row['name']] = text_encoding($row['showname']);
		}else{
			$columns['type_'.$row['id']] = text_encoding($row['showname']);
		}
	}
	unset($rs);
}else{
	$channel = [];
	$rs = $DB->getAll("SELECT id,name FROM pre_channel WHERE status=1");
	foreach($rs as $row){
		$channel[$row['id']] = text_encoding($row['name']);
	}
	unset($rs);
}

if($type == 4){
	$rs=$DB->query("SELECT uid,type,channel,money from pre_transfer where status=1 and paytime>='$startday' and paytime<='$endday'");
	while($row = $rs->fetch())
	{
		$money = (float)$row['money'];
		if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
		$data[$row['uid']]['total'] += $money;
		if($method == 'type'){
			$ukey = 'type_'.$row['type'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
		}else{
			$ukey = 'channel_'.$row['channel'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
			if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
		}
	}
}else{
	$rs=$DB->query("SELECT uid,type,channel,money,realmoney,getmoney,profitmoney from pre_order where status=1 and date>='$startday' and date<='$endday'");
	while($row = $rs->fetch())
	{
		if($type == 3){
			$money = (float)$row['profitmoney'];
		}elseif($type == 2){
			$money = (float)$row['getmoney'];
		}elseif($type == 1){
			$money = (float)$row['realmoney'];
		}else{
			$money = (float)$row['money'];
		}
		if(!array_key_exists($row['uid'], $data)) $data[$row['uid']] = ['uid'=>$row['uid'], 'total'=>0];
		$data[$row['uid']]['total'] += $money;
		if($method == 'type'){
			$ukey = 'type_'.$row['type'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
		}else{
			$ukey = 'channel_'.$row['channel'];
			if(!array_key_exists($ukey, $data[$row['uid']])) $data[$row['uid']][$ukey] = $money;
			else $data[$row['uid']][$ukey] += $money;
			if(!in_array($ukey, $columns)) $columns[$ukey] = $channel[$row['channel']];
		}
	}
}
ksort($data);

$file='';
foreach($columns as $column){
	$file.=$column.',';
}
$file=substr($file,0,-1)."\r\n";
foreach($data as $row){
	foreach($columns as $key=>$column){
		if(!array_key_exists($key, $row))
			$file.='0,';
		else
			$file.=$row[$key].',';
	}
	$file=substr($file,0,-1)."\r\n";
}

$file_name='pay_'.$method.'_'.$startday.'_'.$endday.'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
break;

case 'order':
$starttime = trim($_GET['starttime']);
$endtime = trim($_GET['endtime']);
$uid = intval($_GET['uid']);
$type = intval($_GET['type']);
$channel = intval($_GET['channel']);
$dstatus = intval($_GET['dstatus']);

$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type");
foreach($rs as $row){
	$paytype[$row['id']] = text_encoding($row['showname']);
}
unset($rs);

$sql=" 1=1";
if(!empty($uid)) {
	$sql.=" AND A.`uid`='$uid'";
}
if(!empty($type)) {
	$sql.=" AND A.`type`='$type'";
}elseif(!empty($channel)) {
	$sql.=" AND A.`channel`='$channel'";
}
if($dstatus>-1) {
	$sql.=" AND A.status={$dstatus}";
}
if(!empty($starttime)){
	$starttime = date("Y-m-d H:i:s", strtotime($starttime.' 00:00:00'));
	$sql.=" AND A.addtime>='{$starttime}'";
}
if(!empty($endtime)){
	$endtime = date("Y-m-d H:i:s", strtotime("+1 days", strtotime($endtime.' 00:00:00')));
	$sql.=" AND A.addtime<'{$endtime}'";
}

$file="系统订单号,商户订单号,接口订单号,商户号,网站域名,商品名称,订单金额,实际支付,商户分成,支付方式,支付通道ID,支付插件,支付账号,支付IP,创建时间,完成时间,支付状态\r\n";

$rs = $DB->query("SELECT A.*,B.plugin FROM pre_order A LEFT JOIN pre_channel B ON A.channel=B.id WHERE{$sql} order by trade_no desc limit 100000");
while($row = $rs->fetch()){
	$file.='="'.$row['trade_no'].'",="'.$row['out_trade_no'].'",="'.$row['api_trade_no'].'",'.$row['uid'].','.$row['domain'].','.text_encoding($row['name']).','.$row['money'].','.$row['realmoney'].','.$row['getmoney'].','.$paytype[$row['type']].','.$row['channel'].','.$row['plugin'].','.$row['buyer'].','.$row['ip'].','.$row['addtime'].','.$row['endtime'].','.display_status($row['status'])."\r\n";
}

$file_name='order_'.$starttime.'_'.$endtime.'.csv';
$file_size=strlen($file);
header("Content-Description: File Transfer");
header("Content-Type: application/force-download");
header("Content-Length: {$file_size}");
header("Content-Disposition:attachment; filename={$file_name}");
echo $file;
break;

case 'wximg':
	if(!checkRefererHost())exit();
	$channelid = intval($_GET['channel']);
	$media_id = $_GET['mediaid'];
	$channel=\lib\Channel::get($channelid);
	$model = \lib\Complain\CommUtil::getModel($channel);
	$image = $model->getImage($media_id);
	if($image !== false){
		$seconds_to_cache = 3600*24*7;
		header("Cache-Control: max-age=$seconds_to_cache");
		header("Content-Type: image/jpeg");
		echo $image;
	}
break;

default:
	exit('No Act');
break;
}