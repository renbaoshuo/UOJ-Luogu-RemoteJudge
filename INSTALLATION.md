# 安装

## 前期准备

请先在服务器上安装以下软件/模块：

- Node.js 18 LTS
- php-curl

然后为 php-cli 启用 php-v8js 和 php-yaml 扩展：

```bash
sed -i -e '912a\extension=v8js.so\nextension=yaml.so' /etc/php/7.4/cli/php.ini
```

## 修改源码

_TODO_
