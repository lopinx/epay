<?php
// QQ公众号支付页面

if(!defined('IN_PLUGIN'))exit();
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta charset="utf-8" />
    <meta name="viewport" content="initial-scale=1, maximum-scale=1, user-scalable=no, width=device-width">
    <link href="<?php echo $cdnpublic?>ionic/1.3.2/css/ionic.min.css" rel="stylesheet" />
</head>
<body>
<div class="bar bar-header bar-light" align-title="center">
	<h1 class="title">QQ钱包支付</h1>
</div>
<div class="has-header" style="padding: 5px;position: absolute;width: 100%;">
<div class="text-center" style="color: #a09ee5;">
<i class="icon ion-information-circled" style="font-size: 80px;"></i><br>
<span>正在跳转...</span>
<script src="<?php echo $cdnpublic?>jquery/1.12.4/jquery.min.js"></script>
<script src="<?php echo $cdnpublic?>layer/3.1.1/layer.min.js"></script>
<script src="//open.mobile.qq.com/sdk/qqapi.js?_bid=152"></script>
<script>
	document.body.addEventListener('touchmove', function (event) {
		event.preventDefault();
	},{ passive: false });

	function callpay()
	{
		mqq.tenpay.pay({
			tokenId: '<?php echo $tokenId; ?>',
			appInfo: "<?php echo $appInfo; ?>"
		}, function(result, resultCode){
			if(result.resultCode == 0){ //支付成功
				loadmsg();
			}
		});
	}
    function loadmsg() {
        $.ajax({
            type: "GET",
            dataType: "json",
            url: "/getshop.php",
            data: {type: "qqpay", trade_no: "<?php echo $order['trade_no']?>"},
            success: function (data) {
                if (data.code == 1) {
					layer.msg('支付成功，正在跳转中...', {icon: 16,shade: 0.1,time: 15000});
					setTimeout(window.location.href=data.backurl, 1000);
                }else{
                    setTimeout("loadmsg()", 2000);
                }
            },
            error: function () {
                setTimeout("loadmsg()", 2000);
            }
        });
    }
    window.onload = callpay();
</script>
</div>
</div>
</body>
</html>