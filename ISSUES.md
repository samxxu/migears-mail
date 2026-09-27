# migears-mail — Known Issues / 已知问题

> Generated from the miGears Full-Module Code Review Report (4th round, 2026-09-27).
> This file has two regions. Everything above **Owner feedback** is generated from the report — do
> not edit it there. The **Owner feedback** region belongs to the module maintainer: write into it,
> and it is preserved verbatim when the file is regenerated.
> A `fixed` reply is verified against the code by the reviewer before the finding is closed; a
> `rejected` reply is either accepted as a false positive or answered with counter-evidence.
>
> 本文件分两个区域。**「负责人反馈」之前的全部内容**由评审报告生成，请勿在该区修改；
> **「负责人反馈」区**归模块负责人所有，重新生成时会原样保留。
> 标注 `fixed`（已修复）的回复会被评审对照代码核实后才关闭；标注 `rejected`（不认同）的，
> 评审要么采纳为误报，要么给出反驳证据。
>
> 摘自 miGears 全模块代码评审报告（第四轮，2026-09-27）。

| | |
|---|---|
| Status / 状态 | **P1 open / P1 待修** |
| Findings / 问题 | P0 0 · P1 1 · P2 0 · P3 5 |
| Size / 体量 | src 652 lines (513 net) · 88 tests · 7 src files |

Legend / 图例 — **P0** functional or security · **P1** documentation that fails when copied · **P2** robustness · **P3** metadata and docs
级别说明 — **P0** 功能性或安全级 · **P1** 文档照抄即错 · **P2** 健壮性 · **P3** 元数据与文档

## Verdict / 结论

The driver asymmetry is largely cured and the SMTP session logic is now properly defensive. One README promise is still false for one driver, and the remaining items are all driver-to-driver inconsistencies rather than crashes.

两驱动的不对称基本治好，SMTP 会话逻辑的防御也补齐了。仍有一条 README 承诺对其中一个驱动不成立，其余问题都属于「两驱动行为不一致」而非直接出错。

## Fixed since the last round / 本轮已修复确认

上一轮的 NativeMailer 两大硬伤确已修复：subject 现在过 stripCrlf 并有测试，附件走 multipart/mixed 且缺文件会抛异常。SmtpMailer 侧也全部补齐：缺一凭据即抛、STARTTLS 大小写与末行形态、cc-only 不再输出空 To、expect() 支撑裸状态码与截断多行、公共构造器补上地址校验。 

## Open findings / 未修问题


### P1

**P1-1** — `README:101,224 vs src/SmtpMailer.php:113-122, src/NativeMailer.php:105-108`

- EN: The README promises custom headers override same-named built-in ones "in both drivers". Measured with `withHeaders(["X-Mailer"=>"CUSTOM","Content-Type"=>"text/x-custom"])`: NativeMailer lets both win, SmtpMailer lets `X-Mailer` win but `Content-Type` and `Content-Transfer-Encoding` stay built-in, because `buildMessage()` writes them after the custom headers.
- 中文: README 承诺「两个驱动里自定义头都覆盖同名内置头」。实测 withHeaders(["X-Mailer"=>"CUSTOM","Content-Type"=>"text/x-custom"])：NativeMailer 两者都覆盖，SmtpMailer 的 X-Mailer 覆盖而 Content-Type 与 Content-Transfer-Encoding 仍是内置头，因为 buildMessage() 在自定义头之后才写它们。
- Verification / 验证: reproduced / 已实证


### P3

**P3-1** — `src/NativeMailer.php:16-18 vs src/SmtpMailer.php:39-41`

- EN: A cc/bcc-only message is accepted by SmtpMailer but rejected by NativeMailer with "No recipient specified" — the same `Mail` object has different capabilities per driver, undocumented.
- 中文: 仅 cc/bcc 的邮件 SmtpMailer 接受、NativeMailer 抛「No recipient specified」——同一个 Mail 对象在两个驱动下能力不同，文档未说明。
- Verification / 验证: reproduced / 已实证

**P3-2** — `src/NativeMailer.php:92-94 vs src/SmtpMailer.php:113`

- EN: NativeMailer writes a `Bcc:` header while SmtpMailer deliberately omits it, so Bcc exposure depends on the local MTA stripping the header.
- 中文: NativeMailer 会写出 Bcc: 头，而 SmtpMailer 刻意不写，因此 Bcc 是否泄露取决于本地 MTA 是否剥离该头。
- Verification / 验证: reproduced / 已实证

**P3-3** — `src/SmtpMailer.php:168-173 vs src/NativeMailer.php:24`

- EN: Non-ASCII subjects diverge: SmtpMailer applies RFC 2047 base64 encoding, NativeMailer passes the raw string to `mail()`.
- 中文: 非 ASCII 主题两驱动不同：SmtpMailer 做 RFC 2047 base64 编码，NativeMailer 把原始字符串交给 mail()。
- Verification / 验证: reproduced / 已实证

**P3-4** — `src/SmtpMailer.php:219-229`

- EN: `expect()` validates only the first line's status code; a continuation mix like `250-x\r\n550 bad\r\n` ends the loop on the 550 line and the whole response is treated as success.
- 中文: expect() 只校验首行状态码；像 250-x\r\n550 bad\r\n 这样的续行混用会在 550 行结束循环并把整段当成功。
- Verification / 验证: static / 仅静态推断

**P3-5** — `src/SmtpMailer.php:80-91 vs README:99`

- EN: With `encryption: ""` and credentials present, `AUTH LOGIN` still goes out in the clear, while the README states credentials "are never transmitted unencrypted".
- 中文: encryption 为空串且提供凭据时仍以明文发送 AUTH LOGIN，而 README 称凭据「绝不会以未加密方式传输」。
- Verification / 验证: reproduced / 已实证

## Test gaps / 测试盲区

No test asserts what actually reaches `mail()` (the existing CRLF test calls the private helper via reflection); no cross-driver assertion for custom-vs-builtin header precedence (which is how the P1 escaped); no NativeMailer cc-only case; no plaintext-AUTH case; no non-ASCII subject case for NativeMailer.

无「实际传给 mail() 的实参」断言（现有 CRLF 用例只是反射调用私有方法）；无「自定义头 vs 内置头优先级」的两驱动对拍断言（P1 由此逃逸）；无 NativeMailer 仅 cc/bcc 用例；无明文 AUTH 用例；无 NativeMailer 非 ASCII 主题用例。

## Verification protocol / 验证方式

- `./vendor/bin/phpunit` · `composer analyse` · `composer validate`
- Warning/notice/deprecation/risky flags in `phpunit.xml.dist`: on: Warning, Risky
- A PHP warning counts as a test failure only where those flags are on; otherwise run `./vendor/bin/phpunit --fail-on-warning` explicitly.
- 只有在上述开关打开时 PHP 警告才会导致套件失败；否则请显式加 `--fail-on-warning`。

## Owner feedback / 负责人反馈

<!-- OWNER-FEEDBACK:BEGIN -->
<!-- 渠道说明 / channel notice — 跨模块协调人发布，长期有效 / issued by the cross-module coordinator, standing
     ISSUES.md 是本模块「完整」的问题讨论与修复渠道，不只是评审结论的存放处。
     ISSUES.md is this module's COMPLETE issue-discussion-and-fix channel, not merely where review verdicts land.

     1. 每位负责人只对自己模块负责。对别的模块有意见、疑问、反证或改动建议，写入「对方模块」的 ISSUES.md，
        不要写在自己模块里。
        Each owner is responsible for their own module only. Opinions, questions, counter-evidence and
        change requests about ANOTHER module go into THAT module's ISSUES.md, never into your own.
     2. 在对方模块的文件里注明你是谁：模块名 + 身份。署名是硬要求，不署名则无法追溯来源。
        Sign it in the other module's file: your module name and your role. Signing is mandatory; an
        unsigned entry cannot be traced back to its author.
     3. 署名格式 / signature forms, so the source is distinguishable:
          reviewer — migears-full-review   评审方
          coordinator — cross-module       跨模块协调人
          owner — migears-<module>         其他模块负责人
     4. 结论文本一律带状态词：accepted / fixed / rejected / deferred / question / new-evidence。
        无署名条目下一轮可能被按新发现重新评级。
        Sign conclusions with one status word: accepted / fixed / rejected / deferred / question /
        new-evidence. An unsigned entry may be re-graded as a new finding in the next round.
     5. 开工之前先通读本文件：把每条开启条目按证据评估（签名条目也算），再把你接受的条目与自己的工作一并执行，
        不要拆成两轮。每条都要有状态词。
        Read this file before starting work: evaluate every open item on its evidence, signed entries
        included, then execute the ones you accept together with your own work in one pass. Every item
        gets a status word. -->

<!-- Maintainers: reply under each finding's `### <id>` heading and keep the headings, so the
     reviewer can map your reply to the finding. Status vocabulary, one word followed by your
     reasoning and any evidence:
       accepted      you agree; it will be fixed
       fixed         you believe it is already fixed in the code (the reviewer verifies this)
       rejected      you disagree — give the reason; the reviewer either accepts it as a false
                     positive or answers with counter-evidence
       deferred      deliberate, out of scope for now — give the reason
       question      you need a decision or clarification first
       new-evidence  you have additional facts bearing on the finding
     You may also add findings of your own under `### New — <short title>`.

     负责人：请在对应 `### <编号>` 标题下逐条回复，并保留标题以便评审对应。
     状态词（一个词 + 理由与证据）：
       accepted      认同，将会修复
       fixed         认为代码里已经修好（评审会对照代码核实）
       rejected      不认同——请给理由；评审要么采纳为误报，要么给出反驳证据
       deferred      有意暂缓或超出范围——请给理由
       question      需要先明确或决策
       new-evidence  补充与本次结论相关的新事实
     也欢迎在 `### New — <简短标题>` 下补充你发现的问题。 -->

### P1-1
<!-- 负责人反馈 / owner response here -->

- `fixed` — both drivers now apply custom headers *after* the built-ins, so a same-named custom header wins in each. `src/NativeMailer.php:114-117` and `src/SmtpMailer.php:121-125` (the latter no longer writes `Content-Type` / `Content-Transfer-Encoding` after the custom loop). Commit `4f416eb` ("Fix round-4 audit findings and add a NativeMailer test seam"). / 两个驱动都在内置头之后应用自定义头，同名自定义头均可覆盖内置头。
- Evidence / 证据: `tests/SmtpMailerTest.php::testCustomHeaderOverridesContentTypeAndCte` asserts `Content-Type: text/x-custom` is present once and `text/plain` absent; `tests/NativeMailerTest.php::testCustomHeadersOverrideBuiltInOnes` asserts the same for NativeMailer. The README statement (line 235) now matches. / 两个驱动各有对拍用例，README 已一致。
- owner — migears-mail

### P3-1
<!-- 负责人反馈 / owner response here -->

- `fixed` — the divergence is now documented rather than silent: README lines 113 / 245 state that `SmtpMailer` accepts a cc/bcc-only message (delivering to the cc/bcc envelope) while `NativeMailer` rejects it, because `mail()` cannot deliver to a cc/bcc address without exposing it in `To:`. `tests/NativeMailerTest.php` asserts the rejection. Commit `4f416eb`. / 该差异已写入 README，NativeMailer 的拒绝行为有测试固定。
- owner — migears-mail

### P3-2
<!-- 负责人反馈 / owner response here -->

- `fixed` — documented: README lines 114 / 246 state that `SmtpMailer` never writes a `Bcc:` header while `NativeMailer` passes it and relies on the local MTA to strip it (standard `mail()` practice). Commit `4f416eb`. / Bcc 头差异已在 README 记明。
- owner — migears-mail

### P3-3
<!-- 负责人反馈 / owner response here -->

- `fixed` — `NativeMailer` now RFC 2047 base64-encodes a non-ASCII subject (`src/NativeMailer.php:127-132`), matching `SmtpMailer` (`src/SmtpMailer.php:171-176`); README line 235 records that both drivers encode. Commit `4f416eb`. / 非 ASCII 主题两个驱动均做 RFC 2047 编码。
- owner — migears-mail

### P3-4
<!-- 负责人反馈 / owner response here -->

- `fixed` — `expect()` now validates the status code of *every* line, continuation or final (`src/SmtpMailer.php:222-238`); a `250-x\r\n550 bad` mix throws `SMTP error: expected 250, got 550` instead of ending the loop on the 550 line. Commit `4f416eb`. / `expect()` 校验每一行的状态码，续行混用会抛错。
- Evidence / 证据: `tests/SmtpMailerTest.php::testExpectHandlesBareStatusCodeLine`, `::testExpectTruncatedMultilineResponseThrows`, and the `expected 250, got 550` case (line 662). / 相关用例见 SmtpMailerTest。
- owner — migears-mail

### P3-5
<!-- 负责人反馈 / owner response here -->

- `fixed` — the README promise is now precise: lines 101 / 233 state that `encryption: ''` is an explicit opt-out, that credentials are then sent in the clear, and that the no-silent-downgrade guarantee covers only `tls`/`ssl`. Commit `4f416eb`. / README 已明确 `encryption: ''` 为显式不加密、凭据明文发送。
- owner — migears-mail
<!-- 跨模块条目 / cross-module items — 由跨模块协调人提出，非本轮评审 finding。口径见工作区根目录 `migears-engineering-gates.md`。
      Filed by the cross-module coordinator, not by the round's review. Standard: `migears-engineering-gates.md` at the workspace root. -->

### G2

- EN: Strict flags: `phpunit.xml.dist` currently sets `failOnWarning`, `failOnRisky`. The standard is all five — `failOnWarning`, `failOnNotice`, `failOnDeprecation`, `failOnRisky`, `beStrictAboutOutputDuringTests` — which 11 of 27 modules set. Missing here: `failOnNotice`, `failOnDeprecation`, `beStrictAboutOutputDuringTests`. Turn them on and make the suite green; run `./vendor/bin/phpunit` and `composer analyse` before and after, and expect the first run to surface real warnings. If a flag genuinely cannot be turned on, reply `deferred` with the failing test and the reason instead of leaving the suite red.
- 中文: 严格开关：`phpunit.xml.dist` 目前已开启 `failOnWarning`、`failOnRisky`。标准是五个全开——`failOnWarning`、`failOnNotice`、`failOnDeprecation`、`failOnRisky`、`beStrictAboutOutputDuringTests`——27 个模块中 11 个如此。本模块缺 `failOnNotice`、`failOnDeprecation`、`beStrictAboutOutputDuringTests`。请打开并让套件保持全绿；改动前后各跑一次 `./vendor/bin/phpunit` 与 `composer analyse`，第一次跑出真警告是预期内的。若某个开关确实无法打开，请回复 `deferred` 并给出失败的用例与原因，而不是把套件留在红灯状态。
- Reply with one status word (`accepted` / `fixed` / `rejected` / `deferred` / `question`). / 请回复一个状态词（`accepted` / `fixed` / `rejected` / `deferred` / `question`）。
coordinator — cross-module

- `fixed` — `phpunit.xml.dist` now sets all five strict flags: the two it already had (`failOnWarning`, `failOnRisky`) plus `failOnNotice`, `failOnDeprecation`, `beStrictAboutOutputDuringTests`, and the three matching `displayDetailsOnTestsThatTriggerWarnings` / `…Notices` / `…Deprecations` attributes. `colors` and the `<testsuites>` / `<source>` blocks are unchanged (attribute order only). / 五个开关全开，并在原有两项之外补齐缺失三项与三个 `displayDetails…` 属性；其余结构未动。
- Evidence / 证据:
  - before / 改动前: `./vendor/bin/phpunit` → `OK (95 tests, 213 assertions)`, exit 0.
  - after / 改动后: `./vendor/bin/phpunit` → `OK (95 tests, 213 assertions)`, exit 0 (no warning, notice or deprecation surfaced, so the flags cost nothing here). / 未出现警告、通知或弃用，开关成本为零。
  - `./vendor/bin/phpstan analyse --no-progress` → `[OK] No errors`.
  - the identical flag set was proven live in `migears-log` with a temporary probe (echo + `E_USER_NOTICE` → exit 1); the probe was removed. / 同款开关已在 migears-log 用临时探针证明生效。
- The change is committed in the migears-mail gate commit that carries this file (hash in the module hand-off). / 改动随本文件所在的那次 migears-mail 门禁提交。
- owner — migears-mail

<!-- OWNER-FEEDBACK:END -->
