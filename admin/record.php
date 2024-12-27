<?php
/**
 * 资金明细
**/
include("../includes/common.php");
$title='资金明细';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-md-12 center-block" style="float: none;">
<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
  <div class="form-group">
    <label>搜索</label>
	<select name="column" class="form-control"><option value="uid">商户号</option><option value="type">操作类型</option><option value="money">变更金额</option><option value="trade_no">关联订单号</option></select>
  </div>
  <div class="form-group">
    <input type="text" class="form-control" name="value" placeholder="搜索内容">
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="javascript:searchClear()" class="btn btn-default" title="刷新明细列表"><i class="fa fa-refresh"></i></a>
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
		url: 'ajax_user.php?act=recordList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'uid',
				title: '商户号',
				formatter: function(value, row, index) {
					return '<b><a href="./ulist.php?column=uid&value='+value+'" target="_blank">'+value+'</a></b>';
				}
			},
			{
				field: 'type',
				title: '操作类型',
				formatter: function(value, row, index) {
					return row.action==2?'<font color="red">'+value+'</font>':'<font color="green">'+value+'</font>';
				}
			},
			{
				field: 'money',
				title: '变更金额',
				formatter: function(value, row, index) {
					return (row.action==2?'- ':'+ ')+value;
				}
			},
			{
				field: 'oldmoney',
				title: '变更前金额'
			},
			{
				field: 'newmoney',
				title: '变更后金额'
			},
			{
				field: 'date',
				title: '时间'
			},
			{
				field: 'trade_no',
				title: '关联订单号',
				formatter: function(value, row, index) {
					return value?'<a href="./order.php?column=trade_no&value='+value+'" target="_blank">'+value+'</a>':'无';
				}
			},
			{
				field: '',
				title: '操作',
				visible: false,
				formatter: function(value, row, index) {
					return '<a class="btn btn-xs btn-danger" onclick="delItem('+row.id+')">删除</a>';
				}
			},
		],
	})
})
function delItem(id) {
	var confirmobj = layer.confirm('你确实要删除此记录吗？', {
	  btn: ['确定','取消'], icon:0
	}, function(){
	  $.ajax({
		type : 'GET',
		url : 'ajax_user.php?act=delRecord&id='+id,
		dataType : 'json',
		success : function(data) {
			if(data.code == 0){
				layer.closeAll();
				searchSubmit();
			}else{
				layer.alert(data.msg, {icon: 2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	  });
	}, function(){
	  layer.close(confirmobj);
	});
}
</script>