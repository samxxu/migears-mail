# migears-mail — Known Issues / 已知问题

> Summary of this module's issues. The items themselves are in [`issues/`](issues/README.md), one file
> per item: a front-matter header and a thread. This file is generated from them and can be rewritten at
> any time; edit an item, never this file.
>
> 本模块问题的概览。条目本体在 [`issues/`](issues/README.md)，一条目一文件：前置字段加讨论串。
> 本文件由条目生成，随时可以整段重写；请改条目，不要改本文件。
>
> From the miGears Full-Module Code Review Report (4th round, 2026-09-27).

| | |
|---|---|
| Status / 状态 | **P1 open / P1 待修** |
| Size / 体量 | src 652 lines (513 net) · 88 tests · 7 src files |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## At a glance / 状态一览

| | |
|---|---|
| Items / 条目 | P0 0 · P1 1 · P2 0 · P3 5 · other 1 |
| Answered / 已回复 | 7 of 7 |
| Waiting / 等待回复 | _nothing / 无_ |

| id | level | status | title |
|---|---|---|---|
| [`P1-1`](issues/P1-1.md) | P1 | **fixed** | The README promises custom headers override same-named built-in ones … |
| [`P3-1`](issues/P3-1.md) | P3 | **fixed** | A cc/bcc-only message is accepted by SmtpMailer but rejected by … |
| [`P3-2`](issues/P3-2.md) | P3 | **fixed** | NativeMailer writes a `Bcc:` header while SmtpMailer deliberately omits … |
| [`P3-3`](issues/P3-3.md) | P3 | **fixed** | Non-ASCII subjects diverge: SmtpMailer applies RFC 2047 base64 … |
| [`P3-4`](issues/P3-4.md) | P3 | **fixed** | `expect()` validates only the first line's status code; a continuation … |
| [`P3-5`](issues/P3-5.md) | P3 | **fixed** | With `encryption: ''` and credentials present, `AUTH LOGIN` still goes … |
| [`G2`](issues/G2.md) | - | **fixed** | Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, … |

## Verdict / 结论

The driver asymmetry is largely cured and the SMTP session logic is now properly defensive. One README promise is still false for one driver, and the remaining items are all driver-to-driver inconsistencies rather than crashes.

两驱动的不对称基本治好，SMTP 会话逻辑的防御也补齐了。仍有一条 README 承诺对其中一个驱动不成立，其余问题都属于「两驱动行为不一致」而非直接出错。

## Fixed since the last round / 本轮已修复确认

上一轮的 NativeMailer 两大硬伤确已修复：subject 现在过 stripCrlf 并有测试，附件走 multipart/mixed 且缺文件会抛异常。SmtpMailer 侧也全部补齐：缺一凭据即抛、STARTTLS 大小写与末行形态、cc-only 不再输出空 To、expect() 支撑裸状态码与截断多行、公共构造器补上地址校验。 

## Test gaps / 测试盲区

No test asserts what actually reaches `mail()` (the existing CRLF test calls the private helper via reflection); no cross-driver assertion for custom-vs-builtin header precedence (which is how the P1 escaped); no NativeMailer cc-only case; no plaintext-AUTH case; no non-ASCII subject case for NativeMailer.

无「实际传给 mail() 的实参」断言（现有 CRLF 用例只是反射调用私有方法）；无「自定义头 vs 内置头优先级」的两驱动对拍断言（P1 由此逃逸）；无 NativeMailer 仅 cc/bcc 用例；无明文 AUTH 用例；无 NativeMailer 非 ASCII 主题用例。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: all four on
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。
