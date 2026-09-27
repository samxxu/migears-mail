# migears/mail

![Version](https://img.shields.io/badge/version-2.0.0-blue)

A minimalist email sending library with zero required dependencies.

> **Background**: miGears is the open-source successor of **TinyGears**, a
> self-developed PHP framework. It was renamed and open-sourced recently because
> the name *TinyGears* is already taken in the open-source community.

## Features

- PHP 8.1+, using modern syntax (readonly, enums, type declarations)
- Zero required dependencies
- Immutable mail message value object
- Fluent chainable API
- Email address validation on set (blocks CRLF header injection)
- Supports HTML emails and attachments
- Two built-in sending drivers: native `mail()` function and SMTP
- SMTP transport can be injected for testing
- PSR-4 autoloading compliant

## Installation

```bash
composer require migears/mail
```

## Quick Start

```php
use MiGears\Mail\Mail;
use MiGears\Mail\NativeMailer;
use MiGears\Mail\SmtpMailer;

// Build the email
$mail = (new Mail())
    ->withFrom('sender@example.com', 'Sender Name')
    ->withTo('recipient@example.com')
    ->withCc('cc@example.com')
    ->withBcc('bcc@example.com')
    ->withSubject('Hello World')
    ->withBody('<p>This is an HTML email</p>', isHtml: true)
    ->withAttachment('/path/to/file.pdf', 'document.pdf', 'application/pdf');

// Send using native mail()
$mailer = new NativeMailer();
$mailer->send($mail);

// Send using SMTP
$smtpMailer = new SmtpMailer(
    host: 'smtp.example.com',
    port: 587,
    username: 'user@example.com',
    password: 'secret',
    encryption: 'tls',
);
$smtpMailer->send($mail);
```

## API Reference

### Mail (Immutable Value Object)

All `with*` methods return a new `Mail` instance; the original instance remains unchanged.

`withFrom` / `withTo` / `withCc` / `withBcc` / `withReplyTo` validate their email addresses (via `filter_var`) and throw `MailException` on invalid input. This also prevents CRLF header injection.

| Method | Description |
|--------|-------------|
| `withFrom(string $email, string $name = '')` | Set the sender |
| `withTo(string ...$emails)` | Set recipients |
| `withCc(string ...$emails)` | Set CC recipients |
| `withBcc(string ...$emails)` | Set BCC recipients |
| `withReplyTo(string $email)` | Set reply-to address |
| `withSubject(string $subject)` | Set the subject |
| `withBody(string $body, bool $isHtml = false)` | Set the body |
| `withCharset(string $charset)` | Set the charset |
| `withHeaders(array $headers)` | Set custom headers |
| `withAttachment(string $path, ?string $name = null, ?string $type = null)` | Add an attachment |
| `hasAttachments(): bool` | Whether there are attachments |
| `getContentType(): string` | Get Content-Type |
| `getFormattedFrom(): string` | Get formatted sender |

### MailerInterface

```php
interface MailerInterface
{
    public function send(Mail $mail): void;
}
```

### Built-in Implementations

- **NativeMailer** - Uses PHP's native `mail()` function, supports attachments (multipart/mixed)
- **SmtpMailer** - Implements SMTP protocol using `fsockopen`, supports TLS/SSL and LOGIN authentication

Both drivers require a non-empty sender (`from`). If `SmtpMailer` is configured with `encryption: 'tls'` and the server does not advertise `STARTTLS`, it throws `MailException` rather than silently downgrading to plaintext. The encryption mode is case-insensitive and any value other than `''`/`tls`/`ssl` is rejected at construction. Providing only a username or only a password also throws rather than silently skipping authentication.

Note that `encryption: ''` is an explicit opt-out: with credentials supplied, `AUTH LOGIN` is then sent in the clear. The no-silent-downgrade guarantee applies to the `tls`/`ssl` modes only — it is not a promise that credentials are encrypted on every configuration.

All user-supplied values that end up in message headers (display name, subject, custom header names/values, charset, attachment names and types) have CR/LF characters stripped at serialization time by the mailer driver, so no injected header line (e.g. `Bcc:`) can be smuggled in. Custom header names containing `:` are rejected. The `Mail` value object itself is wire-format agnostic and performs no CRLF filtering — header-safety is a driver responsibility. Custom headers override built-in ones of the same name in both drivers. A non-ASCII subject is RFC 2047 base64-encoded by both drivers.

The display name is additionally escaped (`\` and `"`) by `Mail::getFormattedFrom()` before being wrapped in quotes, so it cannot break out of `"..."` and inject extra addresses into the `From` header.

The `Mail` constructor validates all email addresses (`from`, `to`, `cc`, `bcc`, `replyTo`) just like the `with*` methods, so `new Mail(to: [...])` is just as safe as `(new Mail())->withTo(...)`.

#### Driver differences

Because `NativeMailer` delegates to PHP's `mail()`, whose first argument always becomes the `To:` header, two capabilities differ from `SmtpMailer`:

- **cc/bcc-only messages.** `SmtpMailer` accepts a message with no `to` recipients (it delivers to the `cc`/`bcc` envelope). `NativeMailer` rejects it with `No recipient specified`, since `mail()` has no way to deliver to a `cc`/`bcc` address without also exposing it as the `To:` header.
- **`Bcc` header.** `SmtpMailer` never writes a `Bcc:` header (it only issues `RCPT TO` for those addresses). `NativeMailer` passes `Bcc:` in the headers and relies on the local MTA to strip it, which is the standard `mail()` practice.

For testing, `SmtpMailer` accepts an optional injected `MiGears\Mail\Transport\SmtpTransport` (see the `SocketSmtpTransport` default implementation); `NativeMailer` exposes a protected `deliver()` seam over `mail()`.

### Exceptions

All sending failures throw `MiGears\Mail\Exception\MailException`.

## Testing

```bash
composer install
vendor/bin/phpunit
```

Tests use `InMemoryMailer` (in-memory implementation) for assertions, no real mail server required.

## License

MIT

---

# migears/mail

![Version](https://img.shields.io/badge/version-2.0.0-blue)

极简邮件发送库，零强制依赖。

## 特性

- PHP 8.1+，使用现代语法（readonly、枚举、类型声明）
- 零强制依赖
- 不可变邮件消息值对象
- 流畅的链式调用 API
- 设置地址时校验邮箱格式（阻断 CRLF 头注入）
- 支持 HTML 邮件和附件
- 内置两种发送驱动：原生 `mail()` 函数和 SMTP
- SMTP 传输层可注入以便测试
- 符合 PSR-4 自动加载规范

## 安装

```bash
composer require migears/mail
```

## 快速开始

```php
use MiGears\Mail\Mail;
use MiGears\Mail\NativeMailer;
use MiGears\Mail\SmtpMailer;

// 构建邮件
$mail = (new Mail())
    ->withFrom('sender@example.com', 'Sender Name')
    ->withTo('recipient@example.com')
    ->withCc('cc@example.com')
    ->withBcc('bcc@example.com')
    ->withSubject('Hello World')
    ->withBody('<p>This is an HTML email</p>', isHtml: true)
    ->withAttachment('/path/to/file.pdf', 'document.pdf', 'application/pdf');

// 使用原生 mail() 发送
$mailer = new NativeMailer();
$mailer->send($mail);

// 使用 SMTP 发送
$smtpMailer = new SmtpMailer(
    host: 'smtp.example.com',
    port: 587,
    username: 'user@example.com',
    password: 'secret',
    encryption: 'tls',
);
$smtpMailer->send($mail);
```

## API 参考

### Mail (不可变值对象)

所有 `with*` 方法返回新的 `Mail` 实例，原实例保持不变。

`withFrom` / `withTo` / `withCc` / `withBcc` / `withReplyTo` 会（通过 `filter_var`）校验邮箱地址，非法输入抛 `MailException`；同时可阻断 CRLF 头注入。

| 方法 | 说明 |
|------|------|
| `withFrom(string $email, string $name = '')` | 设置发件人 |
| `withTo(string ...$emails)` | 设置收件人 |
| `withCc(string ...$emails)` | 设置抄送 |
| `withBcc(string ...$emails)` | 设置密送 |
| `withReplyTo(string $email)` | 设置回复地址 |
| `withSubject(string $subject)` | 设置主题 |
| `withBody(string $body, bool $isHtml = false)` | 设置正文 |
| `withCharset(string $charset)` | 设置字符集 |
| `withHeaders(array $headers)` | 设置自定义头 |
| `withAttachment(string $path, ?string $name = null, ?string $type = null)` | 添加附件 |
| `hasAttachments(): bool` | 是否有附件 |
| `getContentType(): string` | 获取 Content-Type |
| `getFormattedFrom(): string` | 获取格式化的发件人 |

### MailerInterface

```php
interface MailerInterface
{
    public function send(Mail $mail): void;
}
```

### 内置实现

- **NativeMailer** - 使用 PHP 原生 `mail()` 函数，支持附件（multipart/mixed）
- **SmtpMailer** - 使用 `fsockopen` 实现 SMTP 协议，支持 TLS/SSL 和 LOGIN 认证

两个驱动都要求非空发件人（`from`）。若 `SmtpMailer` 配置了 `encryption: 'tls'` 但服务端未宣告 `STARTTLS`，会抛出 `MailException` 而非静默降级为明文。加密模式大小写不敏感，构造时仅接受 `''`/`tls`/`ssl`，其他值直接抛异常。只提供用户名或只提供密码也会抛异常，而不会静默跳过认证。

需要注意：`encryption: ''` 是显式的「不加密」选择——此时若提供了凭据，`AUTH LOGIN` 会以明文发送。「不静默降级」的保证只适用于 `tls`/`ssl` 模式，并不等于「任何配置下凭据都加密」。

所有会进入邮件头的用户输入（显示名、主题、自定义头名与值、charset、附件名与类型）在序列化时由 Mailer 驱动剥离 CR/LF 字符，因此无法注入额外的头部行（如 `Bcc:`）。含冒号的自定义头名会被拒绝。`Mail` 值对象本身与传输格式无关，不做 CRLF 过滤——头部安全是驱动层的职责。两个驱动中同名自定义头均会覆盖内置头；非 ASCII 主题两个驱动都会做 RFC 2047 base64 编码。

显示名的 `\` 与 `"` 转义由 `Mail::getFormattedFrom()` 完成（属格式化职责），因此无法突破 `"..."` 向 `From` 头注入额外地址。

`Mail` 构造器与 `with*` 方法一样会校验全部邮箱地址（`from`、`to`、`cc`、`bcc`、`replyTo`），因此 `new Mail(to: [...])` 与 `(new Mail())->withTo(...)` 同样安全。

#### 驱动差异

由于 `NativeMailer` 依赖 PHP 的 `mail()`，而 `mail()` 的第一个参数总会成为 `To:` 头，因此有两处能力与 `SmtpMailer` 不同：

- **仅 cc/bcc 的邮件。** `SmtpMailer` 接受没有 `to` 收件人的邮件（通过 cc/bcc 信封投递）。`NativeMailer` 会以 `No recipient specified` 拒绝，因为 `mail()` 无法在不让 cc/bcc 地址暴露为 `To:` 头的前提下投递给它们。
- **`Bcc` 头。** `SmtpMailer` 绝不写出 `Bcc:` 头（只为这些地址发 `RCPT TO`）。`NativeMailer` 会把 `Bcc:` 放进头部，依赖本地 MTA 剥离——这是 `mail()` 的标准做法。

为便于测试，`SmtpMailer` 接受可选注入的 `MiGears\Mail\Transport\SmtpTransport`（默认实现为 `SocketSmtpTransport`）；`NativeMailer` 则暴露了一个包装 `mail()` 的 protected `deliver()` 接缝。

### 异常

所有发送失败抛出 `MiGears\Mail\Exception\MailException`。

## 测试

```bash
composer install
vendor/bin/phpunit
```

测试中使用 `InMemoryMailer`（内存实现）进行断言，无需真实邮件服务器。

## License

MIT
