<?php
/**
 * 分账规则列表
**/
include("../includes/common.php");
$title='分账规则列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12">
<?php
$support_plugins = \lib\ProfitSharing\CommUtil::$plugins;
$channels = $DB->getAll("SELECT id,name,plugin FROM pre_channel WHERE plugin IN ('".implode("','",$support_plugins)."') ORDER BY id ASC");
$channel_select = '';
foreach($channels as $row){
	$channel_select .= '<option value="'.$row['id'].'" plugin="'.$row['plugin'].'">'.$row['id'].'__'.$row['name'].'</option>';
}

$link = '';
$sql = " 1";
if(isset($_GET['value']) && !empty($_GET['value'])) {
	$value=daddslashes($_GET['value']);
	$sql .= " AND A.`{$_GET['column']}`='{$value}'";
	$link .= '&column='.$_GET['column'].'&value='.urlencode($_GET['value']);
}
?>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
	<div class="modal-dialog">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">分账规则修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="id" id="id"/>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">支付通道</label>
						<div class="col-sm-9">
							<select name="channel" id="channel" class="form-control" onchange="changeChannel()">
								<option value="0">请选择支付通道</option><?php echo $channel_select; ?>
							</select>
							<font color="green">只支持alipay、alipaysl、wxpayn、wxpaynp支付插件</font>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">商户ID</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="uid" id="uid" placeholder="可留空，留空则为当前支付通道所有订单">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">接收方账号</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="account" id="account" placeholder="">
							<font color="green" id="account_note"></font>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">接收方姓名</label>
						<div class="col-sm-9">
							<input type="text" class="form-control" name="name" id="name" placeholder="可留空，不填写则不校验真实姓名">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">分账比例</label>
						<div class="col-sm-9">
						<div class="input-group"><input type="text" class="form-control" name="rate" id="rate" placeholder="填写1~100的数字"><span class="input-group-addon">%</span></div>
						<font color="green">一般限制最高30%（微信还需要减去手续费，例如手续费是0.6%，则填写29.4%）</font>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-3 control-label no-padding-right">订单最小金额</label>
						<div class="col-sm-9">
						<div class="input-group"><input type="text" class="form-control" name="minmoney" id="minmoney" placeholder="订单超过该金额才进行分账"><span class="input-group-addon">元</span></div>
						</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
				<button type="button" class="btn btn-primary" id="store" onclick="save()">保存</button>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-info">
   <div class="panel-heading"><h3 class="panel-title">分账规则列表</h3></div>
<div class="panel-body">
<form action="./ps_receiver.php" method="GET" class="form-inline">
  <div class="form-group">
    <label><b>搜索</b></label>
	<select name="column" class="form-control" default="<?php echo @$_GET['column']?>"><option value="channel">通道ID</option><option value="uid">商户ID</option><option value="account">接收方账号</option><option value="name">接收方姓名</option></select>
    <input type="text" class="form-control" name="value" placeholder="输入搜索内容" value="<?php echo @$_GET['value']?>">
	<button type="submit" class="btn btn-primary"><i class="fa fa-search"></i>&nbsp;搜索</button>&nbsp;
	<a href="javascript:addframe()" class="btn btn-success"><i class="fa fa-plus"></i>&nbsp;新增</a>&nbsp;
	<a href="?" class="btn btn-default" title="刷新列表"><i class="fa fa-refresh"></i></a>
  </div>
</form>
</div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>支付通道</th><th>商户ID</th><th>接收方账号/姓名</th><th>分账比例</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
$numrows=$DB->getColumn("SELECT count(*) from pre_psreceiver A WHERE{$sql}");
$pagesize=15;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT A.*,B.name channelname FROM pre_psreceiver A LEFT JOIN pre_channel B ON A.channel=B.id WHERE{$sql} order by A.id desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '<tr><td><b>'.$res['id'].'</b></td><td>'.$res['channel'].'__'.$res['channelname'].'</td><td><a href="./ulist.php?my=search&column=uid&value='.$res['uid'].'" target="_blank">'.$res['uid'].'</a></td><td>'.$res['account'].($res['name']?'／'.$res['name']:'').'</td><td>'.$res['rate'].'</td><td>'.($res['status']==1?'<a class="btn btn-xs btn-success" onclick="setStatus('.$res['id'].',0)">已开启</a>':'<a class="btn btn-xs btn-warning" onclick="setStatus('.$res['id'].',1)">已关闭</a>').'</td><td><a class="btn btn-xs btn-info" onclick="editframe('.$res['id'].')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['id'].')">删除</a>&nbsp;<a href="./ps_order.php?rid='.$res['id'].'" target="_blank" class="btn btn-xs btn-default">订单</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
<?php
echo'<center><ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li><a href="ps_receiver.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="ps_receiver.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li><a href="ps_receiver.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li><a href="ps_receiver.php?page='.$i.$link.'">'.$i .'</a></li>';
if ($page<$pages)
{
echo '<li><a href="ps_receiver.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="ps_receiver.php?page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo'</ul></center>';
?>
	</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增分账规则");
	$("#action").val("add");
	$("#id").val('');
	$("#channel").val(0);
	$("#uid").val('');
	$("#account").val('');
	$("#name").val('');
	$("#rate").val('');
	$("#minmoney").val('');
}
function changeChannel(){
	var channel = parseInt($("#channel").val());
	if(channel>0){
		var plugin = $("#channel option:selected").attr('plugin');
		if(plugin == 'wxpayn' || plugin == 'wxpaynp')
			$("#account_note").text('支持填写商户号或个人OpenId！OpenId获取地址：<?php echo $siteurl?>user/openid.php?channel='+channel);
		else if(plugin == 'alipay' || plugin == 'alipaysl')
			$("#account_note").text('支持填写支付宝UID（2088开头的16位数字）或支付宝账号');
		else
			$("#account_note").text('');
	}else{
		$("#account_note").text('');
	}
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_profitsharing.php?act=get_receiver&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改分账规则");
				$("#action").val("edit");
				$("#id").val(data.data.id);
				$("#channel").val(data.data.channel);
				$("#uid").val(data.data.uid);
				$("#account").val(data.data.account);
				$("#name").val(data.data.name);
				$("#rate").val(data.data.rate);
				$("#minmoney").val(data.data.minmoney);
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function save(){
	if($("#channel").val()=='0'||$("#account").val()==''){
		layer.alert('必填项不能为空！');return false;
	}
	var url = 'ajax_profitsharing.php?act=add_receiver';
	if($("#action").val() == 'edit') url = 'ajax_profitsharing.php?act=edit_receiver';
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : url,
		data : $("#form-store").serialize(),
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg,{
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
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function delItem(id) {
	var confirmobj = layer.confirm('你确实要删除此分账规则吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'POST',
		url : 'ajax_profitsharing.php?act=del_receiver',
		data : {id: id},
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
function setStatus(id,status) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_profitsharing.php?act=set_receiver',
		data : {id:id, status:status},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon: 1}, function(){layer.closeAll();window.location.reload()});
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function getAll(id, obj){
	var ii = layer.load();
	$.ajax({
		type : 'GET',
		url : 'ajax_profitsharing.php?act=getMoney&id='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$(obj).html(data.money);
			}else{
				layer.alert(data.msg, {icon: 2})
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	});
}
$(document).ready(function(){
	var items = $("select[default]");
	for (i = 0; i < items.length; i++) {
		if($(items[i]).attr("default")!=''){
			$(items[i]).val($(items[i]).attr("default"));
		}
	}
})
</script>