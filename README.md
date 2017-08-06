# WeCenter 问答系统简介

---

WeCenter 问答系统是一套开源的社交化问答软件系统。作为国内首个推出基于 PHP 的社交化问答系统，WeCenter 期望能够给更多的站长或者企业提供一套完整的社交问答系统，帮助社区或者企业搭建相关的知识库建设。

### WeCenter 问答系统的下载

您可以随时从我们的官方下载站下载到最新版本，以及各种补丁

[http://www.wecenter.com/downloads/][1]

### WeCenter 问答系统的环境需求

 1. 可用的 www 服务器，如 Apache、IIS、nginx, 推荐使用性能高效的 Apache 或 nginx.
 2. PHP 5.2.2 及以上
 3. MySQL 5.0 及以上, 服务器需要支持 MySQLi 或 PDO_MySQL
 4. GD 图形库支持或 ImageMagick 支持, 推荐使用 ImageMagick, 在处理大文件的时候表现良好

### WeCenter 问答系统的安装

 1. 上传 upload 目录中的文件到服务器
 2. 设置目录属性（windows 服务器可忽略这一步），以下这些目录需要可读写权限

    ./
    ./system
    ./system/config 含子目录

 3. 访问站点开始安装
 4. 参照页面提示，进行安装，直至安装完毕

### WeCenter 问答系统在 Sina App Engine 安装

参见这篇文章: [http://www.wecenter.com/category/support/sae-install/][2]

### WeCenter Rewrite 开启方法

参见这篇文章: [http://www.wecenter.com/category/support/settings/][3]

### WeCenter 问答系统的升级

升级过程非常简单, 覆盖所有文件之后运行 http://您的域名/index.php?/upgrade/ 按照提示操作即可

### WeCenter 软件的技术支持

当您在安装、升级、日常使用当中遇到疑难，请您到以下站点获取技术支持。

 - 支持：[http://www.wecenter.com/support/][4]
 - 讨论区：[http://wenda.wecenter.com][5]



[1]: http://www.wecenter.com/downloads/
[2]: http://www.wecenter.com/category/support/sae-install/
[3]: http://www.wecenter.com/category/support/settings/
[4]: http://www.wecenter.com/support/
[5]: http://wenda.wecenter.com

