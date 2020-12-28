# Create signed URLs with an expiring date

[![License](https://poser.pugx.org/coppolafab/php-url-signer/license)](//packagist.org/packages/coppolafab/php-url-signer)
[![Minimum PHP Version](https://img.shields.io/badge/php-%7E7.4%20%7C%7C%20%7E8.0.0-blue.svg?style=flat)](https://php.net/)
[![Latest Stable Version](https://img.shields.io/packagist/v/coppolafab/php-url-signer.svg)](https://packagist.org/packages/coppolafab/php-url-signer)
[![Mutation testing badge](https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fcoppolafab%2Fphp-url-signer%2Fmain)](https://dashboard.stryker-mutator.io/reports/github.com/coppolafab/php-url-signer/main)
[![Type Coverage](https://shepherd.dev/github/coppolafab/php-url-signer/coverage.svg)](https://shepherd.dev/github/coppolafab/php-url-signer)

## Installation
Install via Composer:

```bash
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

## Testing
- Coding Style: ```$ vendor/bin/phpcs```
- Unit tests: ```$ vendor/bin/phpunit```
- Static analysis - PHPStan: ```$ vendor/bin/phpstan analyse```
- Static analysis - Psalm: ```$ vendor/bin/psalm```
- Mutation Testing - Infection: ```vendor/bin/infection```

### Docker
A docker-compose.yml file is included, with a pre-configured image that builds PHP8 and pcov. To use previous commands with it, prepend the following:

```bash
docker-compose run --rm php-url-signer ...
```
