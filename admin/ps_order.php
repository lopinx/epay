<?php
/**
 * 分账记录
**/
include("../includes/common.php");
$title='分账记录';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="trade_no">系统订单号</option><option value="api_trade_no">接口订单号</option><option value="money">分账金额</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容">
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="rid" style="width: 120px;" placeholder="分账规则ID" value="">
  </div>
  <div class="form-group">
	<select name="dstatus" class="form-control"><option value="-1">全部状态</option><option value="0">待分账</option><option value="1">已提交</option><option value="2">分账成功</option><option value="3">分账失败</option></select>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新记录列表"><i class="fa fa-refresh"></i></a>
</form>

      <table id="listTable">
	  </table>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
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
		url: 'ajax_profitsharing.php?act=orderList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'trade_no',
				title: '系统订单号',
				formatter: function(value, row, index) {
					return '<b><a href="./order.php?column=trade_no&value='+value+'" target="_blank">'+value+'</a></b>';
				}
			},
			{
				field: 'rid',
				title: '分账规则ID'
			},
			{
				field: 'money',
				title: '分账金额',
				formatter: function(value, row, index) {
					if(row.status == '0'){
						return '<a href="javascript:editmoney('+row.id+', \''+value+'\')" title="修改分账金额">'+value+'</a>';
					}
					return value;
				}
			},
			{
				field: 'addtime',
				title: '时间'
			},
			{
				field: 'status',
				title: '分账状态',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<font color=orange>已提交</font>';
					}else if(value == '2'){
						return '<font color=green>分账成功</font>';
					}else if(value == '3'){
						return '<font color=red>分账失败</font>';
					}else if(value == '4'){
						return '<font color=grey>已取消</font>';
					}else{
						return '<font color=blue>待分账</font>';
					}
				}
			},
			{
				field: 'status',
				title: '操作',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<a href="javascript:do_query('+row.id+')" class="btn btn-info btn-xs">查询结果</a>';
					}else if(value == '2'){
						return '<a href="javascript:do_return('+row.id+')" class="btn btn-danger btn-xs">分账回退</a>';
					}else if(value == '3'){
						return '<a href="javascript:show_result('+row.id+',\''+row.result+'\')" class="btn btn-warning btn-xs">查看原因</a>&nbsp;<a href="javascript:do_submit('+row.id+')" class="btn btn-primary btn-xs">重试</a>';
					}else if(value == '0'){
						return '<a href="javascript:do_submit('+row.id+')" class="btn btn-primary btn-xs">提交分账</a>&nbsp;<a href="javascript:do_unfreeze('+row.id+')" class="btn btn-danger btn-xs">取消</a>';
					}
				}
			},
		],
	})
})
function do_submit(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_profitsharing.php?act=submit',
		data : {id:id},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code >= 0){
				layer.alert(data.msg, {icon: 1}, function(){layer.closeAll();searchSubmit()});
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
function do_query(id){
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax_profitsharing.php?act=query',
		data : {id:id},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				var msg = '查询结果：正在分账';
				if(data.status == 1) msg = '查询结果：分账成功';
				else if(data.status == 2) msg = '查询结果：分账失败，原因：'+data.reason;
				layer.alert(msg, {icon: 1}, function(){layer.closeAll();searchSubmit()});
			}else{
				layer.alert('查询失败：'+data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function do_unfreeze(id){
	var confirmobj = layer.confirm('取消分账后将解冻资金到商户号，后续无法再次发起分账，是否继续？', {
		btn: ['确定','取消']
	}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_profitsharing.php?act=unfreeeze',
			data : {id:id},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.alert(data.msg, {icon: 1}, function(){layer.closeAll();searchSubmit()});
				}else{
					layer.alert('解冻剩余资金失败：'+data.msg, {icon: 2});
				}
			},
			error:function(data){
				layer.close(ii);
				layer.msg('服务器错误');
			}
		});
	});
}
function do_return(id){
	var confirmobj = layer.confirm('将已分账的资金从分账接收方的账户回退给分账方，是否继续？', {
		btn: ['确定','取消']
	}, function(){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_profitsharing.php?act=return',
			data : {id:id},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.alert(data.msg, {icon: 1}, function(){layer.closeAll();searchSubmit()});
				}else{
					layer.alert('退分账失败：'+data.msg, {icon: 2});
				}
			},
			error:function(data){
				layer.close(ii);
				layer.msg('服务器错误');
			}
		});
	});
}
function show_result(id, result){
	layer.alert(result, {title:'失败原因'});
}
function editmoney(id, money){
	layer.prompt({title: '修改分账金额', value: money, formType: 0}, function(text, index){
		var ii = layer.load(2, {shade:[0.1,'#fff']});
		$.ajax({
			type : 'POST',
			url : 'ajax_profitsharing.php?act=editmoney',
			data : {id:id,money:text},
			dataType : 'json',
			success : function(data) {
				layer.close(ii);
				if(data.code == 0){
					layer.closeAll();
					layer.msg('修改成功', {time:800});
					searchSubmit()
				}else{
					layer.alert(data.msg, {icon: 2});
				}
			},
			error:function(data){
				layer.close(ii);
				layer.msg('服务器错误');
			}
		});
	});
}
</script>