<?php

declare(strict_types=1);

namespace IgFeed\Lib;


/**
 * @property-read string $value
 * @property-read int $expires
 * @property-read bool $expired
 */
class AccessToken
{
    use MagicPropertiesTrait;

    /** @var string */
    private $value;

    /** @var int */
    private $expires;

    public function __construct(string $value, int $expires)
    {
        $this->value = $value;
        $this->expires = $expires;
    }

    public function getValue() : string {
        return $this->value;
    }

    public function getExpires() : int {
        return $this->expires;
    }

    public function isExpired() : bool {
        return $this->expires <= time();
    }
}
