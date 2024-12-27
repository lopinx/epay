<div align="center">彩虹易支付</div>

### 部署环境 

宝塔面板 PHP7.4 + MySQL 5.7（官方推荐配置php7.1+mysql5.6)

关闭`跨站攻击`，伪静态配置在根目录 nginx.txt 里面

### 安装教程 

1、访问域名/install 进行安装

2、在宝塔设置url计划任务，不设置支付不回调，具体可安装后在后台支付配置里查看

3、设置Nginx防伪静态，Nginx防伪静态在根目录Nginx.txt里，Apache防伪静态在.htaccess里

4、管理后台 `/admin` , 账号密码 `admin`、`123456`

### 源码版本

Version: 3075

### 其他信息

- [Docker](https://github.com/monlor/dockerfiles/tree/main/epay)

- [TokenPay](https://github.com/LightCountry/TokenPay)

- [Epay](https://github.com/maajiko/Epay)