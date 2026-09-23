# migears/mail

![Version](https://img.shields.io/badge/version-2.0.1-blue)

A minimalist email sending library with zero required dependencies.

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

- **NativeMailer** - Uses PHP's native `mail()` function
- **SmtpMailer** - Implements SMTP protocol using `fsockopen`, supports TLS/SSL and LOGIN authentication

Both drivers require a non-empty sender (`from`). If `SmtpMailer` is configured with `encryption: 'tls'` but the server does not advertise `STARTTLS`, it throws `MailException` rather than falling back to plaintext — credentials are never transmitted unencrypted.

For testing, `SmtpMailer` accepts an optional injected `MiGears\Mail\Transport\SmtpTransport` (see the `SocketSmtpTransport` default implementation).

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

![Version](https://img.shields.io/badge/version-2.0.1-blue)

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

- **NativeMailer** - 使用 PHP 原生 `mail()` 函数
- **SmtpMailer** - 使用 `fsockopen` 实现 SMTP 协议，支持 TLS/SSL 和 LOGIN 认证

两个驱动都要求非空发件人（`from`）。若 `SmtpMailer` 配置了 `encryption: 'tls'` 但服务端未宣告 `STARTTLS`，会抛出 `MailException` 而非回退为明文——凭据绝不会以未加密方式传输。

为便于测试，`SmtpMailer` 接受可选注入的 `MiGears\Mail\Transport\SmtpTransport`（默认实现为 `SocketSmtpTransport`）。

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
