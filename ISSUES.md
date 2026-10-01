# migears-mail — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (6th round, 2026-10-01).

| | |
|---|---|
| Status | **P1 open** |
| Size | src 529 lines (net) · 95 tests · 7 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 1 · P2 5 · P3 3 · other 0 |
| Settled | 7 of 16 |
| Waiting on the owner | `P2-3`, `P2-4`, `P2-5` |
| Waiting on the coordinator | _nothing_ |
| Waiting on the reviewer | `P1-2`, `P2-1`, `P2-2`, `P3-6`, `P3-7`, `P3-8` |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P1-1`](issues/P1-1.md) | P1 | **verified** | The README promises custom headers override same-named built-in ones … |
| [`P1-2`](issues/P1-2.md) | P1 | **fixed** | The README says a custom header overrides the same-named built-in 'in … |
| [`P2-1`](issues/P2-1.md) | P2 | **fixed** | Non-ASCII attachment filenames and non-ASCII display names are emitted … |
| [`P2-2`](issues/P2-2.md) | P2 | **fixed** | Attachment name and type are interpolated into quoted MIME parameters … |
| [`P2-3`](issues/P2-3.md) | P2 | **open** | `charset` is interpolated into the `Content-Type` parameter unescaped, … |
| [`P2-4`](issues/P2-4.md) | P2 | **open** | The native driver appends a caller's custom `To`/`Subject` header … |
| [`P2-5`](issues/P2-5.md) | P2 | **open** | `SmtpMailer` writes a caller's custom `Bcc` header verbatim into the … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | A cc/bcc-only message is accepted by SmtpMailer but rejected by … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | NativeMailer writes a `Bcc:` header while SmtpMailer deliberately omits … |
| [`P3-3`](issues/P3-3.md) | P3 | **verified** | Non-ASCII subjects diverge: SmtpMailer applies RFC 2047 base64 … |
| [`P3-4`](issues/P3-4.md) | P3 | **verified** | `expect()` validates only the first line's status code; a continuation … |
| [`P3-5`](issues/P3-5.md) | P3 | **verified** | With `encryption: ''` and credentials present, `AUTH LOGIN` still goes … |
| [`P3-6`](issues/P3-6.md) | P3 | **rejected** | Mail::getFormattedFrom() does not strip CRLF from fromName, though both … |
| [`P3-7`](issues/P3-7.md) | P3 | **rejected** | SmtpMailer::send() does dot-escaping with str_replace('\r\n.', … |
| [`P3-8`](issues/P3-8.md) | P3 | **fixed** | The README states absolutely that SmtpMailer 'never writes a Bcc: … |
| [`G2`](issues/G2.md) | - | **verified** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **9** of 16 |
| By status | `open` 3 · `rejected` 2 · `fixed` 4 |
| Waiting on | owner 3 · reviewer 6 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P1** | [`P1-2`](issues/P1-2.md) | `fixed` | reviewer | The README says a custom header overrides the same-named built-in 'in … |
| **P2** | [`P2-1`](issues/P2-1.md) | `fixed` | reviewer | Non-ASCII attachment filenames and non-ASCII display names are emitted … |
| **P2** | [`P2-2`](issues/P2-2.md) | `fixed` | reviewer | Attachment name and type are interpolated into quoted MIME parameters … |
| **P2** | [`P2-3`](issues/P2-3.md) | `open` | owner | `charset` is interpolated into the `Content-Type` parameter unescaped, … |
| **P2** | [`P2-4`](issues/P2-4.md) | `open` | owner | The native driver appends a caller's custom `To`/`Subject` header … |
| **P2** | [`P2-5`](issues/P2-5.md) | `open` | owner | `SmtpMailer` writes a caller's custom `Bcc` header verbatim into the … |
| **P3** | [`P3-6`](issues/P3-6.md) | `rejected` | reviewer | Mail::getFormattedFrom() does not strip CRLF from fromName, though both … |
| **P3** | [`P3-7`](issues/P3-7.md) | `rejected` | reviewer | SmtpMailer::send() does dot-escaping with str_replace('\r\n.', … |
| **P3** | [`P3-8`](issues/P3-8.md) | `fixed` | reviewer | The README states absolutely that SmtpMailer 'never writes a Bcc: … |

## Verdict

The round-4 fixes are all genuinely in place, but the README’s blanket promise that custom headers override same-named built-ins "in both drivers" is still false for To and Subject in the native driver — the same clause that was filed as P1 in round 4.

## Fixed since the last round

No item was awaiting a verdict; the seven verified items still hold on the code (RFC 2047 subject encoding, the per-line status-code check, the cc/bcc-only and Bcc divergences documented, encryption:’’ stated as plaintext) and both rejections stand.

## Test gaps

The default SocketSmtpTransport success path is never driven by a real socket; NativeMailer’s mail()-returns-false path is never triggered; custom To/Subject/Bcc headers — where this round’s findings hid — have no tests; attachment names/types containing a quote, non-ASCII filenames and display names, byte-level DATA framing, multi-line greetings, AUTH rejection and the ssl branch are all uncovered.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-mail — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（6th round，2026-10-01）。

| | |
|---|---|
| 状态 | **P1 待修** |
| 体量 | src 529 行（净）· 95 个用例 · 7 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 1 · P2 5 · P3 3 · 其他 0 |
| 已了结 | 7 / 16 |
| 等模块主 | `P2-3`, `P2-4`, `P2-5` |
| 等协调人 | _无_ |
| 等评审方 | `P1-2`, `P2-1`, `P2-2`, `P3-6`, `P3-7`, `P3-8` |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P1-1`](issues/P1-1.md) | P1 | **verified** | README 承诺「两个驱动里自定义头都覆盖同名内置头」。实测 … |
| [`P1-2`](issues/P1-2.md) | P1 | **fixed** | README 称同名自定义头「在两个驱动里」都覆盖内置头。对 To 与 Subject，原生驱动并不覆盖：mail() 仍收到真实的 To 与 … |
| [`P2-1`](issues/P2-1.md) | P2 | **fixed** | 非 ASCII 的附件名与显示名在两个驱动里都按原字节输出：附件名没有 RFC 2231 的 filename* 续行，From 显示名没有 … |
| [`P2-2`](issues/P2-2.md) | P2 | **fixed** | 附件的 name 与 type 被直接插进带引号的 MIME 参数、未转义 " 或 … |
| [`P2-3`](issues/P2-3.md) | P2 | **open** | `charset` 未转义地被插进 `Content-Type` 参数，因此带引号的取值会破坏参数、并向同一头注入额外参数——与 P2-2 … |
| [`P2-4`](issues/P2-4.md) | P2 | **open** | 原生驱动会把调用方自定义的 `To`/`Subject` 头追加在 `mail()` … |
| [`P2-5`](issues/P2-5.md) | P2 | **open** | `SmtpMailer` 会把调用方自定义的 `Bcc` 头原样写进报文，而这份报文会发给每一个收件人——于是 Bcc … |
| [`P3-1`](issues/P3-1.md) | P3 | **verified** | 仅 cc/bcc 的邮件 SmtpMailer 接受、NativeMailer 抛「No recipient specified」——同一个 … |
| [`P3-2`](issues/P3-2.md) | P3 | **verified** | NativeMailer 会写出 Bcc: 头，而 SmtpMailer 刻意不写，因此 Bcc 是否泄露取决于本地 MTA 是否剥离该头。 |
| [`P3-3`](issues/P3-3.md) | P3 | **verified** | 非 ASCII 主题两驱动不同：SmtpMailer 做 RFC 2047 base64 编码，NativeMailer 把原始字符串交给 … |
| [`P3-4`](issues/P3-4.md) | P3 | **verified** | expect() 只校验首行状态码；像 250-x\r\n550 bad\r\n 这样的续行混用会在 550 行结束循环并把整段当成功。 |
| [`P3-5`](issues/P3-5.md) | P3 | **verified** | encryption 为空串且提供凭据时仍以明文发送 AUTH LOGIN，而 README 称凭据「绝不会以未加密方式传输」。 |
| [`P3-6`](issues/P3-6.md) | P3 | **rejected** | Mail::getFormattedFrom() 未对 fromName 做 CRLF 剥离，虽然两个 mailer 在最终头值上都有 … |
| [`P3-7`](issues/P3-7.md) | P3 | **rejected** | SmtpMailer::send() 通过 str_replace 做点转义，若正文第一行以 "." … |
| [`P3-8`](issues/P3-8.md) | P3 | **fixed** | README 绝对化地声称 SmtpMailer「绝不写出 Bcc: 头」。但自定义 Bcc 头会被原样写出（SmtpMailer … |
| [`G2`](issues/G2.md) | - | **verified** | 严格开关：`phpunit.xml.dist` 目前已开启 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **9** / 16 |
| 按状态 | `open` 3 · `rejected` 2 · `fixed` 4 |
| 等在谁 | 模块主 3 · 评审方 6 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P1** | [`P1-2`](issues/P1-2.md) | `fixed` | 评审方 | README 称同名自定义头「在两个驱动里」都覆盖内置头。对 To 与 Subject，原生驱动并不覆盖：mail() 仍收到真实的 To 与 … |
| **P2** | [`P2-1`](issues/P2-1.md) | `fixed` | 评审方 | 非 ASCII 的附件名与显示名在两个驱动里都按原字节输出：附件名没有 RFC 2231 的 filename* 续行，From 显示名没有 … |
| **P2** | [`P2-2`](issues/P2-2.md) | `fixed` | 评审方 | 附件的 name 与 type 被直接插进带引号的 MIME 参数、未转义 " 或 … |
| **P2** | [`P2-3`](issues/P2-3.md) | `open` | 模块主 | `charset` 未转义地被插进 `Content-Type` 参数，因此带引号的取值会破坏参数、并向同一头注入额外参数——与 P2-2 … |
| **P2** | [`P2-4`](issues/P2-4.md) | `open` | 模块主 | 原生驱动会把调用方自定义的 `To`/`Subject` 头追加在 `mail()` … |
| **P2** | [`P2-5`](issues/P2-5.md) | `open` | 模块主 | `SmtpMailer` 会把调用方自定义的 `Bcc` 头原样写进报文，而这份报文会发给每一个收件人——于是 Bcc … |
| **P3** | [`P3-6`](issues/P3-6.md) | `rejected` | 评审方 | Mail::getFormattedFrom() 未对 fromName 做 CRLF 剥离，虽然两个 mailer 在最终头值上都有 … |
| **P3** | [`P3-7`](issues/P3-7.md) | `rejected` | 评审方 | SmtpMailer::send() 通过 str_replace 做点转义，若正文第一行以 "." … |
| **P3** | [`P3-8`](issues/P3-8.md) | `fixed` | 评审方 | README 绝对化地声称 SmtpMailer「绝不写出 Bcc: 头」。但自定义 Bcc 头会被原样写出（SmtpMailer … |

## 结论

第四轮的修复确实全部在位，但 README 关于「两个驱动里同名自定义头都覆盖内置头」的笼统承诺，对原生驱动的 To 与 Subject 仍然不成立——正是第四轮已按 P1 立案的那一句。

## 本轮已修复确认

No item was awaiting a verdict; the seven verified items still hold on the code (RFC 2047 subject encoding, the per-line status-code check, the cc/bcc-only and Bcc divergences documented, encryption:’’ stated as plaintext) and both rejections stand.

## 测试盲区

默认的 SocketSmtpTransport 成功路径从未被真实 socket 驱动；NativeMailer 的「mail() 返回 false」分支从未触发；自定义 To/Subject/Bcc 头——本轮发现正藏在这里——无用例；含引号的附件名/类型、非 ASCII 附件名与显示名、字节级 DATA 帧、多行问候语、AUTH 被拒与 ssl 分支均未覆盖。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
