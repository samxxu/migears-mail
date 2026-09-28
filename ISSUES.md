# migears-mail — Known Issues

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> From the miGears Full-Module Code Review Report (5th round, 2026-09-28).

| | |
|---|---|
| Status | **Best state** |
| Size | src 528 lines (net) · 95 tests · 7 src files |

Legend — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs

## At a glance

| | |
|---|---|
| Unsettled | P0 0 · P1 1 · P2 0 · P3 5 · other 1 |
| Settled | 0 of 7 |
| Waiting on the owner | _nothing_ |
| Waiting on the reviewer | `P1-1`, `P3-1`, `P3-2`, `P3-3`, `P3-4`, `P3-5`, `G2` |
| Waiting on the coordinator | _nothing_ |
| Deferred, owing nobody | _nothing_ |

| id | level | status | title |
|---|---|---|---|
| [`P1-1`](issues/P1-1.md) | P1 | **fixed** | The README promises custom headers override same-named built-in ones … |
| [`P3-1`](issues/P3-1.md) | P3 | **fixed** | A cc/bcc-only message is accepted by SmtpMailer but rejected by … |
| [`P3-2`](issues/P3-2.md) | P3 | **fixed** | NativeMailer writes a `Bcc:` header while SmtpMailer deliberately omits … |
| [`P3-3`](issues/P3-3.md) | P3 | **fixed** | Non-ASCII subjects diverge: SmtpMailer applies RFC 2047 base64 … |
| [`P3-4`](issues/P3-4.md) | P3 | **fixed** | `expect()` validates only the first line's status code; a continuation … |
| [`P3-5`](issues/P3-5.md) | P3 | **fixed** | With `encryption: ''` and credentials present, `AUTH LOGIN` still goes … |
| [`G2`](issues/G2.md) | - | **fixed** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Unclosed

What is left to do here: every item whose `status` is not `verified` or `closed`,
highest severity first. `waiting on` is the party who acts next, read from that status.

| | |
|---|---|
| Unclosed | **7** of 7 |
| By status | `fixed` 7 |
| Waiting on | reviewer 7 |

| level | item | status | waiting on | title |
|---|---|---|---|---|
| **P1** | [`P1-1`](issues/P1-1.md) | `fixed` | reviewer | The README promises custom headers override same-named built-in ones … |
| **P3** | [`P3-1`](issues/P3-1.md) | `fixed` | reviewer | A cc/bcc-only message is accepted by SmtpMailer but rejected by … |
| **P3** | [`P3-2`](issues/P3-2.md) | `fixed` | reviewer | NativeMailer writes a `Bcc:` header while SmtpMailer deliberately omits … |
| **P3** | [`P3-3`](issues/P3-3.md) | `fixed` | reviewer | Non-ASCII subjects diverge: SmtpMailer applies RFC 2047 base64 … |
| **P3** | [`P3-4`](issues/P3-4.md) | `fixed` | reviewer | `expect()` validates only the first line's status code; a continuation … |
| **P3** | [`P3-5`](issues/P3-5.md) | `fixed` | reviewer | With `encryption: ''` and credentials present, `AUTH LOGIN` still goes … |
| **-** | [`G2`](issues/G2.md) | `fixed` | reviewer | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Verdict

A well-engineered mailer with consistent behavior between SmtpMailer and NativeMailer on all documented divergence points. Only minor defense-in-depth gaps remain.

## Fixed since the last round

All six prior items confirmed fixed: P1-1 custom headers now override built-in in both drivers; P3-1 through P3-5 driver differences documented, RFC 2047 subject encoding added, expect() line-code validation added; G2 strict flags complete.

## Test gaps

No integration test against a real SMTP server (all tests use transport mock); no test for attachment with non-ASCII filename encoding; no test for very long subject line folding.

## Verification protocol

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.


---

# migears-mail — 已知问题

> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> 出自 miGears 全模块代码评审报告（5th round，2026-09-28）。

| | |
|---|---|
| 状态 | **状态最好** |
| 体量 | src 528 行（净）· 95 个用例 · 7 个源文件 |

级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## 状态一览

| | |
|---|---|
| 未了结 | P0 0 · P1 1 · P2 0 · P3 5 · 其他 1 |
| 已了结 | 0 / 7 |
| 等负责人 | _无_ |
| 等评审方 | `P1-1`, `P3-1`, `P3-2`, `P3-3`, `P3-4`, `P3-5`, `G2` |
| 等协调人 | _无_ |
| 已暂缓，不欠谁 | _无_ |

| id | 级别 | 状态 | 标题 |
|---|---|---|---|
| [`P1-1`](issues/P1-1.md) | P1 | **fixed** | README 承诺「两个驱动里自定义头都覆盖同名内置头」。实测 … |
| [`P3-1`](issues/P3-1.md) | P3 | **fixed** | 仅 cc/bcc 的邮件 SmtpMailer 接受、NativeMailer 抛「No recipient specified」——同一个 … |
| [`P3-2`](issues/P3-2.md) | P3 | **fixed** | NativeMailer 会写出 Bcc: 头，而 SmtpMailer 刻意不写，因此 Bcc 是否泄露取决于本地 MTA 是否剥离该头。 |
| [`P3-3`](issues/P3-3.md) | P3 | **fixed** | 非 ASCII 主题两驱动不同：SmtpMailer 做 RFC 2047 base64 编码，NativeMailer 把原始字符串交给 … |
| [`P3-4`](issues/P3-4.md) | P3 | **fixed** | expect() 只校验首行状态码；像 250-x\r\n550 bad\r\n 这样的续行混用会在 550 行结束循环并把整段当成功。 |
| [`P3-5`](issues/P3-5.md) | P3 | **fixed** | encryption 为空串且提供凭据时仍以明文发送 AUTH LOGIN，而 README 称凭据「绝不会以未加密方式传输」。 |
| [`G2`](issues/G2.md) | - | **fixed** | 严格开关：`phpunit.xml.dist` 目前已开启 … |

## 未关闭

本模块还剩什么要做：所有 `status` 不是 `verified` 或 `closed` 的条目，按严重度从高到低。
`waiting on` 是下一步该动手的一方，由其状态读出。

| | |
|---|---|
| 未关闭 | **7** / 7 |
| 按状态 | `fixed` 7 |
| 等在谁 | 评审方 7 |

| 级别 | 条目 | 状态 | 等在谁 | 标题 |
|---|---|---|---|---|
| **P1** | [`P1-1`](issues/P1-1.md) | `fixed` | 评审方 | README 承诺「两个驱动里自定义头都覆盖同名内置头」。实测 … |
| **P3** | [`P3-1`](issues/P3-1.md) | `fixed` | 评审方 | 仅 cc/bcc 的邮件 SmtpMailer 接受、NativeMailer 抛「No recipient specified」——同一个 … |
| **P3** | [`P3-2`](issues/P3-2.md) | `fixed` | 评审方 | NativeMailer 会写出 Bcc: 头，而 SmtpMailer 刻意不写，因此 Bcc 是否泄露取决于本地 MTA 是否剥离该头。 |
| **P3** | [`P3-3`](issues/P3-3.md) | `fixed` | 评审方 | 非 ASCII 主题两驱动不同：SmtpMailer 做 RFC 2047 base64 编码，NativeMailer 把原始字符串交给 … |
| **P3** | [`P3-4`](issues/P3-4.md) | `fixed` | 评审方 | expect() 只校验首行状态码；像 250-x\r\n550 bad\r\n 这样的续行混用会在 550 行结束循环并把整段当成功。 |
| **P3** | [`P3-5`](issues/P3-5.md) | `fixed` | 评审方 | encryption 为空串且提供凭据时仍以明文发送 AUTH LOGIN，而 README 称凭据「绝不会以未加密方式传输」。 |
| **-** | [`G2`](issues/G2.md) | `fixed` | 评审方 | 严格开关：`phpunit.xml.dist` 目前已开启 … |

## 结论

一个设计精良的邮件发送器，SmtpMailer 与 NativeMailer 在所有文档记录的差异点上行为一致。仅剩少量纵深防御层面的缺口。

## 本轮已修复确认

All six prior items confirmed fixed: P1-1 custom headers now override built-in in both drivers; P3-1 through P3-5 driver differences documented, RFC 2047 subject encoding added, expect() line-code validation added; G2 strict flags complete.

## 测试盲区

无真实 SMTP 服务器集成测试（全部用 transport mock）；无附件文件名非 ASCII 编码测试；无长主题行折叠测试。

## 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- `phpunit.xml.dist` 中的 warning/notice/deprecation/risky 开关：四个全开
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
