<?php
/**
 * 登录
**/
$verifycode = 1;//验证码开关
$login_limit_count = 5;//登录失败次数
$login_limit_file = '@login.lock';

if(!function_exists("imagecreate") || !file_exists('code.php'))$verifycode=0;
include("../includes/common.php");

if(isset($_GET['act']) && $_GET['act']=='login'){
  if(!checkRefererHost())exit('{"code":403}');
  $username = trim($_POST['username']);
  $password = trim($_POST['password']);
  $code = trim($_POST['code']);
  if(empty($username) || empty($password)){
    exit(json_encode(['code'=>-1,'msg'=>'用户名或密码不能为空']));
  }
  if($verifycode==1 && (!$code || strtolower($code) != $_SESSION['vc_code'])){
    exit(json_encode(['code'=>-1,'msg'=>'验证码错误']));
  }
  if(file_exists($login_limit_file)){
    $login_limit = unserialize(file_get_contents($login_limit_file));
    if($login_limit['count']>=$login_limit_count && $login_limit['time']>time()-86400){
      exit(json_encode(['code'=>-1,'msg'=>'多次登录失败，暂时禁止登录。可删除@login.lock文件解除限制']));
    }
  }
  if($username == $conf['admin_user'] && $password == $conf['admin_pwd']){
    $DB->insert('log', ['uid'=>0, 'type'=>'登录后台', 'date'=>'NOW()', 'ip'=>$clientip]);
		$session=md5($username.$password.$password_hash);
		$expiretime=time() + 2592000;
		$token=authcode("{$username}\t{$session}\t{$expiretime}", 'ENCODE', SYS_KEY);
		setcookie("admin_token", $token, $expiretime, null, null, null, true);
    unset($_SESSION['vc_code']);
    exit(json_encode(['code'=>0]));
  }else{
    //$DB->insert('log', ['uid'=>0, 'type'=>'登录失败', 'date'=>'NOW()', 'ip'=>$clientip]);
    if(!file_exists($login_limit_file)){
      $login_limit = ['count'=>0,'time'=>0];
    }
    $login_limit['count']++;
    $login_limit['time']=time();
    file_put_contents($login_limit_file, serialize($login_limit));
    $retry_times = $login_limit_count-$login_limit['count'];
    unset($_SESSION['vc_code']);
    if($retry_times == 0){
      exit(json_encode(['code'=>-1,'msg'=>'多次登录失败，暂时禁止登录。可删除@login.lock文件解除限制','vcode'=>1]));
    }else{
      exit(json_encode(['code'=>-1,'msg'=>'用户名或密码错误，你还可以尝试'.$retry_times.'次','vcode'=>1]));
    }
  }
}elseif(isset($_GET['logout'])){
	if(!checkRefererHost())exit();
	setcookie("admin_token", "", time() - 2592000);
	exit("<script language='javascript'>window.location.href='./login.php';</script>");
}elseif($islogin==1){
	exit("<script language='javascript'>alert('您已登录！');window.location.href='./';</script>");
}
$title='用户登录';
include './head.php';
?>
  <nav class="navbar navbar-fixed-top navbar-default">
    <div class="container">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
          <span class="sr-only">导航按钮</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="./">支付管理中心</a>
      </div><!-- /.navbar-header -->
      <div id="navbar" class="collapse navbar-collapse">
        <ul class="nav navbar-nav navbar-right">
          <li class="active">
            <a href="./login.php"><span class="glyphicon glyphicon-user"></span> 登录</a>
          </li>
        </ul>
      </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
  </nav><!-- /.navbar -->
  <div class="container" style="padding-top:70px;">
    <div class="col-xs-12 col-sm-10 col-md-8 col-lg-6 center-block" style="float: none;">
      <div class="panel panel-primary">
        <div class="panel-heading"><h3 class="panel-title">管理员登录</h3></div>
        <div class="panel-body">
          <form class="form-horizontal" role="form" onsubmit="return submitlogin()">
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-user"></span></span>
              <input type="text" name="user" value="" class="form-control input-lg" placeholder="用户名" required="required"/>
            </div><br/>
            <div class="input-group">
              <span class="input-group-addon"><span class="glyphicon glyphicon-lock"></span></span>
              <input type="password" name="pass" class="form-control input-lg" placeholder="密码" required="required"/>
            </div><br/>
			<?php if($verifycode==1){?>
			<div class="input-group">
				<span class="input-group-addon"><span class="glyphicon glyphicon-adjust"></span></span>
				<input type="text" class="form-control input-lg" name="code" placeholder="输入验证码" autocomplete="off" required>
				<span class="input-group-addon" style="padding: 0">
					<img id="verifycode" src="./code.php?r=<?php echo time();?>"height="45"onclick="this.src='./code.php?r='+Math.random();" title="点击更换验证码">
				</span>
			</div><br/>
			<?php }?>
            <div class="form-group">
              <div class="col-xs-12"><input type="submit" value="立即登录" class="btn btn-primary btn-block btn-lg"/></div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script>
function submitlogin(){
    var user = $("input[name='user']").val();
	  var pass = $("input[name='pass']").val();
    var code = $("input[name='code']").val();
    if(user=='' || pass==''){layer.alert('用户名或密码不能为空！');return false;}
    var ii = layer.load(2);
    $.ajax({
      type : 'POST',
      url : '?act=login',
      data: {username:user, password:pass, code:code},
      dataType : 'json',
      success : function(data) {
        layer.close(ii);
        if(data.code == 0){
          layer.msg('登录成功，正在跳转', {icon: 1,shade: 0.01,time: 15000});
          window.location.href='./';
        }else{
          if(data.vcode==1){
            $("#verifycode").attr('src', './code.php?r='+Math.random())
          }
          layer.alert(data.msg, {icon: 2});
        }
      },
      error:function(data){
        layer.close(ii);
        layer.msg('服务器错误');
      }
    });
    return false;
}
</script>
</body>
</html>