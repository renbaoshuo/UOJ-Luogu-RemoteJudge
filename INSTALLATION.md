# 安装

**我们强烈建议您在安装前对网站、数据库进行备份，以免修改过程中出错造成数据丢失。**

## 前期准备

请先在服务器上安装以下软件/模块：

- Node.js 18 LTS
- php-curl

然后为 php-cli 启用 php-v8js 和 php-yaml 扩展：

```bash
sed -i -e '912a\extension=v8js.so\nextension=yaml.so' /etc/php/7.4/cli/php.ini
```

## 修改源码

### 修改数据库

先进入 MySQL CLI（将 `-u` 后的 `root` 替换为你的 MySQL 用户名，将 `-p` 后的 `root` 替换为你的 MySQL 密码）：

```bash
mysql -uroot -proot app_uoj233
```

然后运行以下 SQL 语句以修改数据库中 `problems` 表结构：

```sql
ALTER TABLE `problems` ADD `type` varchar(20) NOT NULL DEFAULT 'local';
ALTER TABLE `problems` ADD KEY `type` (`type`);
```

然后输入 `exit;` 退出 MySQL CLI。

### 应用补丁

如果您没有对 UOJ 的源码进行过修改，可以尝试直接使用 `patch -p1` 命令应用提供的补丁。否则，请对照 `<filename>.patch` 逐一修改本仓库的 `web` 目录中给出的每个文件。

如果某个文件并未给出补丁，则该文件为新增文件，您需要将其复制到 UOJ 的对应目录中。

## 配置桥接评测机

### 增加评测机

先进入 MySQL CLI（将 `-u` 后的 `root` 替换为你的 MySQL 用户名，将 `-p` 后的 `root` 替换为你的 MySQL 密码）：

```bash
mysql -uroot -proot app_uoj233
```

然后执行下面的 SQL 语句以添加评测机（请将密码修改为一个随机值，以防被攻击；切勿改变评测机名称）：

```sql
insert into judger_info (judger_name, password) values ('luogu_remote_judger', '_judger_password_');
```

然后输入 `exit;` 退出 MySQL CLI。

接下来的两种运行方式任选其一即可。

### 直接运行

切换到 `luogu_remote_judger` 目录，运行以下命令：

```bash
npm install
```

_运行该命令时需要联网下载依赖，如果花费时间过长请更换 npm 镜像源。_

之后使用以下命令启动桥接评测机（将 `_judger_password_` 修改为上方配置的密码）：

```bash
UOJ_HOST="127.0.0.1" UOJ_JUDGER_PASSWORD="_judger_password_" LUOGU_API_USERNAME="洛谷开放平台用户 ID" LUOGU_API_PASSWORD="洛谷开放平台用户密码" npm run start
```

您需要通过自己的方式保证桥接评测机一直在运行。可以使用 `pm2` 等程序来进行进程守护。

### Docker

当然，您也可以使用 Docker 来启动桥接评测机，请使用以下命令构建镜像：

```bash
docker build -t uoj-luogu-remote-judger .
```

然后使用以下命令来启动容器（将 `uoj-web` 修改为 Docker 宿主机 IP，`_judger_password_` 修改为上方配置的密码）：

```bash
docker run -d -e UOJ_HOST="uoj-web" -e UOJ_JUDGER_PASSWORD="_judger_password_" -e LUOGU_API_USERNAME="洛谷开放平台用户 ID" -e LUOGU_API_PASSWORD="洛谷开放平台用户密码" --name uoj-luogu-remote-judger --restart always uoj-luogu-remote-judger
```

## CLI 使用教程

### 导入题目

如果您需要批量导入题目，可以使用 CLI 来完成这项操作：

```bash
php cli.php luogu:add-problem P1000 P1001
```

上面的命令将会导入洛谷题目 P1000 和 P1001。

如果您有下载好的离线数据库文件（可以在 [评测能力](https://docs.lgapi.cn/open/judge/) 页面下载），可以使用 `--file` 选项指定，以加快导入进度：

```bash
php cli.php luogu:add-problem --file latest.ndjson P1000 P1001
```

注：请使用由洛谷提供的 `latest.ndjson.gz` 解压出的 `latest.ndjson` 文件，而非洛谷提供的压缩包本身。
