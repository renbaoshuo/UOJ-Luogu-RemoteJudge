# 使用帮助

## 题库中的远程题目

![](https://arina.loli.net/2023/03/29/EM2lqdt6rGQeyo8.png)

在 (1) 处显示的是已经添加好的洛谷题目。

在 (2) 处可以输入洛谷题号（如 `P1001`、`B1023`）进行单题的添加。

在 (3) 处则可以添加本地题目（即不使用远程评测的题目），与增加 Remote Judge 功能前一致。

## 编辑现有远端评测题目

![](https://arina.loli.net/2023/03/29/HAayJjdbcY937wt.png)

点击 (1) 处按钮即可重新爬取题目，重新爬取后将会覆盖现有的对标题、题面的修改。对标签的更改不会被覆盖。

需要注意的是，请不要修改「数据」选项卡中的任何信息！这可能会导致远端评测失败。

远端题目的数据并不在本地存储，所以数据选项卡中提示无数据是正常现象，并非代码出错。

## 命令行工具的使用

本模块在 `cli.php` 中提供了命令行管理支持。

命令格式：

```text
php cli.php luogu:add-problem [--file <path>] <luogu-pid> <luogu-pid> ...

  --file <path>   (optional) Specifies the path to the database file (in .ndjson format).

  <luogu-pid>     The problem id on Luogu. (e.g. P1001)
```

### 从离线数据库批量添加题目

洛谷开放平台提供了一份 `.ndjson.gz` 格式的题面信息压缩包，包含了洛谷主题库中所有题目的题面等基础信息。

下载好该文件后，请先解压，得到 `latest.ndjson`。

然后执行如下命令（请根据实际情况更改文件路径和洛谷题号）：

```bash
php cli.php luogu:add-problem --file ./latest.ndjson P2000 P2001
```

结果如图所示：

![](https://arina.loli.net/2023/03/29/hp6LHwmXnSojI29.png)

注意：提示 `Press Enter to continue, or Ctrl+C to abort.` 时请按 <kbd>Enter</kbd> 键以继续，如果想取消操作，请按 <kbd>Ctrl</kbd> + <kbd>C</kbd> 组合键。新建题目的过程中请忽略出现的 `rm` 相关提示，因为题目数据并不在本地存储。

### 从在线题库批量添加题目

如果所需题目量较少，或者不会下载离线题库，可以使用本功能添加题目。使用方法与离线题库大致相同，只需删除 `--file` 参数即可。

![](https://arina.loli.net/2023/03/29/YotqgHwx98zMbuv.png)

## 重测题目

如果评测时出现了错误，可以对评测记录进行重测。请注意，每次重测提交记录都会扣除评测配额。

![](https://arina.loli.net/2023/03/29/rXKEFu4k2tCPn3W.png)
