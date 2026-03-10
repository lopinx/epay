# 支付插件系统

## 概述
支付插件系统是彩虹易支付的核心功能之一，支持87种不同的支付渠道。每个插件独立实现特定支付方式的接口。

## 目录结构
```
plugins/
├── alipay/         # 支付宝支付插件
├── wxpay/          # 微信支付插件
├── qqpay/          # QQ钱包支付插件
├── paypal/         # PayPal支付插件
├── stripe/         # Stripe支付插件
├── epay/           # 易支付接口
├── swiftpass/      # 威富通支付
├── sandpay/        # 杉德支付
├── allinpay/       # 通联支付
├── yeepay/         # 易宝支付
├── jdpay/          # 京东支付
├── unionpay/       # 银联支付
├── heepay/         # 汇付天下
├── fubei/          # 付呗支付
├── xunhupay/       # 迅虎支付
├── jeepay/         # Jeepay支付
├── adapay/         # Adapay支付
├── easypay/        # easypay支付
├── passpay/        # passpay支付
├── xorpay/         # xorpay支付
├── zyu/            # 众游支付
├── hnapay/         # 汇潮支付
├── kayixin/        # 开鑫付
├── ltzf/           # 蓝天支付
├── vmq/            # V免签
├── duolabao/       # 多啦宝
├── umfpay/         # 联动优势
├── chinaums/       # 银商商务
├── ysepay/         # 银盛支付
├── hlpay/          # 汇联支付
├── alipayd/        # 支付宝点餐
├── alipaysl/       # 支付宝服务商
├── alipayrp/       # 支付宝红包
├── wxpayn/         # 微信原生支付
├── wxpaynp/        # 微信Native支付
├── wxpayng/        # 微信公众号支付
├── wxpaysl/        # 微信服务商支付
├── epayn/          # 新版易支付
├── swiftpass2/     # 威富通2.0
├── kuaiqian/       # 快钱支付
├── huifu/          # 汇付支付
├── woaizf/         # 我爱支付
├── xsy/            # 小树云
├── zhangyishou/    # 掌易收
└── ...             # 其他50+支付插件
```

## 插件结构
每个支付插件通常包含以下文件：
1. `{plugin_name}_plugin.php` - 插件主文件
2. `config.php` - 插件配置文件
3. `inc/` - 插件核心代码目录
4. `sdk/` - 第三方SDK目录

## 插件开发规范
1. 插件主文件需定义支付处理类
2. 实现标准的支付、查询、回调接口
3. 遵循系统统一的错误处理机制
4. 插件配置通过数据库管理

## 特殊约定
1. 插件目录名即为插件标识
2. 插件通过后台进行启用和配置
3. 插件间相互独立，无依赖关系