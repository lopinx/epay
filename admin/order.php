<?php
/**
 * 订单列表
**/
include("../includes/common.php");
$title='订单列表';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");

$type_select = '<option value="0">所有支付方式</option>';
$rs = $DB->getAll("SELECT * FROM pre_type ORDER BY id ASC");
foreach($rs as $row){
	$type_select .= '<option value="'.$row['id'].'">'.$row['showname'].'</option>';
}
unset($rs);
?>
<style>
#orderItem .orderTitle{word-break:keep-all;}
#orderItem .orderContent{word-break:break-all;}
.dates{max-width: 120px;}
@media screen and (max-width: 767px) {
.table-responsive {
    overflow-y: auto;
}
}
.type-logo{width: 18px;margin-top: -2px;padding-right: 4px;}
</style>
<link href="../assets/css/datepicker.css" rel="stylesheet">
  <div class="container-fluid" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">

<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <input type="hidden" name="subchannel" value="">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="trade_no">订单号</option><option value="out_trade_no">商户订单号</option><option value="api_trade_no">接口订单号</option><option value="name">商品名称</option><option value="money">订单金额</option><option value="realmoney">实付金额</option><option value="getmoney">分成金额</option><option value="domain">网站域名</option><option value="buyer">支付账号</option><option value="ip">支付IP</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容" value="">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="uid" style="width: 100px;" placeholder="商户号" value="">
  </div>
  <div class="form-group">
    <select name="type" class="form-control"><?php echo $type_select?></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="channel" style="width: 80px;" placeholder="通道ID" value="">
  </div>
  <div class="input-group input-daterange">
	<input type="text" id="starttime" name="starttime" class="form-control dates" placeholder="开始日期" autocomplete="off" title="留空则不限时间范围">
	<span class="input-group-addon" onclick="$('#starttime').val('');$('#endtime').val('');" title="清除"><i class="fa fa-chevron-right"></i></span>
	<input type="text" id="endtime" name="endtime" class="form-control dates" placeholder="结束日期" autocomplete="off" title="留空则不限时间范围">
  </div>
  <div class="form-group">
	<select name="dstatus" class="form-control"><option value="-1">全部状态</option><option value="0">状态未支付</option><option value="1">状态已支付</option><option value="2">状态已退款</option><option value="3">状态已冻结</option><option value="4">状态预授权</option></select>
  </div>
  <button type="submit" class="btn btn-primary">&nbsp;搜索&nbsp;</button>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新订单列表"><i class="fa fa-refresh"></i></a>
  <div class="btn-group" role="group">
	<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">批量操作 <span class="caret"></span></button>
	<ul class="dropdown-menu"><li><a href="javascript:operation(0)">改未完成</a></li><li><a href="javascript:operation(1)">改已完成</a></li><li><a href="javascript:operation(2)">冻结订单</a></li><li><a href="javascript:operation(3)">解冻订单</a></li><li><a href="javascript:operation(4)">删除订单</a></li></ul>
  </div>
</form>

      <table id="listTable">
	  </table>
    </div>
  </div>
<a style="display: none;" href="" id="vurl" rel="noreferrer" target="_blank"></a>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<script src="<?php echo $cdnpublic?>bootstrap-datepicker/1.9.0/locales/bootstrap-datepicker.zh-CN.min.js"></script>
<script src="../assets/js/bootstrap-table.min.js"></script>
<script src="../assets/js/bootstrap-table-page-jump-to.min.js"></script>
<script src="../assets/js/custom.js"></script>
<script>
$(document).ready(function(){
	updateToolbar();
	const defaultPageSize = 30;
	const pageNumber = typeof window.$_GET['pageNumber'] != 'undefined' ? parseInt(window.$_GET['pageNumber']) : 1;
	const pageSize = typeof window.$_GET['pageSize'] != 'undefined' ? parseInt(window.$_GET['pageSize']) : defaultPageSize;

	$("#listTable").bootstrapTable({
		url: 'ajax_order.php?act=orderList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: '',
				checkbox: true
			},
			{
				field: 'trade_no',
				title: '系统订单号<br/>商户订单号',
				formatter: function(value, row, index) {
					return '<a href="javascript:showOrder(\''+value+'\')" title="点击查看详情">'+value+'</a></b><br/>'+row.out_trade_no;
				}
			},
			{
				field: 'uid',
				title: '商户号<br/>网站域名',
				formatter: function(value, row, index) {
					return '<a href="./ulist.php?my=search&column=uid&value='+value+'" target="_blank">'+value+'</a><br/><a onclick="openlink(\'http://'+row.domain+'\')">'+row.domain+'</a>';
				}
			},
			{
				field: 'name',
				title: '商品名称<br/>订单金额',
				formatter: function(value, row, index) {
					return value+'<br/>￥<b>'+row.money+'</b>';
				}
			},
			{
				field: 'realmoney',
				title: '实际支付<br/>商户分成',
				formatter: function(value, row, index) {
					return value!=null?'￥<b>'+value+'</b><br/>￥<b>'+row.getmoney+'</b>':'';
				}
			},
			{
				field: 'type',
				title: '支付方式(通道ID)<br/>支付插件',
				formatter: function(value, row, index) {
					return row.typename ? '<img src="/assets/icon/'+row.typename+'.ico" class="type-logo" onerror="this.style.display=\'none\'">'+row.typeshowname+'('+row.channel+')<br/>'+row.plugin : '';
				}
			},
			{
				field: 'ip',
				visible: false,
				cellStyle: function(value, row, index) {
					return {css:{"max-width":"150px","word-break":"break-all"}};
				},
				title: '支付IP<br/>支付账号',
				formatter: function(value, row, index) {
					return '<a href="https://m.ip138.com/iplookup.asp?ip='+value+'" target="_blank" rel="noreferrer">'+value+'</a>&nbsp;<a href="javascript:addBlackList(1,\''+row.ip+'\')" class="btn btn-xs btn-default"><i class="fa fa-stop-circle"></i></a><br/>'+(row.buyer?row.buyer+'&nbsp;<a href="javascript:addBlackList(0,\''+row.buyer+'\')" class="btn btn-xs btn-default"><i class="fa fa-stop-circle"></i></a>':'&nbsp;');
				}
			},
			{
				field: 'addtime',
				title: '创建时间<br/>完成时间',
				formatter: function(value, row, index) {
					return value+'<br/>'+(row.endtime?row.endtime:'&nbsp;');
				}
			},
			{
				field: 'status',
				title: '支付状态',
				formatter: function(value, row, index) {
					var text = '';
					if(value == '1'){
						text = '<font color=green>已支付</font>';
					}else if(value == '2'){
						text = '<font color=red>已退款</font>';
						if(row.refundmoney > 0 && row.refundmoney < row.realmoney){
							text += '<br/><font color=red>('+row.refundmoney+'元)</font>';
						}
					}else if(value == '3'){
						text = '<font color=red>已冻结</font>';
					}else if(value == '4'){
						text = '<font color=orange>预授权</font>';
					}else{
						text = '<font color=blue>未支付</font>';
					}
					if(row.plugin=='alipayd'){
						if(row.settle == '1'){
							text += '<br/><font color=#8c8f93>待结算</font>';
						}else if(row.settle == '2'){
							text += '<br/><font color=#37db3c>结算成功</font>';
						}else if(row.settle == '3'){
							text += '<br/><font color=#ed6565>结算失败</font>';
						}
					}else if(row.plugin=='alipayrp'){
						if(row.settle == '1'){
							text += '<br/><font color=#8c8f93>待转账</font>';
						}else if(row.settle == '2'){
							text += '<br/><font color=#37db3c>转账成功</font>';
						}else if(row.settle == '3'){
							text += '<br/><font color=#ed6565>转账失败</font>';
						}
					}
					return text;
				}
			},
			{
				field: 'status',
				title: '操作',
				formatter: function(value, row, index) {
					let html = '<div class="btn-group dropdown-group" role="group"><button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">操作订单 <span class="caret"></span></button><ul class="dropdown-menu">';
					if(row.plugin=='alipayd' && (row.settle=='1'||row.settle=='3')){
						html += '<li><a href="javascript:alipaydSettle(\''+row.trade_no+'\')">确认结算</a></li>';
					}
					else if(row.plugin=='alipayrp' && (row.settle=='1'||row.settle=='3')){
						html += '<li><a href="javascript:alipayRedPacketTansfer(\''+row.trade_no+'\')">红包转账重试</a></li>';
					}
					if(value == '1'){
						html+= '<li><a href="javascript:setStatus(\''+row.trade_no+'\', 0)">改未完成</a></li><li><a href="javascript:apirefund(\''+row.trade_no+'\')">API退款</a></li><li><a href="javascript:refund(\''+row.trade_no+'\')">手动退款</a></li><li><a href="javascript:freeze(\''+row.trade_no+'\')">冻结订单</a></li><li role="separator" class="divider"></li><li><a href="javascript:callnotify(\''+row.trade_no+'\')">重新通知</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}else if(value == '2'){
						html+= '<li><a href="javascript:setStatus(\''+row.trade_no+'\', 0)">改未完成</a></li><li><a href="javascript:apirefund(\''+row.trade_no+'\')">API退款</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 1)">改已完成</a></li><li role="separator" class="divider"></li><li><a href="javascript:callnotify(\''+row.trade_no+'\')">重新通知</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}else if(value == '3'){
						html+= '<li><a href="javascript:unfreeze(\''+row.trade_no+'\')">解冻订单</a></li><li role="separator" class="divider"></li><li><a href="javascript:callnotify(\''+row.trade_no+'\')">重新通知</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}else{
						if(value == '4'){
							html += '<li><a href="javascript:alipayPreAuthPay(\''+row.trade_no+'\')">授权资金支付</a></li>';
							html += '<li><a href="javascript:alipayUnfreeze(\''+row.trade_no+'\')">授权资金解冻</a></li><li role="separator" class="divider"></li>';
						}
						html+= '<li><a href="javascript:setStatus(\''+row.trade_no+'\', 1)">改已完成</a></li><li role="separator" class="divider"></li><li><a href="javascript:fillorder(\''+row.trade_no+'\')">手动补单</a></li><li><a href="javascript:setStatus(\''+row.trade_no+'\', 5)">删除订单</a></li>';
					}
					html += '</ul></div>';
					return html;
				}
			},
		],
		onLoadSuccess: function(data) {
			$('.dropdown-group').on('show.bs.dropdown', function (e) {
				var btnPos = $(e.target)[0].getBoundingClientRect();
				var screenWidth = $(window).width();
				var screenHeight = $(window).height();
				var childrenWidth = $(e.target).children('.dropdown-menu').width();
				var childrenHeight = $(e.target).children('.dropdown-menu').height();
				var top = btnPos.bottom;
				if(top + childrenHeight + 12 > screenHeight){
					top = btnPos.top - childrenHeight - 12;
				}
				var left = btnPos.left;
				if(left + childrenWidth + 7 > screenWidth){
					left = screenWidth - childrenWidth - 7;
				}
				$(e.target).children('.dropdown-menu').css({position:'fixed', top:top, left:left});
			});
		}
	})
})

function openlink(full_link){ 
	window.open('javascript:window.name;', '<script>location.replace("'+full_link+'")<\/script>');
}

function operation(status){
	var selected = $('#listTable').bootstrapTable('getSelections');
	if(selected.length == 0){
		layer.msg('未选择订单', {time:1500});return;
	}
	if(status == 4 && !confirm('确定要删除已选中的'+selected.length+'个订单吗？')) return;
	var checkbox = new Array();
	$.each(selected, function(key, item){
		checkbox.push(item.trade_no)
	})
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=operation',
		data : {status:status, checkbox:checkbox},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				searchSubmit();
				layer.alert(data.msg);
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('请求超时');
			searchSubmit();
		}
	});
	return false;
}
function showOrder(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	var status = ['<span class="label label-primary">未支付</span>','<span class="label label-success">已支付</span>','<span class="label label-red">已退款</span>'];
	$.ajax({
		type : 'GET',
		url : 'ajax_order.php?act=order&trade_no='+trade_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var data = data.data;
				var item = '<table class="table table-condensed table-hover" id="orderItem">';
				item += '<tr><td colspan="6" style="text-align:center" class="orderTitle"><b>订单信息</b></td></tr>';
				item += '<tr class="orderTitle"><td class="info" class="orderTitle">系统订单号</td><td colspan="5" class="orderContent">'+data.trade_no+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">商户订单号</td><td colspan="5" class="orderContent">'+data.out_trade_no+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">接口订单号</td><td colspan="5" class="orderContent">'+data.api_trade_no+'</td></tr>';
				item += '<tr><td class="info">商户ID</td class="orderTitle"><td colspan="5" class="orderContent"><a href="./ulist.php?my=search&column=uid&value='+data.uid+'" target="_blank">'+data.uid+'</a></td>';
				item += '</tr><tr><td class="info" class="orderTitle">支付方式</td><td colspan="5" class="orderContent">'+data.typename+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">支付通道</td><td colspan="5" class="orderContent"><a href="./pay_channel.php?id='+data.channel+'" target="_blank">'+data.channelname+'</a></td></tr>';
				if(data.subchannel > 0){
					item += '</tr><tr><td class="info" class="orderTitle">自定义子通道</td><td colspan="5" class="orderContent">'+data.subchannelname+'</td></tr>';
				}
				item += '</tr><tr><td class="info" class="orderTitle">商品名称</td><td colspan="5" class="orderContent">'+data.name+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">订单金额</td><td colspan="5" class="orderContent">'+data.money+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">实际支付金额</td><td colspan="5" class="orderContent">'+data.realmoney+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">商户分成金额</td><td colspan="5" class="orderContent">'+data.getmoney+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">手续费利润</td><td colspan="5" class="orderContent">'+data.profitmoney+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">创建时间</td><td colspan="5" class="orderContent">'+data.addtime+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">完成时间</td><td colspan="5" class="orderContent">'+data.endtime+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle" title="只有在官方通道支付完成后才能显示">支付账号</td><td colspan="5" class="orderContent">'+data.buyer+(data.buyer!=null?'&nbsp;&nbsp;<a href="javascript:addBlackList(0,\''+data.buyer+'\')" class="btn btn-xs btn-default">拉黑</a>':'')+'</td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">网站域名</td><td colspan="5" class="orderContent"><a href="http://'+data.domain+'" target="_blank" rel="noreferrer">'+data.domain+'</a></td></tr>';
				item += '</tr><tr><td class="info" class="orderTitle">支付IP</td><td colspan="5" class="orderContent"><a href="https://m.ip138.com/iplookup.asp?ip='+data.ip+'" target="_blank" rel="noreferrer">'+data.ip+'</a>&nbsp;&nbsp;<a href="javascript:addBlackList(1,\''+data.ip+'\')" class="btn btn-xs btn-default">拉黑</a></td></tr>';
				item += '<tr><td class="info" class="orderTitle">扩展参数</td><td colspan="5" class="orderContent">'+data.param+'</td></tr>';
				item += '<tr><td class="info" class="orderTitle">订单状态</td><td colspan="5" class="orderContent">'+status[data.status]+'</td></tr>';
				if(data.status>0){
					item += '<tr><td class="info" class="orderTitle">通知状态</td><td colspan="5" class="orderContent">'+(data.notify==0?'<span class="label label-success">通知成功</span>':'<span class="label label-danger">通知失败</span>（已通知'+data.notify+'次）')+'</td></tr>';
				}
				item += '<tr><td colspan="6" style="text-align:center" class="orderTitle"><b>订单操作</b></td></tr>';
				item += '<tr><td colspan="6"><a href="javascript:callnotify(\''+data.trade_no+'\')" class="btn btn-xs btn-default">重新通知(异步)</a>&nbsp;<a href="javascript:callreturn(\''+data.trade_no+'\')" class="btn btn-xs btn-default">重新通知(同步)</a></td></tr>';
				item += '</table>';
				var area = [$(window).width() > 480 ? '480px' : '100%'];
				layer.open({
				  type: 1,
				  area: area,
				  title: '订单详细信息',
				  skin: 'layui-layer-rim',
				  content: item
				});
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function callnotify(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=notify',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#vurl").attr("href",data.url);
				document.getElementById("vurl").click();
				searchSubmit();
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	});
	return false;
}
function callreturn(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=notify',
		data : {trade_no:trade_no,isreturn:1},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				$("#vurl").attr("href",data.url);
				document.getElementById("vurl").click();
				searchSubmit();
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.msg('服务器错误');
		}
	});
	return false;
}
function refund(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=getmoney',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.open({
					area: ['360px'],
					title: '手动退款确认',
					content: '<p>此操作将从该商户扣除订单分成金额，你需要手动退款给购买者。</p><div class="form-group"><div class="input-group"><div class="input-group-addon">退款金额</div><input type="text" class="form-control" name="refund1" value="'+data.money+'" placeholder="请输入退款金额" autocomplete="off"/></div></div>',
					yes: function(){
						var money = $("input[name='refund1']").val();
						if(money == ''){
							layer.alert('金额不能为空');return;
						}
						var ii = layer.load(2, {shade:[0.1,'#fff']});
						$.ajax({
							type : 'POST',
							url : 'ajax_order.php?act=refund',
							data : {trade_no:trade_no, money:money},
							dataType : 'json',
							success : function(data) {
								layer.close(ii);
								if(data.code == 0){
									layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
								}else{
									layer.alert(data.msg, {icon:7});
								}
							},
							error:function(data){
								layer.close(ii);
								layer.msg('服务器错误');
							}
						});
					}
				});
			}else{
				layer.alert(data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function apirefund(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=getmoney',
		data : {trade_no:trade_no,api:"1"},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.open({
					area: ['360px'],
					title: 'API退款确认',
					content: '<p>此操作将直接原路退款该订单，每个订单只能操作一次退款，退款金额不能大于订单金额。</p><div class="form-group"><div class="input-group"><div class="input-group-addon">退款金额</div><input type="text" class="form-control" name="refund2" value="'+data.money+'" placeholder="请输入退款金额" autocomplete="off"/></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">支付密码</div><input type="text" class="form-control" name="paypwd" value="" placeholder="请输入支付密码" autocomplete="off"/></div></div>',
					yes: function(){
						var money = $("input[name='refund2']").val();
						var paypwd = $("input[name='paypwd']").val();
						if(money == '' || paypwd == ''){
							layer.alert('金额或密码不能为空');return;
						}
						var ii = layer.load(2, {shade:[0.1,'#fff']});
						$.ajax({
							type : 'POST',
							url : 'ajax_order.php?act=apirefund',
							data : {trade_no:trade_no, money:money, paypwd:paypwd},
							dataType : 'json',
							success : function(data) {
								layer.close(ii);
								if(data.code == 0){
									layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
								}else{
									layer.alert(data.msg, {icon:7});
								}
							},
							error:function(data){
								layer.close(ii);
								layer.msg('服务器错误');
							}
						});
					}
				});
			}else{
				layer.alert(data.msg, {icon:7});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function freeze(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=freeze',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function unfreeze(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=unfreeze',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg);
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function setStatus(trade_no, status) {
	if(status==5){
		var confirmobj = layer.confirm('你确实要删除此订单吗？', {
			btn: ['确定','取消']
		}, function(){
			setStatusDo(trade_no, status);
		});
	}else{
		setStatusDo(trade_no, status);
	}
}
function setStatusDo(trade_no, status) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'get',
		url : 'ajax_order.php',
		data : 'act=setStatus&trade_no=' + trade_no + '&status=' + status,
		dataType : 'json',
		success : function(ret) {
			layer.close(ii);
			if (ret['code'] != 200) {
				alert(ret['msg'] ? ret['msg'] : '操作失败');
			}
			layer.closeAll();
			searchSubmit();
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function fillorder(trade_no) {
	var confirmobj = layer.confirm('此操作将不管该订单是否真的支付，直接改为已支付状态并给商户分成，是否确定？', {
		btn: ['确定','取消'], icon:0
	}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_order.php?act=fillorder',
			data : {trade_no:trade_no},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
				}else{
					layer.alert(data.msg);
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
function alipaydSettle(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipaydSettle',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function alipayPreAuthPay(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipayPreAuthPay',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function alipayUnfreeze(trade_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipayUnfreeze',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function addBlackList(stype, scontent){
	var stitle = stype == 1?'IP地址':'支付账号';
	layer.open({
		type: 1,
		area: ['380px'],
		closeBtn: 2,
		title: '添加黑名单',
		content: '<div style="padding:15px"><div class="form-group"><div class="input-group"><div class="input-group-addon">'+stitle+'</div><input class="form-control" type="text" name="add_content" value="'+scontent+'" autocomplete="off" ></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">有效期</div><input class="form-control" type="text" name="add_days" value="0" autocomplete="off" placeholder="0为永久"><div class="input-group-addon">天</div></div></div><div class="form-group"><div class="input-group"><div class="input-group-addon">备注</div><input class="form-control" type="text" name="add_remark" value="" autocomplete="off" placeholder="选填"></div></div></div>',
		btn: ['确认', '取消'],
		yes: function(){
			var content = $("input[name='add_content']").val();
			var days = $("input[name='add_days']").val();
			var remark = $("input[name='add_remark']").val();
			if(content == ''){
				$("input[name='content']").focus();return;
			}
			var ii = layer.load(2, {shade:[0.1,'#fff']});
			$.ajax({
				type : 'POST',
				url : 'ajax_user.php?act=addBlack',
				data : {type:stype, content:content, days:days, remark:remark},
				dataType : 'json',
				success : function(data) {
					layer.close(ii);
					if(data.code == 0){
						layer.alert(data.msg, {icon:1}, function(){ layer.closeAll(); });
					}else{
						layer.alert(data.msg, {icon:0});
					}
				},
				error:function(data){
					layer.close(ii);
					layer.msg('服务器错误');
				}
			});
		}
	});
}
function alipayRedPacketTansfer(trade_no){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_order.php?act=alipayRedPacketTansfer',
		data : {trade_no:trade_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:1}, function(){ layer.closeAll();searchSubmit(); });
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
$(document).ready(function(){
	$('.input-datepicker, .input-daterange').datepicker({
        format: 'yyyy-mm-dd',
		autoclose: true,
        clearBtn: true,
        language: 'zh-CN'
    });
})
</script>