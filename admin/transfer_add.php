<?php
include("../includes/common.php");
$title='企业付款';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
$app = isset($_GET['app'])?$_GET['app']:'alipay';
if($app=='alipay'){
	$default_channel = $conf['transfer_alipay'];
}elseif($app=='wxpay'){
	$default_channel = $conf['transfer_wxpay'];
}elseif($app=='qqpay'){
	$default_channel = $conf['transfer_qqpay'];
}elseif($app=='bank'){
	$default_channel = $conf['transfer_bank'];
}else{
	showmsg('参数错误',4);
}

if(isset($_POST['submit'])){
	if(!checkRefererHost())exit();
	$out_biz_no = trim($_POST['out_biz_no']);
	if(!isset($_POST['paypwd']) || $_POST['paypwd']!==$conf['admin_paypwd'])showmsg('支付密码错误',3);
	$payee_account = htmlspecialchars(trim($_POST['payee_account']));
	$payee_real_name = htmlspecialchars(trim($_POST['payee_real_name']));
	$money = trim($_POST['money']);
	$desc = htmlspecialchars(trim($_POST['desc']));
	if(empty($out_biz_no) || empty($payee_account) || empty($money))showmsg('必填项不能为空',3);
	if(strlen($out_biz_no)!=19 || !is_numeric($out_biz_no))showmsg('交易号输入不规范',3);
	if($desc && mb_strlen($desc)>32)showmsg('转账备注最多32个字',3);
	if(!is_numeric($money) || !preg_match('/^[0-9.]+$/', $money) || $money<=0)showmsg('转账金额输入不规范',3);
	if ($app=='qqpay' && (!is_numeric($payee_account) || strlen($payee_account)<6 || strlen($payee_account)>10))showmsg('QQ号码格式错误',3);

	$channelid = isset($_POST['channel'])?$_POST['channel']:$default_channel;
	$channel = \lib\Channel::get($channelid);
	if(!$channel)showmsg('当前支付通道信息不存在',4);

	$result = \lib\Transfer::submit($app, $channel, $out_biz_no, $payee_account, $payee_real_name, $money, $desc);

	if($result['code']==0){
		$data = ['biz_no'=>$out_biz_no, 'uid'=>0, 'type'=>$app, 'channel'=>$channelid, 'account'=>$payee_account, 'username'=>$payee_real_name, 'money'=>$money, 'costmoney'=>$money, 'paytime'=>'NOW()', 'pay_order_no'=>$result['orderid'], 'status'=>$result['status'], 'desc'=>$desc];
		$DB->insert('transfer', $data);
		if($result['status'] == 1){
			$result='转账成功！转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
		}else{
			$result='提交成功！转账处理中，请稍后在付款记录页面查看结果。转账单据号:'.$result['orderid'].' 支付时间:'.$result['paydate'];
		}
		showmsg($result,1,'./transfer.php');
	}else{
		$result='转账失败：'.$result['msg'];
		showmsg($result,4);
	}
}

$out_biz_no = date("YmdHis").rand(11111,99999);

$channel_select = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%".$app."%')");
?>

	  <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">企业付款</h3></div>
        <div class="panel-body">
		<ul class="nav nav-tabs">
			<li class="<?php echo $app=='alipay'?'active':null;?>"><a href="?app=alipay">支付宝</a></li><li class="<?php echo $app=='wxpay'?'active':null;?>"><a href="?app=wxpay">微信</a></li><li class="<?php echo $app=='qqpay'?'active':null;?>"><a href="?app=qqpay">QQ钱包</a></li><li class="<?php echo $app=='bank'?'active':null;?>"><a href="?app=bank">银行卡</a></li>
		</ul>
		<div class="tab-pane active" id="alipay">
          <form action="?app=<?php echo $app?>" method="POST" role="form">
			<input type="hidden" name="type" value="<?php echo $app?>"/>
		    <div class="form-group">
				<div class="input-group"><div class="input-group-addon">通道选择</div>
				<select name="channel" class="form-control" default="<?php echo $default_channel?>">
					<?php foreach($channel_select as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].''.($channel['id']==$default_channel?'（默认）':'').'</option>';} ?>
				</select>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">交易号</div>
				<input type="text" name="out_biz_no" value="<?php echo $out_biz_no?>" class="form-control" required/>
			</div></div>
<?php if($app=='alipay'){?>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付宝账号</div>
				<input type="text" name="payee_account" value="" class="form-control" required placeholder="支付宝登录账号或支付宝UID或支付宝Openid"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付宝姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="不填写则不校验真实姓名"/>
			</div></div>
<?php }elseif($app=='wxpay'){?>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">Openid</div>
				<input type="text" name="payee_account" value="" class="form-control" required placeholder="只能填写微信Openid"/>
				<div class="input-group-btn"><a href="./gettoken.php?app=wechat" class="btn btn-default">获取</a></div>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">真实姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="不填写则不校验真实姓名"/>
			</div></div>
<?php }elseif($app=='qqpay'){?>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">收款方QQ</div>
				<input type="text" name="payee_account" value="" class="form-control" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">真实姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="不填写则不校验真实姓名"/>
			</div></div>
<?php }elseif($app=='bank'){?>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">银行卡号</div>
				<input type="text" name="payee_account" value="" class="form-control" required placeholder="收款方银行卡号"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">姓名</div>
				<input type="text" name="payee_real_name" value="" class="form-control" placeholder="收款方银行账户名称"/>
			</div></div>
<?php }?>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账金额</div>
				<input type="text" name="money" value="" class="form-control" placeholder="RMB/元" required/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">转账备注</div>
				<input type="text" name="desc" value="" class="form-control" placeholder="可留空，默认为：<?php echo $app=='alipay'?$conf['transfer_name']:$conf['transfer_desc']?>"/>
			</div></div>
			<div class="form-group">
				<div class="input-group"><div class="input-group-addon">支付密码</div>
				<input type="text" name="paypwd" value="" class="form-control" required/>
			</div></div>
            <p><input type="submit" name="submit" value="立即转账" class="btn btn-primary form-control"/></p>
			<p><a href="javascript:balanceQuery()" class="btn btn-block btn-default">查询账户余额</a></p>
          </form>
        </div>
		</div>
		<div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 交易号可以防止重复转账，同一个交易号只能提交同一次转账。<br/>
		  <a href="./set.php?mod=account">修改支付密码</a>
        </div>
      </div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
function balanceQuery(){
	var type = $("input[name=type]").val();
	var channel = $("select[name=channel]").val();
	if(channel == ''){
		layer.alert('请先选择通道');return;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_transfer.php?act=balance_query',
		dataType : 'json',
		data : {type: type, channel: channel},
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('账户可用余额：'+data.amount+'元');
			}else{
				if(data.msg.indexOf('插件方法不存在')>-1) data.msg = '该通道不支持查询账户余额';
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
</script>