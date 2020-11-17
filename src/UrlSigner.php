<?php

declare(strict_types=1);

namespace coppolafab\UrlSigner;

use DateTimeImmutable;

interface UrlSigner
{
    public function sign(string $url, DateTimeImmutable $expirationDate): string;

    public function verify(string $url): bool;
}
