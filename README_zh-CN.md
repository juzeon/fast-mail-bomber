# Fast Mail Bomber via Mailman

![](https://badgen.net/badge/PHP/%3E=7.1/blue)
![](https://badgen.net/badge/license/GPL%20v2.0/green)

[English](README.md) | 简体中文

基于Mailman的Fast Mail Bomber（FMB）是一个用PHP编写的电子邮件轰炸机脚本。FMB通过调用互联网上大量mailman服务接口来轰炸目标邮箱。

![](fmb1.gif)

![](fmb2.gif)

**免责声明：仅供学术研究使用。对于违反相关法律、造成危害的滥用行为，开发者不负任何责任。**

## 环境需求

- PHP >= 7.1
- cURL扩展支持

## 特性

- 自动从Shodan获取mailman接口地址，或者从本地文件导入
- 多线程轰炸
- 200+内置提供者，4000+内置接口节点，高效的即开即用
- 完善的异常处理机制

## 安装

### 1. 拉取本项目

你可以使用git克隆本项目，或者直接下载.zip文件。

```bash
git clone https://github.com/juzeon/fast-mail-bomber.git
cd fast-mail-bomber/
```

### 2. 安装依赖

FMB使用[Guzzle](https://github.com/guzzle/guzzle)。请使用composer安装依赖。

```bash
composer install
```

如果您未安装composer，请访问：<https://getcomposer.org/>

### 3. 配置

复制 `config.example.php` 为 `config.php` ，用编辑器打开，根据里面的注释配置。

## 使用

**概念解释：**

Provider（提供者）: 指一个mailman服务器，通常包括一个 `listinfo` 页面，列出所有可订阅接口的地址。例如： `http://lists.centos.org/mailman/listinfo`

Node（接口节点）: 指mailman服务器上的一个订阅接口地址，可以被直接调用来给目标邮箱发送确认订阅的邮件。例如： `http://lists.centos.org/mailman/subscribe/centos`

### 1. 从Shodan或本地文件更新提供者

```bash
# 从Shodan更新提供者，请先配置Shodan api key。
php index.php update-providers

# 从一个本地文件导入提供者。提供者URL地址在文件中的格式没有要求，因为FMB使用正则来匹配正确的地址。
php index.php import-providers <filepath>
```

重复的提供者会被自动移除。

### 2. 从提供者列表更新接口节点

```bash
# 更新所有的接口节点
php index.php update-nodes
```

当更新接口节点时，不可用的提供者地址会被自动添加到排除列表中并不再使用。

重复的接口节点会被自动移除。

### 3. 开始轰炸

```bash
php index.php start-bombing 邮件地址
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