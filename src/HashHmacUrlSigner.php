<?php

declare(strict_types=1);

namespace coppolafab\UrlSigner;

use function hash_hmac;

final class HashHmacUrlSigner extends BaseUrlSigner
{
    protected function computeSignature(string $url): string
    {
        return hash_hmac('sha256', $url, $this->getSignerKey());
    }
}
