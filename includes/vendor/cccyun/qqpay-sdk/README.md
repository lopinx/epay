# QQPay SDK for PHP
QQ钱包支付第三方 PHP SDK，基于官方最新版本。

### 功能特点

- 根据QQ钱包支付最新API开发，相比官方SDK，功能更完善，代码更简洁
- 支持Composer安装，无需加载多余组件，可应用于任何平台或框架
- 符合`PSR`标准，你可以各种方便的与你的框架集成
- 基本完善的PHPDoc，可以随心所欲添加本项目中没有的API接口

### 环境要求

`PHP` >= 7.1

### 使用方法

1. Composer 安装。

   ```bash
   composer require cccyun/qqpay-sdk
   ```

2. 创建配置文件 [`config.php`](./examples/config.php)，填写QQ钱包支付商户信息。

3. 引入配置文件，构造请求参数，调用PaymentService中的方法发起请求，参考 [`examples/qrpay.php`](./examples/qrpay.php)。

4. 更多实例，请移步 [`examples`](examples/) 目录。

5. 类功能说明

   | 类名            | 说明                                 |
   | --------------- | ------------------------------------ |
   | PaymentService  | 基础支付服务类，所有支付功能都用这个 |
   | TransferService | QQ钱包企业付款功能                   |
   
6. 要对接的API在以上实现类中没有，可根据QQ钱包官方的文档，使用BaseService类中的execute方法直接调用接口。

   

