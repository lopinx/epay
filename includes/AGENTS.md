# 核心功能库

## 概述
核心功能库包含系统的基础类库和公共函数，是整个系统的支撑框架。

## 目录结构
```
includes/
├── common.php          # 系统初始化文件
├── functions.php       # 公共函数库(40KB)
├── member.php          # 用户会员认证
├── pages/              # 页面处理文件
├── lib/                # 核心类库
│   ├── Cache.php       # 缓存类
│   ├── PdoHelper.php   # 数据库操作类
│   ├── Template.php    # 模板引擎
│   ├── Channel.php     # 支付渠道处理
│   ├── Payment.php     # 支付处理核心
│   ├── Transfer.php    # 转账处理
│   ├── MsgNotice.php   # 消息通知
│   ├── GeetestLib.php  # 极验验证
│   ├── mail/           # 邮件发送类
│   ├── sms/            # 短信发送类
│   ├── wechat/         # 微信相关类
│   └── qrcodedecoder/  # 二维码解码器
├── vendor/             # Composer依赖包
│   ├── composer/       # Composer核心
│   ├── fgrosse/phpasn1/# ASN.1编码解码
│   ├── mdanter/ecc/    # 椭圆曲线加密
│   ├── cccyun/         # 支付SDK
│   └── ...             # 其他第三方库
└── autoloader.php      # 自动加载器
```

## 核心组件
1. **数据库操作类** - 封装PDO操作
2. **缓存类** - 文件缓存实现
3. **模板引擎** - 简单模板处理
4. **支付核心** - 统一支付流程
5. **渠道处理** - 支付插件管理
6. **安全验证** - 极验等验证机制

## 特殊约定
1. 核心函数集中在`functions.php`文件
2. 类库采用自动加载机制
3. 数据库操作使用预处理防止SQL注入
4. 缓存机制减少数据库压力