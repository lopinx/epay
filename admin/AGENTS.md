# 管理后台

## 概述
管理后台包含管理员操作界面，用于管理系统配置、用户账户、支付渠道和订单等。

## 目录结构
```
admin/
├── index.php       # 后台入口文件
├── ajax.php        # AJAX请求处理
├── shop.php        # 商户管理
├── order.php       # 订单管理
├── settle.php      # 结算管理
├── record.php      # 资金记录
├── user.php        # 用户管理
├── group.php       # 用户组管理
├── channel.php     # 支付渠道管理
├── psreceiver.php  # 分账接收方管理
├── transfer.php    # 转账管理
├── cert.php        # 实名认证管理
├── work.php        # 工单管理
├── article.php     # 文章管理
├── pay_template.php# 支付模板管理
├── domain.php      # 域名管理
├── config.php      # 系统配置
├── gonggao.php     # 公告管理
├── invite.php      # 邀请码管理
├── price.php       # 定价模板管理
├── blacklist.php   # 黑名单管理
├── alipayrisk.php  # 支付宝风控
├── login.php       # 登录页面
├── reg.php         # 管理员注册
├── findpwd.php     # 找回密码
├── soft.php        # 软件下载
├── clean.php       # 清理数据
├── database.php    # 数据库管理
├── template.php    # 模板管理
├── log.php         # 日志查看
├── cron.php        # 定时任务
├── updump.php      # 备份恢复
├── check.php       # 系统检测
├── install        # 安装相关文件
│   ├── index.php
│   ├── install.sql
│   ├── uplog.php
│   └── update.php
└── assets/        # 静态资源文件
```

## 关键功能
1. 系统配置管理
2. 用户和商户管理
3. 支付渠道配置
4. 订单和结算管理
5. 财务记录查看
6. 实名认证审核
7. 公告和文章发布
8. 数据备份和恢复

## 特殊约定
1. 后台使用独立的登录验证机制
2. 管理员操作需要相应权限
3. 敏感操作需要二次确认