<?php
include("../includes/common.php");
$title='邀请码管理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
<div class="modal" align="left" id="search" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
        <h4 class="modal-title" id="myModalLabel">生成邀请码</h4>
      </div>
      <div class="modal-body">
      <form action="invitecode.php?my=add" method="POST">
<input type="text" class="form-control" name="num" placeholder="生成的个数" required><br/>
<input type="submit" class="btn btn-primary btn-block" value="生成"></form>
</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-lg-10 center-block" style="float: none;">
<?php
function getkm($len = 18)
{
	$str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	$strlen = strlen($str);
	$randstr = "";
	for ($i = 0; $i < $len; $i++) {
		$randstr .= $str[mt_rand(0, $strlen - 1)];
	}
	return $randstr;
}

$my=isset($_GET['my'])?$_GET['my']:null;

if($my=='add'){
$kind=1;
$num=intval($_POST['num']);
$value=intval($_POST['value']);
echo "<ul class='list-group'><li class='list-group-item active'>成功生成以下邀请码</li>";
for ($i = 0; $i < $num; $i++) {
	$km=random(8);
	$sql=$DB->query("insert into `pre_invitecode` (`code`,`addtime`,`status`) values ('".$km."',NOW(),0)");
	if($sql) {
		echo "<li class='list-group-item'>$km</li>";
	}
}

echo '<a href="./invitecode.php" class="btn btn-default btn-block">>>返回邀请码列表</a>';
}

elseif($my=='del'){
echo '<div class="panel panel-primary">
<div class="panel-heading w h"><h3 class="panel-title">删除邀请码</h3></div>
<div class="panel-body box">';
$id=$_GET['id'];
$sql=$DB->query("DELETE FROM pre_invitecode WHERE id='$id'");
if($sql){echo '删除成功！';}
else{echo '删除失败！';}
echo '<hr/><a href="./invitecode.php">>>返回邀请码列表</a></div></div>';
}

elseif($my=='qk'){//清空邀请码
if(!checkRefererHost())exit();
echo '<div class="panel panel-primary">
<div class="panel-heading w h"><h3 class="panel-title">清空邀请码</h3></div>
<div class="panel-body box">
您确认要清空所有邀请码吗？清空后无法恢复！<br><a href="./invitecode.php?my=qk2">确认</a> | <a href="javascript:history.back();">返回</a></div></div>';
}
elseif($my=='qk2'){//清空邀请码结果
if(!checkRefererHost())exit();
echo '<div class="panel panel-primary">
<div class="panel-heading w h"><h3 class="panel-title">清空邀请码</h3></div>
<div class="panel-body box">';
if($DB->query("DELETE FROM pre_invitecode WHERE 1")==true){
echo '<div class="box">清空成功.</div>';
}else{
echo'<div class="box">清空失败.</div>';
}
echo '<hr/><a href="./invitecode.php">>>返回邀请码列表</a></div></div>';
}
elseif($my=='qkuse'){//清空已使用邀请码
if(!checkRefererHost())exit();
echo '<div class="panel panel-primary">
<div class="panel-heading w h"><h3 class="panel-title">清空邀请码</h3></div>
<div class="panel-body box">
您确认要清空所有邀请码吗？清空后无法恢复！<br><a href="./invitecode.php?my=qkuse2">确认</a> | <a href="javascript:history.back();">返回</a></div></div>';
}
elseif($my=='qkuse2'){//清空已使用邀请码结果
if(!checkRefererHost())exit();
echo '<div class="panel panel-primary">
<div class="panel-heading w h"><h3 class="panel-title">清空邀请码</h3></div>
<div class="panel-body box">';
if($DB->exec("DELETE FROM pre_invitecode WHERE status=1")!==false){
echo '<div class="box">清空成功.</div>';
}else{
echo'<div class="box">清空失败.</div>';
}
echo '<hr/><a href="./invitecode.php">>>返回邀请码列表</a></div></div>';
}
else
{

echo '<form action="invitecode.php" method="GET" class="form-inline">
  <div class="form-group">
    <label>搜索</label>
    <input type="text" class="form-control" name="kw" placeholder="邀请码" required>
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
  <a href="invitecode.php?my=qk" class="btn btn-danger">清空</a>
  <a href="invitecode.php?my=qkuse" class="btn btn-danger">清空已使用</a>
  <a href="#" data-toggle="modal" data-target="#search" id="search" class="btn btn-success">生成</a>
</form>';

if(isset($_GET['kw'])) {
	$sql=" `code`='{$_GET['kw']}'";
	$numrows=$DB->getColumn("SELECT count(*) from pre_invitecode WHERE{$sql}");
	$con='包含 '.$_GET['kw'].' 的共有 <b>'.$numrows.'</b> 个邀请码';
}else{
	$numrows=$DB->getColumn("SELECT count(*) from pre_invitecode WHERE 1");
	$sql=" 1";
	$con='系统共有 <b>'.$numrows.'</b> 个邀请码';
}
echo $con;
?>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>邀请码</th><th>状态</th><th>添加时间</th><th>使用时间</th><th>使用者</th><th>操作</th></tr></thead>
          <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM pre_invitecode WHERE{$sql} order by id desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '<tr><td><b>'.$res['code'].'</b></td><td>'.($res['status']==1?'<font color="red">已使用</font>':'<font color="green">未使用</font>').'</td><td>'.$res['addtime'].'</td><td>'.$res['usetime'].'</td><td><a href="./ulist.php?column=uid&value='.$res['uid'].'" target="_blank">'.$res['uid'].'</a></td><td><a href="./invitecode.php?my=del&id='.$res['id'].'" class="btn btn-xs btn-danger" onclick="return confirm(\'你确实要删除此邀请码吗？\');">删除</a></td></tr>';
}
?>
          </tbody>
        </table>
      </div>
<?php
echo'<ul class="pagination">';
$first=1;
$prev=$page-1;
$next=$page+1;
$last=$pages;
if ($page>1)
{
echo '<li><a href="invitecode.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="invitecode.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
for ($i=1;$i<$page;$i++)
echo '<li><a href="invitecode.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$pages;$i++)
echo '<li><a href="invitecode.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '';
if ($page<$pages)
{
echo '<li><a href="invitecode.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="invitecode.php?page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo'</ul>';
#分页
}
?>
    </div>
  </div>