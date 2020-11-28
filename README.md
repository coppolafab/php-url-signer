# Create signed URLs with an expiring date

[![License: ISC](https://img.shields.io/badge/License-ISC-yellow.svg)](https://opensource.org/licenses/ISC)

## Installation
Install via Composer:

```
composer require coppolafab/php-url-signer
```

## Usage

```php
use coppolafab\UrlSigner\HashHmacUrlSigner;
use DateTimeImmutable;

$urlSigner = new HashHmacUrlSigner('valid'  /** signature key */);

// valid until 2020-09-13T12:26:40+00:00
$expirationDate = (new DateTimeImmutable())->setTimestamp(1600000000);

$signedUrl = $urlSigner->sign('https://example.com/', $expirationDate);
// 'https://example.com/?url_expires_at=1600000000&signature=d6ebe19e590813d94d1b58fe9f9e204a3c5f074ac791dbf0fc2bc3631091f2f1'


$isValid = $urlSigner->verify($signedUrl);
// true, if verified before $expirationDate
```
