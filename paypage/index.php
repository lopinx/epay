<?php
$is_defend = true;
include("./inc.php");
if(isset($_GET['merchant'])){
	$merchant=trim($_GET['merchant']);
	$uid = authcode($merchant, 'DECODE', SYS_KEY);
	if(!$uid || !is_numeric($uid))showerror('参数错误');
}elseif(isset($_SESSION['paypage_uid'])){
	$uid = intval($_SESSION['paypage_uid']);
}else{
	showerror('参数不完整');
}
$userrow = $DB->getRow("SELECT `uid`,`gid`,`money`,`mode`,`pay`,`cert`,`status`,`username`,`channelinfo`,`qq`,`codename` FROM `pre_user` WHERE `uid`='{$uid}' LIMIT 1");
if(!$userrow || $userrow['status']==0 || $userrow['pay']==0)showerror('当前商户不存在或已被封禁');
if($userrow['pay']==2 && $conf['user_review']==1)showerror('商户没通过审核，请联系官方客服进行审核');
if($conf['cert_force']==1 && $userrow['cert']==0){
	showerror('当前商户未完成实名认证，无法收款');
}
if($conf['forceqq']==1 && empty($userrow['qq'])){
	showerror('当前商户未填写联系QQ，无法收款');
}

$_SESSION['paypage_uid'] = $uid;

$direct = '0';
$checktype = check_paytype();
$type = isset($_GET['type'])?trim($_GET['type']):$checktype;
if($type){
    if((isset($_GET['code']) || isset($_GET['auth_code'])) && $_SESSION['paypage_channel']){
        $submitData = \lib\Channel::info($_SESSION['paypage_channel'], $userrow['gid']);
    }else{
        $submitData = \lib\Channel::submit($type, $uid, $userrow['gid']);
        $_SESSION['paypage_subchannel'] = $submitData['subchannel'];
    }
    $_SESSION['paypage_typeid'] = $submitData['typeid'];
	$_SESSION['paypage_channel'] = $submitData['channel'];
	$_SESSION['paypage_rate'] = $submitData['rate'];
	$_SESSION['paypage_paymax'] = $submitData['paymax'];
	$_SESSION['paypage_paymin'] = $submitData['paymin'];
    $_SESSION['paypage_mode'] = $submitData['mode'];

	$apptype = explode(',',$submitData['apptype']);
	if($checktype == 'alipay' && $type == 'alipay' && ($submitData['plugin']=='alipay' || $submitData['plugin']=='alipaysl' || $submitData['plugin']=='alipayd') && in_array('4',$apptype)){
		$openId = alipayOpenId($submitData['channel']);
		$direct = '1';
	}elseif($checktype == 'wxpay' && $type == 'wxpay' && ($submitData['plugin']=='wxpay' || $submitData['plugin']=='wxpaysl' || $submitData['plugin']=='wxpayn' || $submitData['plugin']=='wxpaynp') && in_array('2',$apptype)){
		$openId = weixinOpenId($submitData['channel']);
		$direct = '1';
	}elseif($checktype == 'qqpay' && $type == 'qqpay'&& $submitData['plugin']=='qqpay' && in_array('2',$apptype)){
		$direct = '1';
	}
}

$money = isset($_GET['money'])?$_GET['money']:null;
if($money<=0 || !is_numeric($money) || !preg_match('/^[0-9.]+$/', $money))$money = null;
$codename = !empty($userrow['codename'])?$userrow['codename']:$userrow['username'];
$csrf_token = md5(mt_rand(0,999).time());
$_SESSION['paypage_token'] = $csrf_token;
?>
<html lang="zh-cn">
<head>
    <title>向商户付款</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="cache-control" content="no-cache">
    <meta http-equiv="expires" content="0">
    <link rel="stylesheet" href="css/default.css">
    <link rel="stylesheet" href="css/style.css?version=1001">
</head>
<body>
<div class="layout-flex wrap">

  <!-- content start -->
  <div class="content">
      <div class="mar20">
          <table>
              <tbody>
                  <tr>
                      <td><span class="sico_pay" style="margin:5px 5px 10px 5px"></span></td>
                      <td  class="selTitle"><?php echo $codename?></td>
                  </tr>
              </tbody>
          </table>
      </div>
    <form name="payForm" action="dopay" method="post">
        <input type="hidden" name="uid" id="uid" value="<?php echo $uid?>">
        <input type="hidden" name="token" id="token" value="<?php echo $csrf_token?>">
        <input type="hidden" name="paytype" id="paytype" value="<?php echo $type?>">
		<input type="hidden" name="direct" id="direct" value="<?php echo $direct?>">
		<input type="hidden" name="payer" id="payer" value="<?php echo $openId?>">
		<input type="hidden" name="trade_no" id="trade_no" value="">
        <?php if($money){?><input type="hidden" name="txAmount" id="txAmount" value="<?php echo $money?>"><?php }?>
        <div class="set_amount">
        	<div class="payMoney marLeft10">请输入付款金额</div>
            <div class="amount_bd">
                <i class="i_money marLeft10" style="">¥</i>
                <span class="input_simu " id="amount"></span>

                <!-- 模拟input -->
                <em class="line_simu" id="line"></em>
                <!-- 模拟闪烁的光标 -->
                <div  id="clearBtn"  style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);"></div>
                <!-- 清除按钮 -->
            </div>
        </div>
        <div class="set_remark">
            <div class="have_been_set">
                <span>备注：<span id="remark-content"></span></span>
                <div class="remark_operate">
                    <a href="#" class="remark_add" id="openModal">添加备注</a>
                    <a href="#" class="remark_edit">编辑</a>
                    <a href="#" class="remark_clear_away">清除</a>
                </div>
            </div>
        </div>
    </form>
    <div id="myModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="title">添加备注</h5>
                <span class="close" id="modal-close">&times;</span>
            </div>
            <div class="modal-body" id="remark-form">
                <textarea name="remark" placeholder="请输入备注内容，30个字以内" rows="3"></textarea>
                <button type="button">确认</button>
                <div class="remark-tip">备注内容不能超过30个字</div>
            </div>
        </div>
    </div>
  </div>
  <!-- content end -->

  <div class="copyRight">由 <span style="font-weight:bold"><?php echo $conf['sitename']?></span> 提供服务支持</div>
  <!-- 键盘 -->
  <div class="keyboard">
      <table class="key_table" id="keyboard" style="touch-action:pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);">
          <tbody>
              <tr>
                <td class="key border b_rgt_btm" data-value="1">1</td>
                <td class="key border b_rgt_btm" data-value="2">2</td>
                <td class="key border b_rgt_btm" data-value="3">3</td>
                <td class="key border b_btm clear" data-value="delete"></td>
              </tr>
              <tr>
                <td class="key border b_rgt_btm" data-value="4">4</td>
                <td class="key border b_rgt_btm" data-value="5">5</td>
                <td class="key border b_rgt_btm" data-value="6">6</td>
                <td class="pay_btn" rowspan="3" id="payBtn" style="touch-action: pan-y; user-select: none; -webkit-user-drag: none; -webkit-tap-highlight-color: rgba(0, 0, 0, 0);"><em>确认</em>支付</td>
              </tr>
              <tr>
                <td class="key border b_rgt_btm" data-value="7">7</td>
                <td class="key border b_rgt_btm" data-value="8">8</td>
                <td class="key border b_rgt_btm" data-value="9">9</td>
              </tr>
              <tr>
                <td colspan="2" class="key border b_rgt" data-value="0">0</td>
                <td class="key border b_rgt" data-value="dot">.</td>
              </tr>
          </tbody>
      </table>
  </div>

</div>

<script src="<?php echo $cdnpublic?>jquery/3.4.1/jquery.min.js"></script>
<script src="//open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
<script src="js/hammer.js"></script>
<script src="js/common.js"></script>
<script src="js/pay.js?v=1004"></script>
<script>
	document.body.addEventListener('touchmove', function (event) {
		event.preventDefault();
	},{ passive: false });
    var tips = new Tips();

    // 模态框操作
    var modal = document.getElementById("myModal");
    document.querySelector(".remark_add").onclick = function() {
        modal.classList.add("show");
        document.querySelector(".modal-header .title").innerText = "添加备注";
    }
    document.getElementById("modal-close").onclick = function() {
        modal.classList.remove("show");
        modal.addEventListener('transitionend', () => {
            modal.style.display = "none";
        }, { once: true });
    }

    // 添加备注
    var submitBtn = document.querySelector("#remark-form button");
    submitBtn.onclick = function() {
        var remark = document.querySelector("#remark-form textarea").value;
        if (remark.length > 30) {
            document.querySelector(".remark-tip").style.display = "block";
            document.querySelector("#remark-form textarea").style.borderColor = "red";
            document.querySelector("#remark-form textarea").onfocus = function() {
                document.querySelector("#remark-form textarea").style.borderColor = "#ddd";
                document.querySelector(".remark-tip").style.display = "none";
            }
            return;
        }
        document.querySelector("#remark-content").innerText = remark;
        modal.classList.remove("show");
        modal.addEventListener('transitionend', () => {
            modal.style.display = "none";
        }, { once: true });
        if(remark.length > 0){
            document.querySelector(".remark_operate").classList.add("yes");
        }
    }
    // 编辑备注
    document.querySelector(".remark_edit").onclick = function() {
        modal.classList.add("show");
        document.querySelector(".modal-header .title").innerText = "编辑备注";
    }
    // 清除备注
    document.querySelector(".remark_clear_away").onclick = function() {
        document.querySelector("#remark-content").innerText = "";
        document.querySelector("#remark-form textarea").value = "";
        document.querySelector(".remark_operate").classList.remove("yes");
    }
</script>
</body>
</html>