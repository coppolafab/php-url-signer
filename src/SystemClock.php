<?php

declare(strict_types=1);

namespace coppolafab\UrlSigner;

use DateTimeImmutable;

final class SystemClock implements Clock
{
    public function currentTime(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
