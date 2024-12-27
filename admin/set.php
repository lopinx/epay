<?php
/**
 * 系统设置
**/
include("../includes/common.php");
$title='系统设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
$mod=isset($_GET['mod'])?$_GET['mod']:null;
$mods=['site'=>'网站信息','pay'=>'支付相关','settle'=>'结算规则','transfer'=>'企业付款','oauth'=>'快捷登录','notice'=>'消息提醒','certificate'=>'实名认证','template'=>'首页模板','gonggao'=>'公告与排版','mail'=>'邮箱与短信','upimg'=>'LOGO设置','iptype'=>'IP地址','cron'=>'计划任务','proxy'=>'中转代理','account'=>'修改密码'];
?>
<ul class="nav nav-pills">
	<?php foreach($mods as $key=>$name){echo '<li class="'.($key==$mod?'active':null).'"><a href="set.php?mod='.$key.'">'.$name.'</a></li>';} ?>
</ul>
<?php
$conf=$CACHE->pre_fetch();
if($mod=='site'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">网站信息配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">网站名称</label>
	  <div class="col-sm-10"><input type="text" name="sitename" value="<?php echo $conf['sitename']; ?>" class="form-control" required/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">首页标题</label>
	  <div class="col-sm-10"><input type="text" name="title" value="<?php echo $conf['title']; ?>" class="form-control" required/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">关键字</label>
	  <div class="col-sm-10"><input type="text" name="keywords" value="<?php echo $conf['keywords']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">网站描述</label>
	  <div class="col-sm-10"><input type="text" name="description" value="<?php echo $conf['description']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">公司/组织名称</label>
	  <div class="col-sm-10"><input type="text" name="orgname" value="<?php echo $conf['orgname']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">回调专用网址</label>
	  <div class="col-sm-10"><input type="text" name="localurl" value="<?php echo $conf['localurl']; ?>" class="form-control" placeholder="留空则与本站网址一致"/><font color="green">必须以http://或https://开头，以/结尾，填错会导致订单无法回调</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户对接网址</label>
	  <div class="col-sm-10"><input type="text" name="apiurl" value="<?php echo $conf['apiurl']; ?>" class="form-control" placeholder="留空则与本站网址一致"/><font color="green">用户中心显示的支付对接地址，留空则与本站网址一致</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">联系邮箱</label>
	  <div class="col-sm-10"><input type="text" name="email" value="<?php echo $conf['email']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">客服ＱＱ</label>
	  <div class="col-sm-10"><input type="text" name="kfqq" value="<?php echo $conf['kfqq']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">加群链接</label>
	  <div class="col-sm-10"><input type="text" name="qqqun" value="<?php echo $conf['qqqun']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">APP下载链接</label>
	  <div class="col-sm-10"><input type="text" name="appurl" value="<?php echo $conf['appurl']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户验证方式</label>
	  <div class="col-sm-10"><select class="form-control" name="verifytype" default="<?php echo $conf['verifytype']?>"><option value="0">邮箱验证</option><option value="1">手机验证</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">开放注册</label>
	  <div class="col-sm-10"><select class="form-control" name="reg_open" default="<?php echo $conf['reg_open']?>"><option value="1">开启</option><option value="0">关闭</option><option value="2">仅邀请注册</option></select></div>
	</div><br/>
	<div id="setform1" style="<?php echo $conf['reg_open']==0?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-2 control-label">开启注册审核</label>
	  <div class="col-sm-10"><select class="form-control" name="user_review" default="<?php echo $conf['user_review']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">注册付费</label>
	  <div class="col-sm-10"><select class="form-control" name="reg_pay" default="<?php echo $conf['reg_pay']?>"><option value="1">开启</option><option value="0">关闭</option></select></div>
	</div><br/>
	<div class="form-group" style="<?php echo $conf['reg_pay']==0?'display:none;':null; ?>">
	  <label class="col-sm-2 control-label">注册付费金额</label>
	  <div class="col-sm-10"><input type="text" name="reg_pay_price" value="<?php echo $conf['reg_pay_price']; ?>" class="form-control"/></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户编辑自定义接口信息</label>
	  <div class="col-sm-10"><select class="form-control" name="user_settings_edit" default="<?php echo $conf['user_settings_edit']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">测试支付</label>
	  <div class="col-sm-10"><select class="form-control" name="test_open" default="<?php echo $conf['test_open']?>"><option value="1">开启</option><option value="0">关闭</option></select></div>
	</div><br/>
	<div id="setform3" style="<?php echo $conf['test_open']==0?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-2 control-label">测试支付收款商户ID</label>
	  <div class="col-sm-10"><input type="text" name="test_pay_uid" value="<?php echo $conf['test_pay_uid']; ?>" class="form-control" placeholder="填写在本站注册的商户UID"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">测试支付开启验证码</label>
	  <div class="col-sm-10"><select class="form-control" name="captcha_open_test" default="<?php echo $conf['captcha_open_test']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">极限验证码ID</label>
	  <div class="col-sm-10"><input type="text" name="captcha_id" value="<?php echo $conf['captcha_id']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">极限验证码密钥</label>
	  <div class="col-sm-10"><input type="text" name="captcha_key" value="<?php echo $conf['captcha_key']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">登录开启验证码</label>
	  <div class="col-sm-10"><select class="form-control" name="captcha_open_login" default="<?php echo $conf['captcha_open_login']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">密钥登录</label>
	  <div class="col-sm-10"><select class="form-control" name="close_keylogin" default="<?php echo $conf['close_keylogin']?>"><option value="1">关闭</option><option value="0">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户中心风格</label>
	  <div class="col-sm-10"><select class="form-control" name="user_style" default="<?php echo $conf['user_style']?>"><option value="0">黑色（1）</option><option value="1">黑色（2）</option><option value="2">棕色（1）</option><option value="3">棕色（2）</option><option value="4">蓝色（1）</option><option value="5">蓝色（2）</option><option value="6">紫色（1）</option><option value="7">紫色（2）</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">公共静态资源CDN</label>
	  <div class="col-sm-10"><select class="form-control" name="cdnpublic" default="<?php echo $conf['cdnpublic']?>">
	  <option value="4">字节跳动CDN</option>
	  <option value="0">南方科大CDN</option>
	  <option value="2">ZstaticCDN</option>
	  <option value="1">360CDN</option>
	  </select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">首页显示模式</label>
	  <div class="col-sm-10"><select class="form-control" name="homepage" default="<?php echo $conf['homepage']?>"><option value="0">默认显示首页</option><option value="1">直接跳转登录页面</option><option value="2">显示其它指定网址</option></select></div>
	</div><br/>
	<div class="form-group" id="setform4" style="<?php echo $conf['homepage']!=2?'display:none;':null; ?>">
	  <label class="col-sm-2 control-label">显示网址URL</label>
	  <div class="col-sm-10"><input type="text" name="homepage_url" value="<?php echo $conf['homepage_url']; ?>" class="form-control" placeholder="将以frame方式显示"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<script>
$("select[name='reg_open']").change(function(){
	if($(this).val() == 0){
		$("#setform1").hide();
	}else{
		$("#setform1").show();
	}
});
$("select[name='reg_pay']").change(function(){
	if($(this).val() == 1){
		$("#setform2").show();
	}else{
		$("#setform2").hide();
	}
});
$("select[name='test_open']").change(function(){
	if($(this).val() == 1){
		$("#setform3").show();
	}else{
		$("#setform3").hide();
	}
});
$("select[name='homepage']").change(function(){
	if($(this).val() == 2){
		$("#setform4").show();
	}else{
		$("#setform4").hide();
	}
});
</script>
<?php
}elseif($mod=='paypwd_n' && $_POST['do']=='submit'){
	if(!checkRefererHost())exit;
	$oldpwd=$_POST['oldpwd'];
	$newpwd=$_POST['newpwd'];
	$newpwd2=$_POST['newpwd2'];
	if(!empty($newpwd) && !empty($newpwd2)){
		if($oldpwd!=$conf['admin_paypwd'])showmsg('旧密码不正确！',3);
		if($newpwd!=$newpwd2)showmsg('两次输入的密码不一致！',3);
		saveSetting('admin_paypwd',$newpwd);
	}else{
		showmsg('新密码不能为空',3);
	}
	$ad=$CACHE->clear();
	if($ad)showmsg('修改成功！',1);
	else showmsg('修改失败！<br/>'.$DB->error(),4);
}elseif($mod=='account_n' && $_POST['do']=='submit'){
	if(!checkRefererHost())exit;
	$user=$_POST['user'];
	$oldpwd=$_POST['oldpwd'];
	$newpwd=$_POST['newpwd'];
	$newpwd2=$_POST['newpwd2'];
	if($user==null)showmsg('用户名不能为空！',3);
	saveSetting('admin_user',$user);
	if(!empty($newpwd) && !empty($newpwd2)){
		if($oldpwd!=$conf['admin_pwd'])showmsg('旧密码不正确！',3);
		if($newpwd!=$newpwd2)showmsg('两次输入的密码不一致！',3);
		saveSetting('admin_pwd',$newpwd);
	}
	$ad=$CACHE->clear();
	if($ad)showmsg('修改成功！请重新登录',1);
	else showmsg('修改失败！<br/>'.$DB->error(),4);
}elseif($mod=='account'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">管理员账号配置</h3></div>
<div class="panel-body">
  <form action="./set.php?mod=account_n" method="post" class="form-horizontal" role="form"><input type="hidden" name="do" value="submit"/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户名</label>
	  <div class="col-sm-10"><input type="text" name="user" value="<?php echo $conf['admin_user']; ?>" class="form-control" required/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">旧密码</label>
	  <div class="col-sm-10"><input type="password" name="oldpwd" value="" class="form-control" placeholder="请输入当前的管理员密码"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">新密码</label>
	  <div class="col-sm-10"><input type="password" name="newpwd" value="" class="form-control" placeholder="不修改请留空"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">重输密码</label>
	  <div class="col-sm-10"><input type="password" name="newpwd2" value="" class="form-control" placeholder="不修改请留空"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">支付密码修改</h3></div>
<div class="panel-body">
  <form action="./set.php?mod=paypwd_n" method="post" class="form-horizontal" role="form"><input type="hidden" name="do" value="submit"/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">旧密码</label>
	  <div class="col-sm-10"><input type="password" name="oldpwd" value="" class="form-control" placeholder="请输入当前的支付密码"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">新密码</label>
	  <div class="col-sm-10"><input type="password" name="newpwd" value="" class="form-control" placeholder="不修改请留空"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">重输密码</label>
	  <div class="col-sm-10"><input type="password" name="newpwd2" value="" class="form-control" placeholder="不修改请留空"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 支付密码用于转账接口以及API退款时使用，默认为123456
        </div>
</div>
<?php
}elseif($mod=='template'){
	$mblist = \lib\Template::getList();
?>
<style>.mblist{margin-bottom: 20px;} .mblist img{height: 110px;}</style>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">首页模板设置</h3></div>
<div class="panel-body">
  <h4>当前使用模板：</h4>
  <div class="row text-center">
	  <div class="col-xs-6 col-sm-4">
		<img class="img-responsive img-thumbnail img-rounded" src="/template/<?php echo $conf['template']?>/preview.png" onerror="this.src='/assets/img/NoImg.png'">
	  </div>
	  <div class="col-xs-6 col-sm-4">
		<p>模板名称：<?php echo $conf['template']?></p>
	  </div>
  </div>
  <hr/>
  <h4>更换模板：</h4>
  <div class="row text-center">
  <?php foreach($mblist as $template){?>
	  <div class="col-xs-6 col-sm-4 mblist">
		<a href="javascript:changeTemplate('<?php echo $template?>')"><img class="img-responsive img-thumbnail img-rounded" src="/template/<?php echo $template?>/preview.png" onerror="this.src='/assets/img/NoImg.png'" title="点击更换到该模板"><br/><?php echo $template?></a>
	  </div>
  <?php }?>
  </div>
</div>
</div>
<?php
}elseif($mod=='iptype'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">用户IP地址获取设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
    <div class="form-group">
	  <label class="col-sm-2 control-label">用户IP地址获取方式</label>
	  <div class="col-sm-10"><select class="form-control" name="ip_type" default="<?php echo $conf['ip_type']?>"><option value="0">0_X_FORWARDED_FOR</option><option value="1">1_X_REAL_IP</option><option value="2">2_REMOTE_ADDR</option></select></div>
	</div>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
此功能设置用于防止用户伪造IP请求。<br/>
X_FORWARDED_FOR：之前的获取真实IP方式，极易被伪造IP<br/>
X_REAL_IP：在网站使用CDN的情况下选择此项，在不使用CDN的情况下也会被伪造<br/>
REMOTE_ADDR：直接获取真实请求IP，无法被伪造，但可能获取到的是CDN节点IP<br/>
<b>你可以从中选择一个能显示你真实地址的IP，优先选下方的选项。</b>
</div>
</div>
<script>
$(document).ready(function(){
	$.ajax({
		type : "GET",
		url : "ajax.php?act=iptype",
		dataType : 'json',
		async: true,
		success : function(data) {
			$("select[name='ip_type']").empty();
			var defaultv = $("select[name='ip_type']").attr('default');
			$.each(data, function(k, item){
				$("select[name='ip_type']").append('<option value="'+k+'" '+(defaultv==k?'selected':'')+'>'+ item.name +' - '+ item.ip +' '+ item.city +'</option>');
			})
		}
	});
})
</script>
<?php
}elseif($mod=='pay'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">支付相关配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-3 control-label">最大支付金额</label>
	  <div class="col-sm-9"><input type="text" name="pay_maxmoney" value="<?php echo $conf['pay_maxmoney']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">最小支付金额</label>
	  <div class="col-sm-9"><input type="text" name="pay_minmoney" value="<?php echo $conf['pay_minmoney']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">商品屏蔽关键词</label>
	  <div class="col-sm-9"><input type="text" name="blockname" value="<?php echo $conf['blockname']; ?>" class="form-control"/><font color="green">多个关键词用|隔开。如果触发屏蔽会在<a href="./risk.php">风控记录</a>里面显示</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">商品屏蔽显示内容</label>
	  <div class="col-sm-9"><input type="text" name="blockalert" value="<?php echo $conf['blockalert']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">商品名称自定义</label>
	  <div class="col-sm-9"><input type="text" name="ordername" value="<?php echo $conf['ordername']; ?>" class="form-control" placeholder="默认使用原商品名称"/><font color="green">支持变量值：[name]原商品名称，[order]支付订单号，[outorder]商户订单号，[time]时间戳，[qq]当前商户的联系QQ，[phone]当前商户的手机号</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">扫码页面隐藏商品名称</label>
	  <div class="col-sm-9"><select class="form-control" name="pageordername" default="<?php echo $conf['pageordername']?>"><option value="0">否</option><option value="1">是</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">回调参数去除商品名称</label>
	  <div class="col-sm-9"><select class="form-control" name="notifyordername" default="<?php echo $conf['notifyordername']?>"><option value="0">否</option><option value="1">是</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">未填联系QQ禁止支付</label>
	  <div class="col-sm-9"><select class="form-control" name="forceqq" default="<?php echo $conf['forceqq']?>"><option value="0">否</option><option value="1">是</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">本站网址(支付宝专用)</label>
	  <div class="col-sm-9"><input type="text" name="localurl_alipay" value="<?php echo $conf['localurl_alipay']; ?>" class="form-control" placeholder="留空默认使用当前网址"/><font color="green">适用于网站有多个绑定域名，由于支付宝官方限制域名，使用未登记域名会有违约风险，填写指定网址后，使用支付宝支付都会跳转到该网址再跳转到支付宝。必须以http://或https://开头，以/结尾，留空则使用当前网址</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">本站网址(微信专用)</label>
	  <div class="col-sm-9"><input type="text" name="localurl_wxpay" value="<?php echo $conf['localurl_wxpay']; ?>" class="form-control" placeholder="留空默认使用当前网址"/><font color="green">适用于网站有多个绑定域名，由于微信公众号只能授权一个域名，填写指定网址后，使用微信公众号支付或获取openid都会跳转到该网址再跳转到微信。必须以http://或https://开头，以/结尾，留空则使用当前网址</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">开启余额充值</label>
	  <div class="col-sm-9"><select class="form-control" name="recharge" default="<?php echo $conf['recharge']?>"><option value="1">开启</option><option value="0">关闭</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">注册/充值/购买会员收款商户ID</label>
	  <div class="col-sm-9"><input type="text" name="reg_pay_uid" value="<?php echo $conf['reg_pay_uid']; ?>" class="form-control" placeholder="填写在本站注册的商户UID"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">开启聚合收款码</label>
	  <div class="col-sm-9"><select class="form-control" name="onecode" default="<?php echo $conf['onecode']?>"><option value="1">开启</option><option value="0">关闭</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">满多少随机增减金额</label>
	  <div class="col-sm-9"><input type="text" name="pay_payaddstart" value="<?php echo $conf['pay_payaddstart']; ?>" class="form-control" placeholder="订单满多少随机增减金额，留空不随机增减"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">随机增减金额范围</label>
	  <div class="col-sm-4"><input type="text" name="pay_payaddmin" value="<?php echo $conf['pay_payaddmin']; ?>" class="form-control" placeholder="最小增加金额，负数为减少"/></div>
	  <div class="col-sm-4"><input type="text" name="pay_payaddmax" value="<?php echo $conf['pay_payaddmax']; ?>" class="form-control" placeholder="最大增加金额，负数为减少"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">开启授权支付域名添加</label>
	  <div class="col-sm-9"><select class="form-control" name="pay_domain_open" default="<?php echo $conf['pay_domain_open']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">未授权支付域名禁止支付</label>
	  <div class="col-sm-9"><select class="form-control" name="pay_domain_forbid" default="<?php echo $conf['pay_domain_forbid']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">支付宝电脑网站支付使用扫码模式</label>
	  <div class="col-sm-9"><select class="form-control" name="alipay_paymode" default="<?php echo $conf['alipay_paymode']?>"><option value="0">关闭</option><option value="1">开启</option></select><font color="green">开启后，如果使用支付宝"电脑网站支付"，则直接显示二维码，这种模式下跳转回网站速度更快</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">支付宝手机网站支付前快捷登录</label>
	  <div class="col-sm-9"><select class="form-control" name="alipay_wappaylogin" default="<?php echo $conf['alipay_wappaylogin']?>"><option value="0">关闭</option><option value="1">开启</option></select><font color="green">开启后，可在支付前获取用户支付宝userid，用于黑名单屏蔽</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">开启商户后台自助退款</label>
	  <div class="col-sm-9"><select class="form-control" name="user_refund" default="<?php echo $conf['user_refund']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">微信小程序支付跳转小程序页面路径</label>
	  <div class="col-sm-9"><input type="text" name="wxminipay_path" value="<?php echo $conf['wxminipay_path']; ?>" class="form-control" placeholder=""/><font color="green">留空为使用旧版小程序源码，填写后为新版小程序源码，例如pages/pay/order</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">限制每IP每天支付笔数</label>
	  <div class="col-sm-9"><input type="text" name="pay_iplimit" value="<?php echo $conf['pay_iplimit']; ?>" class="form-control" placeholder="留空为不限制"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">限制每支付账号每天支付笔数</label>
	  <div class="col-sm-9"><input type="text" name="pay_userlimit" value="<?php echo $conf['pay_userlimit']; ?>" class="form-control" placeholder="留空为不限制"/><font color="green">只支持支付宝JS支付与微信公众号支付</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">创建订单人机验证</label>
	  <div class="col-sm-9"><select class="form-control" name="pay_verify" default="<?php echo $conf['pay_verify']?>"><option value="0">关闭</option><option value="1">按规则开启</option><option value="2">指定商户开启</option><option value="3">全部商户开启</option></select><font color="green">在商户网站通过submit发起支付的时候，可先进行人机验证再创建订单，防止被刷订单，开启后无法通过mapi接口发起支付</font></div>
	</div><br/>
	<div id="pay_verify_form" style="<?php echo $conf['pay_verify']!=1?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-3 control-label">人机验证开启规则1</label>
	  <div class="col-sm-4"><div class="input-group"><input type="text" name="pay_verify_check_second" value="<?php echo $conf['pay_verify_check_second']; ?>" class="form-control" placeholder="检测商户多少秒内"/><span class="input-group-addon">秒</span></div></div>
	  <div class="col-sm-5"><div class="input-group"><input type="text" name="pay_verify_check_count" value="<?php echo $conf['pay_verify_check_count']; ?>" class="form-control" placeholder="超过多少个订单时"/><span class="input-group-addon">个订单</span></div></div>
	  <div class="col-sm-offset-3 col-sm-9"><div class="input-group"><span class="input-group-addon">成功率</span><input type="text" name="pay_verify_check_rate" value="<?php echo $conf['pay_verify_check_rate']; ?>" class="form-control" placeholder="成功率低于多少时（不填写则不启用该条规则）"/><span class="input-group-addon">%</span></div></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">人机验证开启规则2</label>
	  <div class="col-sm-9"><div class="input-group"><span class="input-group-addon">单个IP</span><input type="text" name="pay_verify_check_ip" value="<?php echo $conf['pay_verify_check_ip']; ?>" class="form-control" placeholder="1小时内连续未支付订单数量（不填写则不启用该条规则）"/><span class="input-group-addon">个订单</span></div></div>
	</div><br/>
	</div>
	<div id="pay_verify_form2" style="<?php echo $conf['pay_verify']!=2?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-3 control-label">开启人机验证的商户</label>
	  <div class="col-sm-9"><input type="text" name="pay_verify_check_uid" value="<?php echo $conf['pay_verify_check_uid']; ?>" class="form-control" placeholder="多个商户号用|隔开"/></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-3 control-label">人机验证类型</label>
	  <div class="col-sm-9"><select class="form-control" name="pay_verify_type" default="<?php echo $conf['pay_verify_type']?>"><option value="0">JS跳转验证（速度快）</option><option value="1">极验无感验证（速度慢）</option><option value="2">极验滑动验证（速度慢且体验差）</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">手续费最低扣除设置</label>
	  <div class="col-sm-4"><div class="input-group"><input type="text" name="payfee_lessthan" value="<?php echo $conf['payfee_lessthan']; ?>" class="form-control" placeholder="手续费低于多少元时"/><span class="input-group-addon">元</span></div></div>
	  <div class="col-sm-5"><div class="input-group"><input type="text" name="payfee_mincost" value="<?php echo $conf['payfee_mincost']; ?>" class="form-control" placeholder="最低扣除多少元"/><span class="input-group-addon">元</span></div></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">邀请返现功能</label>
	  <div class="col-sm-9"><select class="form-control" name="invite_open" default="<?php echo $conf['invite_open']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">邀请分成比例</label>
	  <div class="col-sm-9"><div class="input-group"><input type="text" name="invite_rate" value="<?php echo $conf['invite_rate']; ?>" class="form-control"/><span class="input-group-addon">%</span></div><font color="green">被邀请用户支付订单，给邀请者分成的比例，分成金额不会超过订单的手续费</font></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">支付风控检测设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
    <h4 style="text-align: center;">检测通道下连续未支付订单数量自动关闭支付通道</h4>
	<div class="form-group">
	  <label class="col-sm-3 control-label">功能开关</label>
	  <div class="col-sm-9"><select class="form-control" name="auto_check_channel" default="<?php echo $conf['auto_check_channel']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
  	<div class="form-group">
	  <label class="col-sm-3 control-label">参数设置</label>
	  <div class="col-sm-4"><div class="input-group"><input type="text" name="check_channel_second" value="<?php echo $conf['check_channel_second']; ?>" class="form-control" placeholder="检测多少秒内"/><span class="input-group-addon">秒</span></div></div>
	  <div class="col-sm-5"><div class="input-group"><input type="text" name="check_channel_failcount" value="<?php echo $conf['check_channel_failcount']; ?>" class="form-control" placeholder="连续未支付订单数量"/><span class="input-group-addon">个订单</span></div></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">发送提醒</label>
	  <div class="col-sm-9"><select class="form-control" name="check_channel_notice" default="<?php echo $conf['check_channel_notice']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<h4 style="text-align: center;">检测商户订单支付成功率自动关闭商户支付权限</h4>
	<div class="form-group">
	  <label class="col-sm-3 control-label">功能开关</label>
	  <div class="col-sm-9"><select class="form-control" name="auto_check_sucrate" default="<?php echo $conf['auto_check_sucrate']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
  	<div class="form-group">
	  <label class="col-sm-3 control-label">参数设置</label>
	  <div class="col-sm-4"><div class="input-group"><input type="text" name="check_sucrate_second" value="<?php echo $conf['check_sucrate_second']; ?>" class="form-control" placeholder="检测多少秒内"/><span class="input-group-addon">秒</span></div></div>
	  <div class="col-sm-5"><div class="input-group"><input type="text" name="check_sucrate_count" value="<?php echo $conf['check_sucrate_count']; ?>" class="form-control" placeholder="超过多少个订单时"/><span class="input-group-addon">个订单</span></div></div>
	  <div class="col-sm-offset-3 col-sm-9"><div class="input-group"><span class="input-group-addon">成功率</span><input type="text" name="check_sucrate_value" value="<?php echo $conf['check_sucrate_value']; ?>" class="form-control" placeholder="成功率低于多少时自动关闭商户支付权限"/><span class="input-group-addon">%</span></div></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">发送提醒</label>
	  <div class="col-sm-9"><select class="form-control" name="check_sucrate_notice" default="<?php echo $conf['check_sucrate_notice']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
开启后在计划任务设置会多出一个url，定时访问该url后才生效。自动关闭的商户会在<a href="./risk.php">风控记录</a>里面显示。
</div>
</div>
<script>
$("select[name='pay_verify']").change(function(){
	if($(this).val() == 1){
		$("#pay_verify_form").show();
		$("#pay_verify_form2").hide();
	}else if($(this).val() == 2){
		$("#pay_verify_form").hide();
		$("#pay_verify_form2").show();
	}else{
		$("#pay_verify_form").hide();
		$("#pay_verify_form2").hide();
	}
});
</script>
<?php
}elseif($mod=='settle'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">结算规则配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-3 control-label">结算总开关</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_open" default="<?php echo $conf['settle_open']?>"><option value="0">关闭结算功能</option><option value="1">只开启每日自动结算</option><option value="2">只开启手动申请结算</option><option value="3">开启自动+手动结算</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">结算周期</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_type" default="<?php echo $conf['settle_type']?>"><option value="0">D+0（可结算全部余额）</option><option value="1">D+1（可结算前1天的余额）</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">手动结算自动转账</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_transfer" default="<?php echo $conf['settle_transfer']?>"><option value="0">关闭</option><option value="1">开启</option></select><font color="green">如开启，需要先开启企业付款功能</font></div>
	</div><br/>
	<div id="settle_transfer_form" style="<?php echo $conf['settle_transfer']!=1?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-3 control-label">超出多少元不自动转账</label>
	  <div class="col-sm-9"><input type="text" name="settle_transfermax" value="<?php echo $conf['settle_transfermax']; ?>" class="form-control" placeholder="留空则不限制最大自动转账金额"/></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-3 control-label">每日手动申请次数限制</label>
	  <div class="col-sm-9"><input type="text" name="settle_maxlimit" value="<?php echo $conf['settle_maxlimit']; ?>" class="form-control" placeholder="留空则不限制每日手动申请结算次数"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">最低结算金额</label>
	  <div class="col-sm-9"><input type="text" name="settle_money" value="<?php echo $conf['settle_money']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">结算手续费</label>
	  <div class="col-sm-9"><div class="input-group"><input type="text" name="settle_rate" value="<?php echo $conf['settle_rate']; ?>" class="form-control"/><span class="input-group-addon">%</span></div></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">结算手续费最小</label>
	  <div class="col-sm-9"><input type="text" name="settle_fee_min" value="<?php echo $conf['settle_fee_min']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">结算手续费最大</label>
	  <div class="col-sm-9"><input type="text" name="settle_fee_max" value="<?php echo $conf['settle_fee_max']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">支付宝结算开关</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_alipay" default="<?php echo $conf['settle_alipay']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">微信结算开关</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_wxpay" default="<?php echo $conf['settle_wxpay']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">QQ钱包结算开关</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_qqpay" default="<?php echo $conf['settle_qqpay']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">银行卡结算开关</label>
	  <div class="col-sm-9"><select class="form-control" name="settle_bank" default="<?php echo $conf['settle_bank']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<script>
$("select[name='settle_transfer']").change(function(){
	if($(this).val() == 1){
		$("#settle_transfer_form").show();
	}else{
		$("#settle_transfer_form").hide();
	}
});
</script>
<?php
}elseif($mod=='gonggao'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">其他公告与排版设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">用户中心弹出公告</label>
	  <div class="col-sm-10"><textarea class="form-control" name="modal" rows="5" placeholder="不填写则不显示弹出公告"><?php echo $conf['modal']?></textarea></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">注册页面弹出公告</label>
	  <div class="col-sm-10"><textarea class="form-control" name="zhuce" rows="5" placeholder="不填写则不显示弹出公告"><?php echo $conf['zhuce']?></textarea></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">首页底部排版</label>
	  <div class="col-sm-10"><textarea class="form-control" name="footer" rows="3" placeholder="可填写备案号等"><?php echo $conf['footer']?></textarea></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/><br/>
	  <a href="./gonggao.php" class="btn btn-default btn-block">用户中心公告列表</a>
	 </div>
	</div>
  </form>
</div>
<?php
}elseif($mod=='transfer'){
	$alipay_channel = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%alipay%')");
	$wxpay_channel = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%wxpay%')");
	$qqpay_channel = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%qqpay%')");
	$bank_channel = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN (SELECT name FROM pre_plugin WHERE transtypes LIKE '%bank%')");
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">企业付款配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-3 control-label">付款方显示名称</label>
	  <div class="col-sm-9"><input type="text" name="transfer_name" value="<?php echo $conf['transfer_name']; ?>" class="form-control" placeholder="支付宝转账时显示"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">付款默认备注</label>
	  <div class="col-sm-9"><input type="text" name="transfer_desc" value="<?php echo $conf['transfer_desc']; ?>" class="form-control" placeholder="微信和QQ转账时显示"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">支付宝转账接口通道</label>
	  <div class="col-sm-9"><select class="form-control" name="transfer_alipay" default="<?php echo $conf['transfer_alipay']?>"><option value="0">关闭</option><?php foreach($alipay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?></select><font color="green">请先添加支付插件为alipay的支付通道。需签约“转账到支付宝账户”产品。</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">微信转账接口通道</label>
	  <div class="col-sm-9"><select class="form-control" name="transfer_wxpay" default="<?php echo $conf['transfer_wxpay']?>"><option value="0">关闭</option><?php foreach($wxpay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?></select><font color="green">请先添加支付插件为wxpay或wxpayn的支付通道。需申请“企业付款”（V2插件）或“商家转账到零钱”（V3插件）产品。</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">QQ钱包转账接口通道</label>
	  <div class="col-sm-9"><select class="form-control" name="transfer_qqpay" default="<?php echo $conf['transfer_qqpay']?>"><option value="0">关闭</option><?php foreach($qqpay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?></select><font color="green">请先添加支付插件为qqpay的支付通道。需申请“企业付款”产品。</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">银行卡转账接口通道</label>
	  <div class="col-sm-9"><select class="form-control" name="transfer_bank" default="<?php echo $conf['transfer_bank']?>"><option value="0">关闭</option><?php foreach($bank_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?></select><font color="green">请先添加支付插件为alipay的支付通道。需签约“转账到银行卡”产品。或wxpay插件，签约“付款到银行卡”。部分其他插件也有付款到银行卡功能</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">用户中心代付功能</label>
	  <div class="col-sm-9"><select class="form-control" name="user_transfer" default="<?php echo $conf['user_transfer']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">最小代付金额限制</label>
	  <div class="col-sm-9"><input type="text" name="transfer_minmoney" value="<?php echo $conf['transfer_minmoney']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">最大代付金额限制</label>
	  <div class="col-sm-9"><input type="text" name="transfer_maxmoney" value="<?php echo $conf['transfer_maxmoney']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">每日对单人最大代付笔数限制</label>
	  <div class="col-sm-9"><input type="text" name="transfer_maxlimit" value="<?php echo $conf['transfer_maxlimit']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">代付手续费</label>
	  <div class="col-sm-9"><div class="input-group"><input type="text" name="transfer_rate" value="<?php echo $conf['transfer_rate']; ?>" class="form-control" placeholder="留空则与结算手续费一样"/><span class="input-group-addon">%</span></div></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<?php
}elseif($mod=='certificate'){
	$alipay_channel = $DB->getAll("SELECT * FROM pre_channel WHERE plugin='alipay'");
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">实名认证接口配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
    <div class="form-group">
	  <label class="col-sm-3 control-label">是否开启实名认证</label>
	  <div class="col-sm-9"><select class="form-control" name="cert_open" default="<?php echo $conf['cert_open']?>"><option value="0">关闭</option><option value="1">支付宝身份验证</option><option value="3">支付宝实名信息验证</option><option value="5">阿里云金融级实人认证</option><option value="4">微信扫码实名认证</option><option value="2">手机号三要素实名认证</option></select></div>
	</div><br/>
	<div id="setform2" style="<?php echo $conf['cert_open']!=1&&$conf['cert_open']!=3?'display:none;':null; ?>">
    <div class="form-group">
	  <label class="col-sm-3 control-label">支付宝通道选择</label>
	  <div class="col-sm-9"><select class="form-control" name="cert_channel" default="<?php echo $conf['cert_channel']?>"><option value="0">关闭</option><?php foreach($alipay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?></select><font color="green">请先添加支付插件为alipay的支付通道</font></div>
	</div><br/>
	</div>
	<div id="setform3" style="<?php echo $conf['cert_open']!=2?'display:none;':null; ?>">
    <div class="form-group">
	  <label class="col-sm-3 control-label">APPCODE</label>
	  <div class="col-sm-9"><input type="text" name="cert_appcode" value="<?php echo $conf['cert_appcode']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	</div>
	<div id="setform4" style="<?php echo $conf['cert_open']!=4?'display:none;':null; ?>">
    <div class="form-group">
	  <label class="col-sm-3 control-label">腾讯云SecretId</label>
	  <div class="col-sm-9"><input type="text" name="cert_qcloudid" value="<?php echo $conf['cert_qcloudid']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">腾讯云SecretKey</label>
	  <div class="col-sm-9"><input type="text" name="cert_qcloudkey" value="<?php echo $conf['cert_qcloudkey']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	</div>
	<div id="setform5" style="<?php echo $conf['cert_open']!=5?'display:none;':null; ?>">
    <div class="form-group">
	  <label class="col-sm-3 control-label">阿里云AccessKeyId</label>
	  <div class="col-sm-9"><input type="text" name="cert_aliyunid" value="<?php echo $conf['cert_aliyunid']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">阿里云AccessKeySecret</label>
	  <div class="col-sm-9"><input type="text" name="cert_aliyunkey" value="<?php echo $conf['cert_aliyunkey']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">阿里云认证场景ID</label>
	  <div class="col-sm-9"><input type="text" name="cert_aliyunsceneid" value="<?php echo $conf['cert_aliyunsceneid']; ?>" class="form-control" placeholder="阿里云金融级实人认证-接入设置里面"/></div>
	</div><br/>
	</div>
	<div id="setform1" style="<?php echo $conf['cert_open']==0?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-3 control-label">开启企业认证方式</label>
	  <div class="col-sm-9"><select class="form-control" name="cert_corpopen" default="<?php echo $conf['cert_corpopen']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div id="setform6" style="<?php echo $conf['cert_corpopen']!=1?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-3 control-label">企业信息校验接口APPCODE</label>
	  <div class="col-sm-9"><input type="text" name="cert_appcode2" value="<?php echo $conf['cert_appcode2']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-3 control-label">商户强制认证</label>
	  <div class="col-sm-9"><select class="form-control" name="cert_force" default="<?php echo $conf['cert_force']?>"><option value="0">关闭</option><option value="1">开启</option></select><font color="green">开启后商户必须实名认证，才能正常使用支付接口收款</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">实名认证费用</label>
	  <div class="col-sm-9"><input type="text" name="cert_money" value="<?php echo $conf['cert_money']; ?>" class="form-control" placeholder="留空或0为免认证费用"/><font color="green">支付宝身份验证接口是1元/次。设置实名认证费用后，认证成功将从商户余额扣除，如果是付费注册商户建议免认证费</font></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
<br/><b>支付宝身份验证：</b><a href="https://b.alipay.com/signing/productDetailV2.htm?productId=I1080300001000010588" target="_blank" rel="noreferrer">申请地址</a>，该接口费用1元/人，支持人脸识别，同一个人重复验证不重复收费
<br/><b>支付宝实名信息验证：</b><a href="https://opendocs.alipay.com/open/repo-00hddl" target="_blank" rel="noreferrer">申请地址</a>，该接口完全免费，授权回调地址填写：<?php echo $siteurl.'user/oauth.php';?>
<br/><b>阿里云金融级实人认证：</b><a href="https://www.aliyun.com/product/cloudauth" target="_blank" rel="noreferrer">申请地址</a>｜<a href="https://usercenter.console.aliyun.com/#/manage/ak" target="_blank" rel="noreferrer">获取密钥</a>，该接口费用1元/人，支持人脸识别，同一个人重复验证不重复收费。无需签约，只需要阿里云企业认证账号即可开通。
<br/><b>微信扫码实名认证：</b><a href="https://cloud.tencent.com/product/faceid" target="_blank" rel="noreferrer">申请地址</a>｜<a href="https://console.cloud.tencent.com/cam/capi" target="_blank" rel="noreferrer">获取密钥</a>，接口0.3元/次
<br/><b>手机号三要素实名认证：</b><a href="https://market.aliyun.com/products/57000002/cmapi031847.html" target="_blank" rel="noreferrer">点击进入</a>
<br/><b>企业信息校验接口：</b><a href="https://market.aliyun.com/products/56928005/cmapi00043309.html" target="_blank" rel="noreferrer">点击进入</a>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">OCR文字识别接口配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
   <div class="form-group">
	  <label class="col-sm-3 control-label">阿里云AccessKeyId</label>
	  <div class="col-sm-9"><input type="text" name="ocr_aliyunid" value="<?php echo $conf['ocr_aliyunid']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">阿里云AccessKeySecret</label>
	  <div class="col-sm-9"><input type="text" name="ocr_aliyunkey" value="<?php echo $conf['ocr_aliyunkey']; ?>" class="form-control" placeholder=""/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
<br/><b>阿里云OCR文字识别：</b><a href="https://ai.aliyun.com/ocr" target="_blank" rel="noreferrer">开通地址</a>，需开通“个人证照识别”、“企业资质识别”
</div>
</div>
<script>
$("select[name='cert_open']").change(function(){
	if($(this).val() > 0){
		$("#setform1").show();
		if($(this).val() == 2){
			$("#setform2").hide();
			$("#setform3").show();
			$("#setform4").hide();
			$("#setform5").hide();
		}else if($(this).val() == 4){
			$("#setform2").hide();
			$("#setform3").hide();
			$("#setform4").show();
			$("#setform5").hide();
		}else if($(this).val() == 5){
			$("#setform2").hide();
			$("#setform3").hide();
			$("#setform4").hide();
			$("#setform5").show();
		}else{
			$("#setform2").show();
			$("#setform3").hide();
			$("#setform4").hide();
			$("#setform5").hide();
		}
	}else{
		$("#setform1").hide();
		$("#setform2").hide();
		$("#setform3").hide();
		$("#setform4").hide();
		$("#setform5").hide();
	}
});
$("select[name='cert_corpopen']").change(function(){
	if($(this).val() == 1){
		$("#setform6").show();
	}else{
		$("#setform6").hide();
	}
});
</script>
<?php
}elseif($mod=='oauth'){
	$alipay_channel = $DB->getAll("SELECT * FROM pre_channel WHERE plugin='alipay'");
	$wxpay_channel = $DB->getAll("SELECT * FROM pre_weixin WHERE type=0");
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">快捷登录配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-3 control-label">QQ快捷登录</label>
	  <div class="col-sm-9"><select class="form-control" name="login_qq" default="<?php echo $conf['login_qq']?>"><option value="0">关闭</option><option value="1">QQ互联官方快捷登录</option><option value="2">手机QQ扫码登录</option><option value="3">彩虹聚合登录</option></select><a href="https://connect.qq.com" target="_blank" rel="noreferrer">QQ互联申请地址</a>，回调地址填写：<?php echo $siteurl.'user/connect.php';?></div>
	</div><br/>
	<div id="setform1" style="<?php echo $conf['login_qq']!=1?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-3 control-label">QQ快捷登录Appid</label>
	  <div class="col-sm-9"><input type="text" name="login_qq_appid" value="<?php echo $conf['login_qq_appid']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">QQ快捷登录Appkey</label>
	  <div class="col-sm-9"><input type="text" name="login_qq_appkey" value="<?php echo $conf['login_qq_appkey']; ?>" class="form-control"/></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-3 control-label">支付宝快捷登录</label>
	  <div class="col-sm-9"><select class="form-control" name="login_alipay" default="<?php echo $conf['login_alipay']?>"><option value="0">关闭</option><?php foreach($alipay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?><option value="-1">彩虹聚合登录</option></select><font color="green">请先添加支付插件为alipay的支付通道</font><br/><a href="https://openhome.alipay.com/platform/appManage.htm" target="_blank" rel="noreferrer">申请地址</a>，应用内添加功能"获取会员信息"，授权回调地址填写：<?php echo $siteurl.'user/oauth.php';?></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">微信快捷登录</label>
	  <div class="col-sm-9"><select class="form-control" name="login_wx" default="<?php echo $conf['login_wx']?>"><option value="0">关闭</option><?php foreach($wxpay_channel as $channel){echo '<option value="'.$channel['id'].'">'.$channel['name'].'</option>';} ?><option value="-1">彩虹聚合登录</option></select><font color="green">请先<a href="./pay_weixin.php" target="_blank">添加一个微信公众号</a>。需要服务号，并配置网页授权域名：<?php echo $_SERVER['HTTP_HOST'];?></font></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<?php if($conf['login_qq']==3 || $conf['login_alipay']==-1 || $conf['login_wx']==-1){?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">彩虹聚合登录配置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-3 control-label">聚合登录API接口地址</label>
	  <div class="col-sm-9"><input type="text" name="login_apiurl" value="<?php echo $conf['login_apiurl']; ?>" class="form-control" placeholder="API地址要以http://开头，以/结尾"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">聚合登录应用APPID</label>
	  <div class="col-sm-9"><input type="text" name="login_appid" value="<?php echo $conf['login_appid']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">聚合登录应用APPKEY</label>
	  <div class="col-sm-9"><input type="text" name="login_appkey" value="<?php echo $conf['login_appkey']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<?php }?>
<script>
$("select[name='login_qq']").change(function(){
	if($(this).val() == 1){
		$("#setform1").show();
	}else if($(this).val() == 2){
		$("#setform1").hide();
	}else{
		$("#setform1").hide();
	}
});
</script>
<?php
}elseif($mod=='mailtest'){
	$mail_name = $conf['mail_recv']?$conf['mail_recv']:$conf['mail_name'];
	if(!empty($mail_name)){
	$result=send_mail($mail_name,'邮件发送测试。','这是一封测试邮件！<br/><br/>来自：'.$siteurl);
	if($result==1)
		showmsg('邮件发送成功！',1);
	else
		showmsg('邮件发送失败！'.$result,3);
	}
	else
		showmsg('您还未设置邮箱！',3);
}elseif($mod=='mail'){
	$wxpay_channel = $DB->getAll("SELECT * FROM pre_weixin WHERE type=0");
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">发信邮箱设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">发信模式</label>
	  <div class="col-sm-10"><select class="form-control" name="mail_cloud" default="<?php echo $conf['mail_cloud']?>"><option value="0">SMTP发信</option><option value="1">搜狐Sendcloud</option><option value="2">阿里云邮件推送</option></select></div>
	</div><br/>
	<div id="frame_set1" style="<?php echo $conf['mail_cloud']>1?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-2 control-label">SMTP服务器</label>
	  <div class="col-sm-10"><input type="text" name="mail_smtp" value="<?php echo $conf['mail_smtp']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">SMTP端口</label>
	  <div class="col-sm-10"><input type="text" name="mail_port" value="<?php echo $conf['mail_port']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">邮箱账号</label>
	  <div class="col-sm-10"><input type="text" name="mail_name" value="<?php echo $conf['mail_name']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">邮箱密码</label>
	  <div class="col-sm-10"><input type="text" name="mail_pwd" value="<?php echo $conf['mail_pwd']; ?>" class="form-control"/></div>
	</div><br/>
	</div>
	<div id="frame_set2" style="<?php echo $conf['mail_cloud']==0?'display:none;':null; ?>">
	<div class="form-group">
	  <label class="col-sm-2 control-label">API_USER</label>
	  <div class="col-sm-10"><input type="text" name="mail_apiuser" value="<?php echo $conf['mail_apiuser']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">API_KEY</label>
	  <div class="col-sm-10"><input type="text" name="mail_apikey" value="<?php echo $conf['mail_apikey']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">发信邮箱</label>
	  <div class="col-sm-10"><input type="text" name="mail_name2" value="<?php echo $conf['mail_name2']; ?>" class="form-control"/></div>
	</div><br/>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">收信邮箱</label>
	  <div class="col-sm-10"><input type="text" name="mail_recv" value="<?php echo $conf['mail_recv']; ?>" class="form-control" placeholder="不填默认为发信邮箱"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/><?php if($conf['mail_name']){?>[<a href="set.php?mod=mailtest">给 <?php echo $conf['mail_recv']?$conf['mail_recv']:$conf['mail_name']?> 发一封测试邮件</a>]<?php }?>
	 </div><br/>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
使用普通模式发信时，建议使用QQ邮箱，SMTP服务器smtp.qq.com，端口465，密码不是QQ密码也不是邮箱独立密码，是QQ邮箱设置界面生成的<a href="https://service.mail.qq.com/detail/0/75"  target="_blank" rel="noreferrer">授权码</a>。<br/>阿里云邮件推送：<a href="https://www.aliyun.com/product/directmail" target="_blank" rel="noreferrer">点此进入</a>｜<a href="https://usercenter.console.aliyun.com/#/manage/ak" target="_blank" rel="noreferrer">获取AK/SK</a>
</div>
</div>
<script>
$("select[name='mail_cloud']").change(function(){
	if($(this).val() == 0){
		$("#frame_set1").show();
		$("#frame_set2").hide();
	}else{
		$("#frame_set1").hide();
		$("#frame_set2").show();
	}
});
</script>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">短信接口设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">接口选择</label>
	  <div class="col-sm-10"><select class="form-control" name="sms_api" default="<?php echo $conf['sms_api']?>"><option value="0">企信通短信接口</option><option value="1">腾讯云短信接口</option><option value="2">阿里云短信接口</option><option value="3">ThinkAPI短信接口</option><option value="4">短信宝短信接口</option></select></div>
	</div><br/>
	<div class="form-group" id="showAppId" style="<?php echo $conf['sms_api']==0||$conf['sms_api']==3?'display:none;':null; ?>">
	  <label class="col-sm-2 control-label">AppId</label>
	  <div class="col-sm-10"><input type="text" name="sms_appid" value="<?php echo $conf['sms_appid']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">AppKey</label>
	  <div class="col-sm-10"><input type="text" name="sms_appkey" value="<?php echo $conf['sms_appkey']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group" id="showSign" style="<?php echo $conf['sms_api']==0?'display:none;':null; ?>">
	  <label class="col-sm-2 control-label">短信签名内容</label>
	  <div class="col-sm-10"><input type="text" name="sms_sign" value="<?php echo $conf['sms_sign']; ?>" class="form-control"/><font color="green">必须是已添加、并通过审核的短信签名。</font></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">商户注册模板ID</label>
	  <div class="col-sm-10"><input type="text" name="sms_tpl_reg" value="<?php echo $conf['sms_tpl_reg']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">找回密码模板ID</label>
	  <div class="col-sm-10"><input type="text" name="sms_tpl_find" value="<?php echo $conf['sms_tpl_find']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">修改结算账号模板ID</label>
	  <div class="col-sm-10"><input type="text" name="sms_tpl_edit" value="<?php echo $conf['sms_tpl_edit']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div><br/>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
腾讯云短信接口：<a href="https://console.cloud.tencent.com/sms/smslist" target="_blank" rel="noreferrer">点此进入</a><br/>阿里云短信接口：<a href="https://dysms.console.aliyun.com/dysms.htm" target="_blank" rel="noreferrer">点此进入</a><br/>ThinkAPI短信接口：<a href="https://www.kancloud.cn/topthink-doc/think-api/2203721" target="_blank" rel="noreferrer">点此进入</a>，签名内容处填写签名ID<br/>企信通短信接口：<a href="http://sms.php.gs/" target="_blank" rel="noreferrer">点此进入</a><br/>
短信宝短信接口：<a href="https://www.smsbao.com/" target="_blank" rel="noreferrer">点此进入</a>，模板ID处写模板内容（不含签名，验证码用{code}代替）
</div>
</div>
<script>
$("select[name='sms_api']").change(function(){
	if($(this).val() == 0){
		$("#showAppId").hide();
		$("#showSign").hide();
	}else if($(this).val() == 3){
		$("#showAppId").hide();
		$("#showSign").show();
	}else{
		$("#showAppId").show();
		$("#showSign").show();
	}
});
</script>
<?php
}elseif($mod=='notice'){
$errmsg = $CACHE->read('wxtplerrmsg');
if($errmsg){
	$arr = unserialize($errmsg);
	$errmsg = $arr['time'].' - '.$arr['errmsg'];
}

$errmsg2 = $CACHE->read('mailerrmsg');
if($errmsg2){
	$arr = unserialize($errmsg2);
	$errmsg2 = $arr['time'].' - '.$arr['errmsg'];
}
?>
<style>.wxparam{display: inline;width: 100px; margin: 0 3px;}</style>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">微信公众号消息提醒设置</h3></div>
<div class="panel-body">
<?php if($errmsg){?><div class="alert alert-warning">上一次报错信息：<?php echo $errmsg;?></div><?php }?>
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">公众号消息开关</label>
	  <div class="col-sm-10"><select class="form-control" name="wxnotice" default="<?php echo $conf['wxnotice']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">新订单通知模板ID</label>
	  <div class="col-sm-10"><input type="text" name="wxnotice_tpl_order" value="<?php echo $conf['wxnotice_tpl_order']; ?>" class="form-control" placeholder="留空则不开启此消息"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">新订单通知模板参数</label>
	  <div class="col-sm-10"><input type="text" name="wxnotice_tpl_order_no" value="<?php echo $conf['wxnotice_tpl_order_no']; ?>" class="form-control wxparam" placeholder="订单编号"/><input type="text" name="wxnotice_tpl_order_name" value="<?php echo $conf['wxnotice_tpl_order_name']; ?>" class="form-control wxparam" placeholder="商品名称"/><input type="text" name="wxnotice_tpl_order_money" value="<?php echo $conf['wxnotice_tpl_order_money']; ?>" class="form-control wxparam" placeholder="订单金额"/><input type="text" name="wxnotice_tpl_order_time" value="<?php echo $conf['wxnotice_tpl_order_time']; ?>" class="form-control wxparam" placeholder="交易时间"/><input type="text" name="wxnotice_tpl_order_outno" value="<?php echo $conf['wxnotice_tpl_order_outno']; ?>" class="form-control wxparam" placeholder="外部订单号"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">结算通知模板ID</label>
	  <div class="col-sm-10"><input type="text" name="wxnotice_tpl_settle" value="<?php echo $conf['wxnotice_tpl_settle']; ?>" class="form-control" placeholder="留空则不开启此消息"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">结算通知模板参数</label>
	  <div class="col-sm-10"><input type="text" name="wxnotice_tpl_settle_type" value="<?php echo $conf['wxnotice_tpl_settle_type']; ?>" class="form-control wxparam" placeholder="交易类型"/><input type="text" name="wxnotice_tpl_settle_account" value="<?php echo $conf['wxnotice_tpl_settle_account']; ?>" class="form-control wxparam" placeholder="结算账号"/><input type="text" name="wxnotice_tpl_settle_money" value="<?php echo $conf['wxnotice_tpl_settle_money']; ?>" class="form-control wxparam" placeholder="结算金额"/><input type="text" name="wxnotice_tpl_settle_realmoney" value="<?php echo $conf['wxnotice_tpl_settle_realmoney']; ?>" class="form-control wxparam" placeholder="实际到账"/><input type="text" name="wxnotice_tpl_settle_time" value="<?php echo $conf['wxnotice_tpl_settle_time']; ?>" class="form-control wxparam" placeholder="结算时间"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">登录通知模板ID</label>
	  <div class="col-sm-10"><input type="text" name="wxnotice_tpl_login" value="<?php echo $conf['wxnotice_tpl_login']; ?>" class="form-control" placeholder="留空则不开启此消息"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">登录通知模板参数</label>
	  <div class="col-sm-10"><input type="text" name="wxnotice_tpl_login_user" value="<?php echo $conf['wxnotice_tpl_login_user']; ?>" class="form-control wxparam" placeholder="登录帐号"/><input type="text" name="wxnotice_tpl_login_time" value="<?php echo $conf['wxnotice_tpl_login_time']; ?>" class="form-control wxparam" placeholder="登录时间"/><input type="text" name="wxnotice_tpl_login_name" value="<?php echo $conf['wxnotice_tpl_login_name']; ?>" class="form-control wxparam" placeholder="登录平台"/><input type="text" name="wxnotice_tpl_login_ip" value="<?php echo $conf['wxnotice_tpl_login_ip']; ?>" class="form-control wxparam" placeholder="IP地址"/><input type="text" name="wxnotice_tpl_login_iploc" value="<?php echo $conf['wxnotice_tpl_login_iploc']; ?>" class="form-control wxparam" placeholder="IP归属地"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div><br/>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
用于消息提醒的公众号，和快捷登录使用的公众号一样，也即必须同时开启微信快捷登录才可以使用该功能。<br/>
需申请模板消息的模板，不能用订阅消息！模板参数名类似于thingx、character_stringx、amountx等，在微信模板详情页面查看，无相关参数的可留空。如果不知道模板参数名怎么填写的，可先仔细阅读<a href="https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Template_Message_Interface.html#%E5%8F%91%E9%80%81%E6%A8%A1%E6%9D%BF%E6%B6%88%E6%81%AF" target="_blank">发送模板消息接口文档</a>。<br/>
<b>需要用户在用户中心绑定微信快捷登录，并手动开启才能收到。</b>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">邮件消息提醒设置</h3></div>
<div class="panel-body">
<?php if($errmsg2){?><div class="alert alert-warning">上一次报错信息：<?php echo $errmsg2;?></div><?php }?>
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
    <h4 style="text-align: center;">管理员接收邮件开关设置</h4>
	<div class="form-group">
	  <label class="col-sm-3 control-label">新注册商户待审核提醒</label>
	  <div class="col-sm-9"><select class="form-control" name="msgconfig_regaudit" default="<?php echo $conf['msgconfig_regaudit']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">商户手动提现提醒</label>
	  <div class="col-sm-9"><select class="form-control" name="msgconfig_apply" default="<?php echo $conf['msgconfig_apply']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">授权域名待审核提醒</label>
	  <div class="col-sm-9"><select class="form-control" name="msgconfig_domain" default="<?php echo $conf['msgconfig_domain']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<h4 style="text-align: center;">用户接收邮件开关设置</h4>
	<div class="form-group">
	  <label class="col-sm-3 control-label">邮件消息开关</label>
	  <div class="col-sm-9"><select class="form-control" name="mailnotice" default="<?php echo $conf['mailnotice']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">新订单通知</label>
	  <div class="col-sm-9"><select class="form-control" name="msgconfig_order" default="<?php echo $conf['msgconfig_order']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-3 control-label">结算通知</label>
	  <div class="col-sm-9"><select class="form-control" name="msgconfig_settle" default="<?php echo $conf['msgconfig_settle']?>"><option value="0">关闭</option><option value="1">开启</option></select></div>
	</div><br/>
  	<div class="form-group">
	  <div class="col-sm-offset-3 col-sm-9"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div><br/>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
<b>用户接收邮件的开关，除了在这里开启外，需要在用户中心手动开启才能收到。且用户只能选择一种消息接收方式。</b>
</div>
</div>
<?php
}elseif($mod=='cron'){
?>
<style>.list-group-item{word-break: break-all;}</style>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">计划任务设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">计划任务访问密钥</label>
	  <div class="col-sm-10"><input type="text" name="cronkey" value="<?php echo $conf['cronkey']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary form-control"/><br/>
	 </div>
	</div>
  </form>
</div>
</div>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">计划任务列表</h3></div>
<div class="panel-body">
<p>订单统计任务（0点后访问一次即可）</p>
<li class="list-group-item"><?php echo $siteurl?>cron.php?do=order&key=<?php echo $conf['cronkey']; ?></li>
<br/>
<p>自动生成结算任务（0点后访问一次即可）</p>
<li class="list-group-item"><?php echo $siteurl?>cron.php?do=settle&key=<?php echo $conf['cronkey']; ?></li>
<br/>
<p>订单异步通知重试任务<br/>（如果有订单出现通知失败的，可以通过此条任务自动重新通知，通知重试时间：1分钟，3分钟，20分钟，1小时，2小时）</p>
<li class="list-group-item"><?php echo $siteurl?>cron.php?do=notify&key=<?php echo $conf['cronkey']; ?></li>
<?php if($DB->getColumn("SELECT count(*) from pre_psreceiver WHERE status=1")>0){ ?>
<br/>
<p>订单分账任务</p>
<li class="list-group-item"><?php echo $siteurl?>cron.php?do=profitsharing&key=<?php echo $conf['cronkey']; ?></li><?php }?>
<?php if($conf['auto_check_sucrate']==1 || $conf['auto_check_channel']==1){ ?>
<br/>
<p>商户订单成功率检查任务</p>
<li class="list-group-item"><?php echo $siteurl?>cron.php?do=check&key=<?php echo $conf['cronkey']; ?></li><?php }?>
</div>
</div>
<?php
}
elseif($mod=='proxy'){
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">中转代理设置</h3></div>
<div class="panel-body">
  <form onsubmit="return saveSetting(this)" method="post" class="form-horizontal" role="form">
	<div class="form-group">
	  <label class="col-sm-2 control-label">中转代理开关</label>
	  <div class="col-sm-10"><select class="form-control" name="proxy" default="<?php echo $conf['proxy']?>">
	  <option value="0">关闭</option>
	  <option value="1">开启</option>
	  </select></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">代理IP</label>
	  <div class="col-sm-10"><input type="text" name="proxy_server" value="<?php echo $conf['proxy_server']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">代理端口</label>
	  <div class="col-sm-10"><input type="text" name="proxy_port" value="<?php echo $conf['proxy_port']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">代理账号</label>
	  <div class="col-sm-10"><input type="text" name="proxy_user" value="<?php echo $conf['proxy_user']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">代理密码</label>
	  <div class="col-sm-10"><input type="text" name="proxy_pwd" value="<?php echo $conf['proxy_pwd']; ?>" class="form-control"/></div>
	</div><br/>
	<div class="form-group">
	  <label class="col-sm-2 control-label">代理协议</label>
	  <div class="col-sm-10"><select class="form-control" name="proxy_type" default="<?php echo $conf['proxy_type']; ?>">
	  <option value="http">HTTP</option>
	  <option value="https">HTTPS</option>
	  <option value="sock4">SOCK4</option>
	  <option value="sock5">SOCK5</option>
	  </select></div>
	</div><br/>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="修改" class="btn btn-primary btn-block"/><br/>
	 </div>
	</div>
  </form>
</div>
<div class="panel-footer">
<span class="glyphicon glyphicon-info-sign"></span>
本功能开启后，在支付成功异步回调的时候，使用中转代理访问商户网站，可解决一些只能国内访问的网站回调问题，也可以防止本站服务器IP泄露。<br/>
自定义代理可以使用Windows服务器+CCProxy软件搭建<br/>
</div>
</div>
<?php
}elseif($mod=='upimg'){
echo '<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">更改首页LOGO</h3></div>
<div class="panel-body">';
if($_POST['s']==1){
if(!checkRefererHost())exit;
if(copy($_FILES['file']['tmp_name'], ROOT.'assets/img/logo.png')){
	echo "成功上传文件!<br>（可能需要清空浏览器缓存才能看到效果，按Ctrl+F5即可一键刷新缓存）";
}else{
	echo "上传失败，可能没有文件写入权限";
}
}
echo '<form action="set.php?mod=upimg" method="POST" enctype="multipart/form-data"><label for="file"></label><input type="file" name="file" id="file" /><input type="hidden" name="s" value="1" /><br><input type="submit" class="btn btn-primary btn-block" value="确认上传" /></form><br>现在的图片：<br><img src="../assets/img/logo.png?r='.rand(10000,99999).'" style="max-width:100%">';
echo '</div></div>';
}
?>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
function checkURL(obj)
{
	var url = $(obj).val();

	if (url.indexOf(" ")>=0){
		url = url.replace(/ /g,"");
	}
	if (url.toLowerCase().indexOf("http://")<0 && url.toLowerCase().indexOf("https://")<0){
		url = "http://"+url;
	}
	if (url.slice(url.length-1)!="/"){
		url = url+"/";
	}
	$(obj).val(url);
}
function saveSetting(obj){
	if($("input[name='localurl_alipay']").length>0 && $("input[name='localurl_alipay']").val()!=''){
		checkURL("input[name='localurl_alipay']");
	}
	if($("input[name='localurl_wxpay']").length>0 && $("input[name='localurl_wxpay']").val()!=''){
		checkURL("input[name='localurl_wxpay']");
	}
	if($("input[name='localurl']").length>0 && $("input[name='localurl']").val()!=''){
		checkURL("input[name='localurl']");
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax.php?act=set',
		data : $(obj).serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('设置保存成功！', {
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
	return false;
}
function changeTemplate(template){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax.php?act=set',
		data : {template:template},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert('更换模板成功！', {
					icon: 1,
					closeBtn: false
				}, function(){
				  window.location.reload()
				});
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
</script>