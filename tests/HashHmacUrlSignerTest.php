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

use function version_compare;

use const PHP_VERSION;

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

        new HashHmacUrlSigner('valid', '', 'url_expires_at');
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
    public function testVerifyInvalidUrl(string $url): void
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
            'url_expires_at',
            $pastClock
        );

        $this->assertTrue($signer->verify($expectedUrl));
    }

    public function testExpiredUrl(): void
    {
        $futureClock = new class implements Clock {
            public function currentTime(): DateTimeImmutable
            {
                return (new DateTimeImmutable())->setTimestamp(1600000001);
            }
        };

        $signer = new HashHmacUrlSigner(
            'valid',
            'signature',
            'url_expires_at',
            $futureClock
        );

        $signedUrl = $signer->sign('https://example.com/path?q=1', (new DateTimeImmutable())->setTimestamp(1600000000));

        $this->assertFalse($signer->verify($signedUrl));
    }

    public function testNotExpiredUrl(): void
    {
        $sameClock = new class implements Clock {
            public function currentTime(): DateTimeImmutable
            {
                return (new DateTimeImmutable())->setTimestamp(1600000000);
            }
        };

        $signer = new HashHmacUrlSigner(
            'valid',
            'signature',
            'url_expires_at',
            $sameClock
        );

        $signedUrl = $signer->sign('https://example.com/path?q=1', (new DateTimeImmutable())->setTimestamp(1600000000));

        $this->assertTrue($signer->verify($signedUrl));
    }

    /**
     * @dataProvider missingSignerParameterProvider
     */
    public function testMissingSignerParameter(string $signedUrl): void
    {
        $this->assertFalse(($this->createDefaultSigner())->verify($signedUrl));
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
        $onPHP7 = version_compare(PHP_VERSION, '8', '<');

        return [
            'http_basic' => [
                'http://example.com',
                $fixedExpirationDate,
                'http://example.com?url_expires_at=1600000000&signature=46a550e6b0c672781b3553fa98cf68b36f408fe32182b61ddd84f137e5c41d89',
            ],
            'https_with_port' => [
                'https://example.com:8080',
                $fixedExpirationDate,
                'https://example.com:8080?url_expires_at=1600000000&signature=6ab9dee3ea9a7dc70308c3f9c83dbea560277be9668dbe38f35264acaebdac02',
            ],
            'https_with_rootpath' => [
                'https://example.com/',
                $fixedExpirationDate,
                'https://example.com/?url_expires_at=1600000000&signature=d6ebe19e590813d94d1b58fe9f9e204a3c5f074ac791dbf0fc2bc3631091f2f1',
            ],
            'https_with_path' => [
                'https://example.com/path',
                $fixedExpirationDate,
                'https://example.com/path?url_expires_at=1600000000&signature=78681bab4602edf8de1972c417a82445a7063291bd83fbe4cdf0aba9e764987c',
            ],
            'https_with_query' => [
                'https://example.com/path?a',
                $fixedExpirationDate,
                'https://example.com/path?a=&url_expires_at=1600000000&signature=09a18f68f0f2db118c99009f99e015e4c2a6fe769414e065506311c5363131d4',
            ],
            'https_with_query2' => [
                'https://example.com/path?a=',
                $fixedExpirationDate,
                'https://example.com/path?a=&url_expires_at=1600000000&signature=09a18f68f0f2db118c99009f99e015e4c2a6fe769414e065506311c5363131d4',
            ],
            'https_with_empty_fragment' => [
                'https://example.com/path#',
                $fixedExpirationDate,
                $onPHP7
                    ? 'https://example.com/path?url_expires_at=1600000000&signature=78681bab4602edf8de1972c417a82445a7063291bd83fbe4cdf0aba9e764987c'
                    : 'https://example.com/path?url_expires_at=1600000000&signature=198ce1619abfc3fa7cbce03508d2a604e4c4ee479cae42b7b8792811d131b06e#',
            ],
            'https_with_fragment' => [
                'https://example.com/path#a',
                $fixedExpirationDate,
                'https://example.com/path?url_expires_at=1600000000&signature=55c26ffa48854b398df58a0f3720b53bbe5a986d0ad3dac11e4245a4cf898802#a',
            ],
            'full' => [
                'https://example.com/path?q=1#a',
                $fixedExpirationDate,
                'https://example.com/path?q=1&url_expires_at=1600000000&signature=a30466c46cc265a4ce9244d2e55a70aa77a955315fe5c9b971705e609553abb0#a',
            ],
        ];
    }

    public function reservedParameterProvider(): array
    {
        $fixedExpirationDate = (new DateTimeImmutable())->setTimestamp(1600000000);

        return [
            [
                'http://example.com?url_expires_at=1',
                $fixedExpirationDate,
            ],
            [
                'http://example.com?signature=1',
                $fixedExpirationDate,
            ],
        ];
    }

    public function missingSignerParameterProvider(): array
    {
        return [
            [
                'http://example.com?signature=46a550e6b0c672781b3553fa98cf68b36f408fe32182b61ddd84f137e5c41d89',
            ],
        ];
    }

    private function createDefaultSigner(): HashHmacUrlSigner
    {
        return new HashHmacUrlSigner('valid', 'signature', 'url_expires_at');
    }
}
