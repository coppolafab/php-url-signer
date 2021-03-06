<?php

declare(strict_types=1);

namespace coppolafab\UrlSigner;

use coppolafab\UrlSigner\Exception\InvalidSignerKey;
use coppolafab\UrlSigner\Exception\InvalidUrlParameter;
use DateTimeImmutable;
use InvalidArgumentException;

use function array_key_exists;
use function hash_equals;
use function http_build_query;
use function parse_url;
use function parse_str;
use function strval;
use function vsprintf;

abstract class BaseUrlSigner implements UrlSigner
{
    public function __construct(
        string $signerKey,
        string $urlSignatureParam = 'signature',
        string $urlExpireParam = 'url_expires_at',
        Clock $clock = null
    ) {
        if (!$signerKey) {
            throw new InvalidSignerKey();
        }

        if (!$urlSignatureParam) {
            throw new InvalidUrlParameter('invalid url signature parameter');
        }

        if (!$urlExpireParam) {
            throw new InvalidUrlParameter('invalid url expire parameter');
        }

        if ($urlSignatureParam === $urlExpireParam) {
            throw new InvalidUrlParameter('url parameters must differ');
        }

        $this->clock = $clock ?? new SystemClock();
        $this->signerKey = $signerKey;
        $this->urlSignatureParam = $urlSignatureParam;
        $this->urlExpireParam = $urlExpireParam;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function sign(string $url, DateTimeImmutable $expirationDate): string
    {
        $parsedUrl = $this->doParseUrl($url);

        if (array_key_exists('query', $parsedUrl)) {
            /** @var string */
            $query = $parsedUrl['query'];

            $parsedQuery = $this->parsedQueryArray($query);
            $this->ensureUrlSignerParametersDoNotExist($parsedQuery);
        } else {
            $parsedQuery = [];
        }

        $parsedQuery[$this->urlExpireParam] = strval($expirationDate->getTimestamp());

        return $this->parsedToString($parsedUrl, $parsedQuery + [
            $this->urlSignatureParam => $this->computeSignature($this->parsedToString($parsedUrl, $parsedQuery))
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function parsedQueryArray(string $parsedQuery): array
    {
        parse_str($parsedQuery, $parsedQueryArray);
        /** @var array<string, string> */
        return $parsedQueryArray;
    }

    public function verify(string $url): bool
    {
        $parsedUrl = $this->doParseUrl($url);

        if (array_key_exists('query', $parsedUrl)) {
            /** @var string */
            $query = $parsedUrl['query'];
            $parsedQuery = $this->parsedQueryArray($query);
        } else {
            $parsedQuery = [];
        }

        if (
            ! $this->containsSignerParameters($parsedQuery)
            || $this->isExpiredTimestamp((int) $parsedQuery[$this->urlExpireParam])
        ) {
            return false;
        }

        $urlSignature = $parsedQuery[$this->urlSignatureParam];

        unset($parsedQuery[$this->urlSignatureParam]);
        $originalUrl = $this->parsedToString($parsedUrl, $parsedQuery);
        $signature = $this->computeSignature($originalUrl);

        return hash_equals($signature, $urlSignature);
    }

    abstract protected function computeSignature(string $url): string;

    protected function getSignerKey(): string
    {
        return $this->signerKey;
    }

    /**
     * @param array<string, string> $parsedQuery
     */
    private function ensureUrlSignerParametersDoNotExist(array $parsedQuery): void
    {
        if (
            array_key_exists($this->urlSignatureParam, $parsedQuery)
            || array_key_exists($this->urlExpireParam, $parsedQuery)
        ) {
            throw new InvalidArgumentException('url contains signer parameters');
        }
    }

    /**
     * @param array<array-key, string> $parsedQuery
     */
    private function containsSignerParameters(array $parsedQuery): bool
    {
        return array_key_exists($this->urlSignatureParam, $parsedQuery)
            && array_key_exists($this->urlExpireParam, $parsedQuery);
    }

    private function isExpiredTimestamp(int $timestamp): bool
    {
        return $timestamp < $this->clock->currentTime()->getTimestamp();
    }

    /**
     * @param array<array-key, mixed> $parsedUrl
     * @param array<array-key, string> $parsedQuery
     */
    private function parsedToString(array $parsedUrl, array $parsedQuery): string
    {
        /** @var ?int */
        $parsedPort = !isset($parsedUrl['port']) ? null : $parsedUrl['port'];

        /** @var string */
        $parsedFragment = !isset($parsedUrl['fragment']) ? '' : $parsedUrl['fragment'];

        return vsprintf('%s://%s%s%s?%s%s', [
            $parsedUrl['scheme'],
            $parsedUrl['host'],
            isset($parsedUrl['port']) ? ':' . strval($parsedPort) : '',
            $parsedUrl['path'] ?? '',
            http_build_query($parsedQuery),
            isset($parsedUrl['fragment']) ? '#' . $parsedFragment : ''
        ]);
    }

    /**
     * @return array<array-key, mixed>
     */
    private function doParseUrl(string $url): array
    {
        /** @var array<array-key, mixed>|false */
        $parsedUrl = parse_url($url);

        if ($parsedUrl === false) {
            throw new InvalidArgumentException('invalid url ' . $url);
        }

        return $parsedUrl;
    }

    private Clock $clock;
    private string $signerKey;
    private string $urlSignatureParam;
    private string $urlExpireParam;
}
