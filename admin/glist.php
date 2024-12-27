<?php
/**
 * 用户组设置
**/
include("../includes/common.php");
$title='用户组设置';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<style>
.table>tbody>tr>td{vertical-align: middle;}
</style>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-10 center-block" style="float: none;">
<?php

$paytype = [];
$rs = $DB->getAll("SELECT * FROM pre_type WHERE status=1 ORDER BY id ASC");
foreach($rs as $row){
	$paytype[$row['id']] = $row['showname'];
}
unset($rs);

function display_info($info){
	global $paytype;
	$result = '';
	$arr = json_decode($info, true);
	foreach($arr as $k=>$v){
		if($v['channel']==0)continue;
		$result .= $paytype[$k].'('.$v['channel'].'):'.$v['rate'].',';
	}
	return substr($result,0,-1);
}

$list = $DB->getAll("SELECT * FROM pre_group ORDER BY gid ASC");
?>
<div class="modal" id="modal-store" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content animated flipInX">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><span
							aria-hidden="true">&times;</span><span
							class="sr-only">Close</span></button>
				<h4 class="modal-title" id="modal-title">用户组修改/添加</h4>
			</div>
			<div class="modal-body">
				<form class="form-horizontal" id="form-store">
					<input type="hidden" name="action" id="action"/>
					<input type="hidden" name="gid" id="gid"/>
					<div class="row">
					<div class="col-sm-12 col-md-7">
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">显示名称</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="name" placeholder="不要与其他用户组名称重复">
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label">通道费率</label>
						<div class="col-sm-10">
							<table class="table">
							  <thead><tr><th style="min-width:100px">支付方式</th><th>选择支付通道</th><th>填写分成比例</th></tr></thead>
							  <tbody>
<?php
foreach($paytype as $key=>$value)
{
$select = '';
$rs = $DB->getAll("SELECT * FROM pre_channel WHERE type='$key' AND status=1");
foreach($rs as $row){
	$select .= '<option value="'.$row['id'].'" rate="'.$row['rate'].'" type="channel">'.$row['name'].'</option>';
}
$rs = $DB->getAll("SELECT * FROM pre_roll WHERE type='$key' AND status=1");
foreach($rs as $row){
	$select .= '<option value="'.$row['id'].'" rate="'.$row['rate'].'" type="roll">'.$row['name'].'</option>';
}
echo '<tr><td><b>'.$value.'</b><input type="hidden" name="info['.$key.'][type]" value=""></td><td><select name="info['.$key.'][channel]" class="form-control" onchange="changeChannel('.$key.')"><option value="0">关闭</option><option value="-1" type="channel">随机可用通道</option>'.$select.'<option value="-2" type="channel">用户自定义子通道</option></select></td><td><div class="input-group"><input type="text" class="form-control" name="info['.$key.'][rate]" placeholder="百分数"><span class="input-group-addon">%</span></div></td></tr>';
}
?>
							  </tbody>
							</table>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-2 control-label no-padding-right">用户变量</label>
						<div class="col-sm-10">
							<input type="text" class="form-control" name="settings" placeholder="没有请勿填写，格式：变量名1:显示名1,变量名2:显示名2">
						</div>
					</div>
					</div>
					<div class="col-sm-12 col-md-5">
					<div class="form-group">
						<label class="col-sm-4 control-label">结算开关</label>
						<div class="col-sm-8">
							<select name="config[settle_open]" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">只开启每日自动结算</option><option value="2">只开启手动申请结算</option><option value="3">开启自动+手动结算</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label">结算周期</label>
						<div class="col-sm-8">
							<select name="config[settle_type]" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">D+0（可结算全部余额）</option><option value="2">D+1（可结算前1天的余额）</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label">自动转账</label>
						<div class="col-sm-8">
							<select name="config[settle_transfer]" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">开启手动提现自动转账</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label no-padding-right">结算手续费</label>
						<div class="col-sm-8">
						<div class="input-group"><input type="text" name="config[settle_rate]" class="form-control" placeholder="留空则与系统设置一致"/><span class="input-group-addon">%</span></div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label">代付功能</label>
						<div class="col-sm-8">
							<select name="config[user_transfer]" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">开启</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label no-padding-right">代付手续费</label>
						<div class="col-sm-8">
						<div class="input-group"><input type="text" name="config[transfer_rate]" class="form-control" placeholder="留空则与系统设置一致"/><span class="input-group-addon">%</span></div>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label">邀请返现</label>
						<div class="col-sm-8">
							<select name="config[invite_open]" class="form-control">
								<option value="0">缺省（与系统设置一致）</option><option value="1">开启</option>
							</select>
						</div>
					</div>
					<div class="form-group">
						<label class="col-sm-4 control-label no-padding-right">邀请分成比例</label>
						<div class="col-sm-8">
						<div class="input-group"><input type="text" name="config[invite_rate]" class="form-control" placeholder="留空则与系统设置一致"/><span class="input-group-addon">%</span></div>
						</div>
					</div>
					</div>
					</div>
				</form>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-white" data-dismiss="modal">关闭</button>
				<button type="button" class="btn btn-primary" onclick="save()">保存</button>
			</div>
		</div>
	</div>
</div>

<div class="panel panel-success">
   <div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo count($list);?></b> 个用户组&nbsp;<span class="pull-right"><a href="javascript:addframe()" class="btn btn-default btn-xs"><i class="fa fa-plus"></i> 新增</a></span></h3></div>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>GID</th><th>用户组名称</th><th>通道与费率</th><th>操作</th></tr></thead>
          <tbody>
<?php
foreach($list as $res)
{
echo '<tr><td><b>'.$res['gid'].'</b></td><td>'.$res['name'].'</td><td>'.display_info($res['info']).'</td><td><a class="btn btn-xs btn-default" href="./ulist.php?gid='.$res['gid'].'">用户</a>&nbsp;<a class="btn btn-xs btn-info" onclick="editframe('.$res['gid'].')">编辑</a>&nbsp;<a class="btn btn-xs btn-danger" onclick="delItem('.$res['gid'].')">删除</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
	  <div class="panel-footer">
          <span class="glyphicon glyphicon-info-sign"></span> 未设置用户组的用户是默认用户组，会自动使用已添加的可用支付通道和通道默认费率
        </div>
	</div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function changeChannel(type){
	var rate = $("select[name='info["+type+"][channel]'] option:selected").attr('rate');
	var type2 = $("select[name='info["+type+"][channel]'] option:selected").attr('type');
	if($("input[name='info["+type+"][rate]']").val()=='')$("input[name='info["+type+"][rate]']").val(rate);
	$("input[name='info["+type+"][type]']").val(type2);
}
function addframe(){
	$("#modal-store").modal('show');
	$("#modal-title").html("新增用户组");
	$("#action").val("add");
	$("#gid").val('');
	$("input[name='name']").val('');
	$("input[name='settings']").val('');
	$("select[name='config[settle_open]']").val(0);
	$("select[name='config[settle_type]']").val(0);
	$("select[name='config[settle_transfer]']").val(0);
	$("input[name='config[settle_rate]']").val('');
	$("select[name='config[user_transfer]']").val(0);
	$("input[name='config[transfer_rate]']").val('');
	$("select[name='config[invite_open]']").val(0);
	$("input[name='config[invite_rate]']").val('');
}
function editframe(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=getGroup&gid='+id,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#modal-store").modal('show');
				$("#modal-title").html("修改用户组");
				$("#action").val("edit");
				$("#gid").val(data.gid);
				$("input[name='name']").val(data.name);
				$("input[name='settings']").val(data.settings);
				$("select[name='config[settle_open]']").val(data.config.settle_open || 0);
				$("select[name='config[settle_type]']").val(data.config.settle_type || 0);
				$("select[name='config[settle_transfer]']").val(data.config.settle_transfer || 0);
				$("input[name='config[settle_rate]']").val(data.config.settle_rate);
				$("select[name='config[user_transfer]']").val(data.config.user_transfer || 0);
				$("input[name='config[transfer_rate]']").val(data.config.transfer_rate);
				$("select[name='config[invite_open]']").val(data.config.invite_open || 0);
				$("input[name='config[invite_rate]']").val(data.config.invite_rate);
				$.each(data.info, function (i, res) {
					$("select[name='info["+i+"][channel]']").val(res.channel);
					$("input[name='info["+i+"][rate]']").val(res.rate);
					$("input[name='info["+i+"][type]']").val(res.type);
				})
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
	if($("#name").val()==''){
		layer.alert('请确保各项不能为空！');return false;
	}
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_user.php?act=saveGroup',
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
	if(id==0){
		layer.msg('系统自带默认用户组不支持删除');
		return false;
	}
	var confirmobj = layer.confirm('你确实要删除此用户组吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=delGroup&gid='+id,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				window.location.reload()
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
</script>