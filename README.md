# Create signed URLs with an expiring date

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
// 'https://example.com/?url_expire_at=1600000000&signature=70d95e8eb1d4184199bf5cf2b64c630856016d5f52f412fd39a2b8e84e38ab72'


$isValid = $urlSigner->verify($signedUrl);
// true, if verified before $expirationDate
```
