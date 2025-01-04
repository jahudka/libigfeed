<?php

declare(strict_types=1);

namespace IgFeed\Lib;


final readonly class AccessToken
{
    public function __construct(
        public string $value,
        public int $expires,
    ) {}

    public function isExpired() : bool
    {
        return $this->expires <= time();
    }
}
