<?php
include("../includes/common.php");
if($islogin2==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
$title='代付管理';
include './head.php';
?>
<style>
.fixed-table-toolbar,.fixed-table-pagination{padding: 15px;}
</style>
 <div id="content" class="app-content" role="main">
    <div class="app-content-body ">

<div class="bg-light lter b-b wrapper-md hidden-print">
  <h1 class="m-n font-thin h3">代付管理</h1>
</div>
<div class="wrapper-md control">
<?php if(isset($msg)){?>
<div class="alert alert-info">
	<?php echo $msg?>
</div>
<?php }?>
<?php if(!$conf['user_transfer']) showmsg('未开启代付功能');?>
	<div class="panel panel-default">
		<div class="panel-heading font-bold">
			代付记录
		</div>
		<form onsubmit="return searchSubmit()" method="GET" class="form-inline" id="searchToolbar">
			<div class="form-group">
				<label>搜索</label>
				<input type="text" class="form-control" name="value" placeholder="收款账号/姓名">
			</div>
			<div class="form-group">
				<select name="type" class="form-control"><option value="">所有付款方式</option><option value="alipay">支付宝</option><option value="wxpay">微信</option><option value="qqpay">QQ钱包</option><option value="bank">银行卡</option></select>
			</div>
			<div class="form-group">
				<select name="dstatus" class="form-control"><option value="-1">全部状态</option><option value="0">状态正在处理</option><option value="1">状态转账成功</option><option value="2">状态转账失败</option></select>
			</div>
			<button class="btn btn-primary" type="submit"><i class="fa fa-search"></i> 搜索</button>
			<a href="javascript:searchClear()" class="btn btn-default"><i class="fa fa-refresh"></i> 重置</a>
			<a href="./transfer_add.php" class="btn btn-success"><i class="fa fa-plus"></i> 新增代付</a>
		</form>
      <table id="listTable">
	  </table>
	</div>
</div>
    </div>
  </div>

<?php include 'foot.php';?>
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
		url: 'ajax2.php?act=transferList',
		pageNumber: pageNumber,
		pageSize: pageSize,
		classes: 'table table-striped table-hover table-bordered',
		columns: [
			{
				field: 'biz_no',
				title: '交易号/第三方交易号',
				formatter: function(value, row, index) {
					return '<b>'+value+'</b><br/>'+row.pay_order_no;
				}
			},
			{
				field: 'type',
				title: '付款方式',
				formatter: function(value, row, index) {
					let typename = '';
					if(value == 'alipay'){
						typename='<img src="/assets/icon/alipay.ico" width="16" onerror="this.style.display=\'none\'">支付宝';
					}else if(value == 'wxpay'){
						typename='<img src="/assets/icon/wxpay.ico" width="16" onerror="this.style.display=\'none\'">微信';
					}else if(value == 'qqpay'){
						typename='<img src="/assets/icon/qqpay.ico" width="16" onerror="this.style.display=\'none\'">QQ钱包';
					}else if(value == 'bank'){
						typename='<img src="/assets/icon/bank.ico" width="16" onerror="this.style.display=\'none\'">银行卡';
					}
					return typename;
				}
			},
			{
				field: 'account',
				title: '付款账号/姓名',
				formatter: function(value, row, index) {
					return ''+value+'<br/>'+row.username+'';
				}
			},
			{
				field: 'money',
				title: '付款金额/花费金额',
				formatter: function(value, row, index) {
					return '￥<b>'+value+'</b><br/>￥<b>'+row.costmoney+'</b>';
				}
			},
			{
				field: 'paytime',
				title: '付款时间/备注',
				formatter: function(value, row, index) {
					return ''+value+'<br/>'+(row.desc?'<font color="#bf7fef">'+row.desc+'</font>':'')+'';
				}
			},
			{
				field: 'status',
				title: '状态',
				formatter: function(value, row, index) {
					if(value == '1'){
						return '<font color=green>转账成功</font>';
					}else if(value == '2'){
						return '<a href="javascript:showResult(\''+row.biz_no+'\')" title="点此查看失败原因"><font color=red>转账失败</font></a>';
					}else{
						return '<a href="javascript:queryStatus(\''+row.biz_no+'\')" title="点此查询转账状态"><font color=orange>正在处理</font></a>';
					}
				}
			},
		],
	})
})
function showResult(biz_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=transfer_result&biz_no='+biz_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				layer.alert(data.msg, {icon:0, title:'失败原因', shadeClose:true});
			}else{
				layer.alert(data.msg, {icon:2});
			}
		},
		error:function(data){
			layer.msg('服务器错误');
			return false;
		}
	});
}
function queryStatus(biz_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'GET',
		url : 'ajax2.php?act=transfer_query&biz_no='+biz_no,
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				searchSubmit();
				layer.alert(data.msg, {title:'查询结果'});
			}else{
				layer.alert(data.msg, {icon:2, title:'查询失败'});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
function getProof(biz_no) {
	var ii = layer.load(2, {shade:[0.1,'#fff']});
	$.ajax({
		type : 'POST',
		url : 'ajax2.php?act=transfer_proof',
		data : {biz_no:biz_no},
		dataType : 'json',
		success : function(data) {
			layer.close(ii);
			if(data.code == 0){
				if(data.download_url){
					layer.alert('获取转账凭证成功！<a href="'+data.download_url+'" target="_blank">点击下载凭证</a>', {icon:1, title:'获取凭证'});
				}else{
					layer.alert(data.msg, {icon:1, title:'获取凭证'});
				}
			}else{
				layer.alert(data.msg, {icon:2, title:'获取失败'});
			}
		},
		error:function(data){
			layer.close(ii);
			layer.msg('服务器错误');
		}
	});
}
</script>