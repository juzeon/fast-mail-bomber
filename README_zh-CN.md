# Fast Mail Bomber via Mailman

![](https://img.shields.io/badge/php-%3E%3D7.2-blue)
![](https://img.shields.io/github/license/juzeon/fast-mail-bomber)
![](https://img.shields.io/github/repo-size/juzeon/fast-mail-bomber?color=blueviolet)
![](https://img.shields.io/github/stars/juzeon/fast-mail-bomber?color=lightgrey)

[English](README.md) | 简体中文

基于Mailman的Fast Mail Bomber（FMB）是一个用PHP编写的电子邮件轰炸机脚本。FMB通过调用互联网上大量mailman服务接口来轰炸目标邮箱。

网页版（仅供测试）：<https://www.skyju.cc/mailhzj.html>

我博客上对这个项目的介绍：<https://blog.skyju.cc/post/introduce-the-new-fast-mail-bomber/>

十分新手向的使用教程：<https://blog.skyju.cc/post/fast-mail-bomber-noob-guidance/>

![](fmb1.gif)

![](fmb2.gif)

**免责声明：仅供学术研究使用。对于违反相关法律、造成危害的滥用行为，开发者不负任何责任。**

## 环境需求

- PHP >= 7.2
- cURL扩展支持

## 特性

- 自动从Shodan获取mailman接口地址，或者从本地文件导入
- 多线程轰炸
- 900+内置提供者，50,000+内置接口节点，高效的即开即用
- 完善的异常处理机制

## 安装

### 1. 拉取本项目

你可以使用git克隆本项目，或者直接下载.zip文件。

```bash
git clone https://github.com/juzeon/fast-mail-bomber.git
cd fast-mail-bomber/
```

### 2. 配置

复制 `config.example.php` 为 `config.php` ，用编辑器打开，根据里面的注释配置。

## 使用

**概念解释：**

Provider（提供者）: 指一个mailman服务器，通常包括一个 `listinfo` 页面，列出所有可订阅接口的地址。例如： `http://lists.centos.org/mailman/listinfo`

Node（接口节点）: 指mailman服务器上的一个订阅接口地址，可以被直接调用来给目标邮箱发送确认订阅的邮件。例如： `http://lists.centos.org/mailman/subscribe/centos`

### 1. （可选）从Shodan和ZoomEye或本地文件更新提供者

```bash
# 从Shodan和ZoomEye更新提供者，请先配置Shodan api key或者ZoomEye api key，也可两者都配置。
php index.php update-providers

# 从一个本地文件导入提供者。提供者URL地址在文件中的格式没有要求，因为FMB使用正则来匹配正确的地址。
php index.php import-providers <filepath>
```

重复的提供者会被自动移除。

### 2. （建议）从提供者列表更新接口节点

```bash
# 可选。由于网络环境的不同，本项目内置的接口节点可能在您的环境中无法使用。因此您最好删除这些节点（但不要删除data/providers.json），然后自行运行update-nodes。根据您的网络环境和提供者列表的大小，操作需要10~30分钟不等。
rm -rf data/nodes.json data/dead_providers.json

# 更新所有的接口节点
php index.php update-nodes

# 可选。每个提供者只精炼一个接口节点，写入一个和上面不同的文件中。
php index.php refine-nodes
```

当更新接口节点时，不可用的提供者地址会被自动添加到排除列表中并不再使用。

重复的接口节点会被自动移除。

您可以直接使用内置提供者和节点，跳过这一步。

### 3. 开始轰炸

```bash
php index.php start-bombing [refined] <邮件地址>

# 举例：使用全部接口节点轰炸一个邮箱：
php index.php start-bombing email@example.com

# 举例：仅使用精炼的接口节点轰炸一个邮箱：
php index.php start-bombing refined email@example.com
```

成功或失败的请求将会通过控制台输出。按CTRL+C停止程序。

**免责声明：仅供学术研究使用。对于违反相关法律、造成危害的滥用行为，开发者不负任何责任。**

## 测试结果

我测试了FMB轰炸邮件对于不同电子邮件提供商的进箱率：

Proton Mail: 99.4% into Inbox（收件箱）, 0.6% into Spambox（垃圾箱）.

Gmail: 83.2% into Inbox, 16.8% into Spambox.

Outlook Mail: 77.1% into Inbox, 22.9% into Spambox.

163 Mail: 100% into Inbox, 0% into Spambox.

QQ Mail: 71% into Inbox, 29% into Spambox.

Zoho Mail: 0% into Inbox, 15.9% into Newsletter, 84.1% into Spambox.

Yandex Mail: 0% into Inbox, 100% into Spambox.

## 如何避免被轰炸

由于mailman默认配置中发送邮件的模板都是一样的，所以简单地添加以下字符串到邮件正文过滤列表中：

```
Mailing list subscription confirmation notice for mailing list
```

## 许可协议

GPL v2.0