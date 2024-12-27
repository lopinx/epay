<?php
if(!defined('IN_CRONLITE')) {
    exit('Access Denied');
}
preg_match("#^\d.\d#", PHP_VERSION, $p_v);
$php_v = str_replace('.', '', $p_v[0]);

$is_win = strtolower(substr(PHP_OS, 0, 3)) === 'win';
$loader_ext = $is_win ? 'dll' : 'so';
$loader_path = ROOT.'assets/loader/swoole_loader_'.$php_v.'_nts.' . $loader_ext;
$loader_path = str_replace('/', DIRECTORY_SEPARATOR, $loader_path);
?>
<div class="container" style="padding-top:70px;">
  <div class="row">
    <div class="col-xs-12 col-sm-10 col-lg-8 center-block" style="float: none;">
<?php
if($p_v[0] < 8.0 || $p_v[0] >= 8.1){
    showmsg('当前插件只支持PHP-8.0版本！');
}
if(!file_exists($loader_path)){
  showmsg('Swoole Loader文件不存在，请联系管理员手动安装！');
}?>
<div class="panel panel-info">
<div class="panel-heading"><h3 class="panel-title">Swoole Loader 安装助手</h3></div>
<div class="panel-body">
<p>1、打开您的PHP配置文件（<code><?php echo php_ini_loaded_file()?></code>），如果是宝塔面板，在【软件商店】，找到PHP，点击【设置】->【配置文件】</p>
<p>2、PHP配置文件底部找一下，如果以前添加过类似<code>extension=swoole_loader_***.<?php echo $loader_ext?></code>这样的代码，先将其删掉，如果没有就下一步</p>
<p>3、将下面的代码复制到PHP配置文件的最后一行保存：</p>
<p><code>extension=<?php echo $loader_path?></code></p>
<p>4、重启php进程</p>
<p>5、刷新本页面</p>
</div>
</div>
    </div>
  </div>
</div>