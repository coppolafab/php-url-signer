<?php

declare(strict_types=1);

namespace Tests;

use coppolafab\UrlSigner\Clock;
use coppolafab\UrlSigner\Exception\InvalidSignerKey;
use coppolafab\UrlSigner\Exception\InvalidUrlParameter;
use coppolafab\UrlSigner\HashHmacUrlSigner;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HashHmacUrlSignerTest extends TestCase
{
    public function testInvalidSignerKey(): void
    {
        $this->expectException(InvalidSignerKey::class);

        new HashHmacUrlSigner('');
    }

    public function testInvalidSignatureParameter(): void
    {
        $this->expectException(InvalidUrlParameter::class);

        new HashHmacUrlSigner('valid', '', 'url_expire_at');
    }

    public function testInvalidExpireParameter(): void
    {
        $this->expectException(InvalidUrlParameter::class);

        new HashHmacUrlSigner('valid', 'signature', '');
    }

    public function testSameParameters(): void
    {
        $this->expectException(InvalidUrlParameter::class);

        new HashHmacUrlSigner('valid', 'same', 'same');
    }

    /**
     * @dataProvider reservedParameterProvider
     */
    public function testUrlWithReservedParameter(string $url, DateTimeImmutable $expirationDate): void
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->createDefaultSigner())->sign($url, $expirationDate);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testSignInvalidUrl(string $url, DateTimeImmutable $expirationDate): void
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->createDefaultSigner())->sign($url, $expirationDate);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testVerifyInvalidUrl(string $url, DateTimeImmutable $expirationDate): void
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->createDefaultSigner())->verify($url);
    }

    /**
     * @dataProvider signProvider
     */
    public function testSign(string $url, DateTimeImmutable $expirationDate, string $expectedUrl): void
    {
        $this->assertSame($expectedUrl, ($this->createDefaultSigner())->sign($url, $expirationDate));
    }

    /**
     * @dataProvider signProvider
     */
    public function testVerify(string $url, DateTimeImmutable $expirationDate, string $expectedUrl): void
    {
        // before url expire dates
        $pastClock = new class () implements Clock {
            public function currentTime(): DateTimeImmutable
            {
                return (new DateTimeImmutable())->setTimestamp(1500000000);
            }
        };

        $signer = new HashHmacUrlSigner(
            'valid',
            'signature',
            'url_expire_at',
            $pastClock
        );

        $this->assertTrue($signer->verify($expectedUrl));
    }

    /**
     * @dataProvider expiredUrlProvider
     */
    public function testExpiredUrl(string $signedUrl): void
    {
        $futureClock = new class implements Clock {
            public function currentTime(): DateTimeImmutable
            {
                return (new DateTimeImmutable())->setTimestamp(1700000000);
            }
        };

        $signer = new HashHmacUrlSigner(
            'valid',
            'signature',
            'url_expire_at',
            $futureClock
        );

        $this->assertFalse($signer->verify($signedUrl));
    }

    public function invalidUrlProvider(): array
    {
        $fixedExpirationDate = (new DateTimeImmutable())->setTimestamp(1600000000);

        return [
            ['invalid://', $fixedExpirationDate],
        ];
    }

    public function signProvider(): array
    {
        $fixedExpirationDate = (new DateTimeImmutable())->setTimestamp(1600000000);

        return [
            'http_basic' => [
                'http://example.com',
                $fixedExpirationDate,
                'http://example.com?url_expire_at=1600000000&signature=7b33cadeddce2f68063b02df33a8b462352aebcb0d9262da7bba6a25f45036d4',
            ],
            'https_with_port' => [
                'https://example.com:8080',
                $fixedExpirationDate,
                'https://example.com:8080?url_expire_at=1600000000&signature=eba74d37a150cb9f201db9934d85977f11f73ed754f212a7c4514b5d6a7543b2',
            ],
            'https_with_rootpath' => [
                'https://example.com/',
                $fixedExpirationDate,
                'https://example.com/?url_expire_at=1600000000&signature=70d95e8eb1d4184199bf5cf2b64c630856016d5f52f412fd39a2b8e84e38ab72',
            ],
            'https_with_path' => [
                'https://example.com/path',
                $fixedExpirationDate,
                'https://example.com/path?url_expire_at=1600000000&signature=0bfc67659857a3c4fa3facbf7cac340a5977f0b19294c296f8bf9f282f4e1c5e',
            ],
            'https_with_query' => [
                'https://example.com/path?a',
                $fixedExpirationDate,
                'https://example.com/path?a=&url_expire_at=1600000000&signature=51fe806d1d0496bdec4d1b0e52d3c71da64aee6673b10a46eeaa83f2dfab45f2',
            ],
            'https_with_query2' => [
                'https://example.com/path?a=',
                $fixedExpirationDate,
                'https://example.com/path?a=&url_expire_at=1600000000&signature=51fe806d1d0496bdec4d1b0e52d3c71da64aee6673b10a46eeaa83f2dfab45f2',
            ],
            'https_with_fragment' => [
                'https://example.com/path#a',
                $fixedExpirationDate,
                'https://example.com/path?url_expire_at=1600000000&signature=6493498c459583a24afd04f1434f31b95b88f076b921d7b5af4a560e45e120ff#a',
            ],
            'full' => [
                'https://example.com/path?q=1#a',
                $fixedExpirationDate,
                'https://example.com/path?q=1&url_expire_at=1600000000&signature=daa2aa09642b92569faed19c1dfcaeb438ee5b018e5c58adf1328fed560f039c#a',
            ],
        ];
    }

    public function expiredUrlProvider(): array
    {
        return [
            [
                'https://example.com/path?q=1&url_expire_at=1600000000&signature=daa2aa09642b92569faed19c1dfcaeb438ee5b018e5c58adf1328fed560f039c#a',
            ],
        ];
    }

    public function reservedParameterProvider(): array
    {
        $fixedExpirationDate = (new DateTimeImmutable())->setTimestamp(1600000000);

        return [
            [
                'http://example.com?url_expire_at=1',
                $fixedExpirationDate,
            ],
            [
                'http://example.com?signature=1',
                $fixedExpirationDate,
            ],
        ];
    }

    private function createDefaultSigner(): HashHmacUrlSigner
    {
        return new HashHmacUrlSigner('valid', 'signature', 'url_expire_at');
    }
}
