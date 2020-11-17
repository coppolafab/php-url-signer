<?php

declare(strict_types=1);

namespace coppolafab\UrlSigner;

use DateTimeImmutable;

interface Clock
{
    public function currentTime(): DateTimeImmutable;
}
