<?php
include("../includes/common.php");
$title='文章管理';
include './head.php';
if($islogin==1){}else exit("<script language='javascript'>window.location.href='./login.php';</script>");
?>
  <div class="container" style="padding-top:70px;">
    <div class="col-sm-12 col-md-10 center-block" style="float: none;">
<?php
$my=isset($_GET['my'])?$_GET['my']:null;

if($my=='add')
{
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">添加文章</h3></div>
<div class="panel-body">
  <form action="./article.php?my=add_submit" method="post" class="form-horizontal" role="form">
    <div class="form-group">
	  <label class="col-sm-2 control-label">文章标题</label>
	  <div class="col-sm-10"><input type="text" name="title" value="" class="form-control" required/></div>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">文章内容</label>
	  <div class="col-sm-10"><textarea id="editor_id" class="form-control" name="content" rows="16" style="width:100%;"></textarea></div>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">是否置顶</label>
	  <div class="col-sm-10"><select class="form-control" name="top"><option value="0">否</option><option value="1">是</option></select></div>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">发布时间</label>
	  <div class="col-sm-10"><input type="date" name="addtime" value="<?php echo date("Y-m-d"); ?>" class="form-control"/></div>
	</div>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="发布" class="btn btn-primary btn-block"/><br/>
	 </div>
	</div>
  </form>
  <br/><a href="./article.php">>>返回文章列表</a>
</div>
</div>
<script charset="utf-8" src="./assets/kindeditor/kindeditor-all-min.js"></script>
<script charset="utf-8" src="./assets/kindeditor/zh-CN.js"></script>
<script src="assets/js/editor.js?ver=<?php echo VERSION ?>"></script>
<?php
}
elseif($my=='edit')
{
$id=$_GET['id'];
$row=$DB->getRow("select * from pre_article where id='$id' limit 1");
?>
<div class="panel panel-primary">
<div class="panel-heading"><h3 class="panel-title">编辑文章</h3></div>
<div class="panel-body">
  <form action="./article.php?my=edit_submit&id=<?php echo $row['id']; ?>" method="post" class="form-horizontal" role="form">
    <div class="form-group">
	  <label class="col-sm-2 control-label">文章标题</label>
	  <div class="col-sm-10"><input type="text" name="title" value="<?php echo $row['title']; ?>" class="form-control" required/></div>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">文章内容</label>
	  <div class="col-sm-10"><textarea id="editor_id" class="form-control" name="content" rows="16" style="width:100%;"><?php echo htmlspecialchars($row['content']);?></textarea></div>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">是否置顶</label>
	  <div class="col-sm-10"><select class="form-control" name="top" default="<?php echo $row['top']?>"><option value="0">否</option><option value="1">是</option></select></div>
	</div>
	<div class="form-group">
	  <label class="col-sm-2 control-label">发布时间</label>
	  <div class="col-sm-10"><input type="date" name="addtime" value="<?php echo date("Y-m-d", strtotime($row['addtime']))?>" class="form-control"/></div>
	</div>
	<div class="form-group">
	  <div class="col-sm-offset-2 col-sm-10"><input type="submit" name="submit" value="发布" class="btn btn-primary btn-block"/><br/>
	 </div>
	</div>
  </form>
  <br/><a href="./article.php">>>返回文章列表</a>
</div>
</div>
<script>
var items = $("select[default]");
for (i = 0; i < items.length; i++) {
	$(items[i]).val($(items[i]).attr("default")||0);
}
</script>
<script charset="utf-8" src="./assets/kindeditor/kindeditor-all-min.js"></script>
<script charset="utf-8" src="./assets/kindeditor/zh-CN.js"></script>
<script src="assets/js/editor.js?ver=<?php echo VERSION ?>"></script>
<?php
}
elseif($my=='add_submit')
{
if(!checkRefererHost())exit();
$title=trim($_POST['title']);
$content=$_POST['content'];
$top=intval($_POST['top']);
$addtime=$_POST['addtime'].' '.date("H:i:s");
if($title==NULL or $content==NULL){
showmsg('保存错误,请确保必填项都不为空!',3);
} else {
$rows=$DB->getRow("select * from pre_article where title='$title' limit 1");
if($rows)
	showmsg('文章标题已存在！',3);
$sql="INSERT INTO `pre_article` (`title`,`content`,`addtime`,`top`,`active`) VALUES (:title, :content, :addtime, :top, :active)";
$data = [':title'=>$title, ':content'=>$content, ':addtime'=>$addtime, ':top'=>$top, ':active'=>1];
if($DB->exec($sql, $data)){
	showmsg('添加文章成功！<br/><br/><a href="./article.php">>>返回文章列表</a>',1);
}else
	showmsg('添加文章失败！'.$DB->error(),4);
}
}
elseif($my=='edit_submit')
{
if(!checkRefererHost())exit();
$id=$_GET['id'];
$rows=$DB->getRow("select * from pre_article where id='$id' limit 1");
if(!$rows)
	showmsg('当前记录不存在！',3);
$title=trim($_POST['title']);
$content=$_POST['content'];
$top=intval($_POST['top']);
$addtime=$_POST['addtime'].' '.date("H:i:s");
if($title==NULL or $content==NULL){
showmsg('保存错误,请确保必填项都不为空!',3);
} else {
$sql = "UPDATE pre_article SET title=:title,content=:content,addtime=:addtime,top=:top WHERE id=:id";
$data = [':title'=>$title, ':content'=>$content, ':addtime'=>$addtime, ':top'=>$top, ':id'=>$id];
if($DB->exec($sql, $data)!==false)
	showmsg('修改文章成功！<br/><br/><a href="./article.php">>>返回文章列表</a>',1);
else
	showmsg('修改文章失败！'.$DB->error(),4);
}
}
elseif($my=='delete')
{
if(!checkRefererHost())exit();
$id=$_GET['id'];
$sql="DELETE FROM pre_article WHERE id=:id";
if($DB->exec($sql, [':id'=>$id]))
	showmsg('删除成功！<br/><br/><a href="./article.php">>>返回文章列表</a>',1);
else
	showmsg('删除失败！'.$DB->error(),4);
}
else
{

if(isset($_GET['kw'])){
	$kw = trim(daddslashes($_GET['kw']));
	$sql=" title LIKE '%$kw%'";
	$numrows=$DB->getColumn("SELECT count(*) from pre_article where{$sql}");
	$link='&kw='.$kw;
}else{
	$sql=" 1";
	$numrows=$DB->getColumn("SELECT count(*) from pre_article");
}
?>
<div class="panel panel-default">
<div class="panel-heading"><h3 class="panel-title">系统共有 <b><?php echo $numrows?></b> 篇文章</h3></div>
<form action="article.php" method="GET" class="form-inline">
 <a href="./article.php?my=add" class="btn btn-success"><i class="fa fa-plus"></i>&nbsp;添加文章</a>
  <div class="form-group">
    <input type="text" class="form-control" name="kw" placeholder="请输入文章标题">
  </div>
  <button type="submit" class="btn btn-primary">搜索</button>
</form>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>ID</th><th>文章标题</th><th>发布时间</th><th>浏览量</th><th>状态</th><th>操作</th></tr></thead>
          <tbody>
<?php
$pagesize=30;
$pages=ceil($numrows/$pagesize);
$page=isset($_GET['page'])?intval($_GET['page']):1;
$offset=$pagesize*($page - 1);

$rs=$DB->query("SELECT * FROM pre_article WHERE{$sql} order by id desc limit $offset,$pagesize");
while($res = $rs->fetch())
{
echo '<tr><td><b>'.$res['id'].'</b></td><td>'.($res['top']?'[置顶]':null).$res['title'].'</td><td>'.$res['addtime'].'</td><td>'.$res['count'].'</td><td>'.($res['active']==1?'<span class="btn btn-xs btn-success" onclick="setActive('.$res['id'].',0)">显示</span>':'<span class="btn btn-xs btn-warning" onclick="setActive('.$res['id'].',1)">隐藏</span>').'</td><td><a class="btn btn-xs btn-success" href="../?mod=article&id='.$res['id'].'" target="_blank">查看</a>&nbsp;<a href="./article.php?my=edit&id='.$res['id'].'" class="btn btn-info btn-xs">编辑</a>&nbsp;<a href="./article.php?my=delete&id='.$res['id'].'" class="btn btn-xs btn-danger" onclick="return confirm(\'你确实要删除此文章吗？\');">删除</a></td></tr>';
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
echo '<li><a href="article.php?page='.$first.$link.'">首页</a></li>';
echo '<li><a href="article.php?page='.$prev.$link.'">&laquo;</a></li>';
} else {
echo '<li class="disabled"><a>首页</a></li>';
echo '<li class="disabled"><a>&laquo;</a></li>';
}
$start=$page-10>1?$page-10:1;
$end=$page+10<$pages?$page+10:$pages;
for ($i=$start;$i<$page;$i++)
echo '<li><a href="article.php?page='.$i.$link.'">'.$i .'</a></li>';
echo '<li class="disabled"><a>'.$page.'</a></li>';
for ($i=$page+1;$i<=$end;$i++)
echo '<li><a href="article.php?page='.$i.$link.'">'.$i .'</a></li>';
if ($page<$pages)
{
echo '<li><a href="article.php?page='.$next.$link.'">&raquo;</a></li>';
echo '<li><a href="article.php?page='.$last.$link.'">尾页</a></li>';
} else {
echo '<li class="disabled"><a>&raquo;</a></li>';
echo '<li class="disabled"><a>尾页</a></li>';
}
echo'</ul>';
#分页
?>
<?php }?>
    </div>
  </div>
</div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.js"></script>
<script src="assets/js/article.js?ver=<?php echo VERSION ?>"></script>
</body>
</html>