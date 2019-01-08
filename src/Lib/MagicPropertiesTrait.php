<?php

declare(strict_types=1);

namespace IgFeed\Lib;


trait MagicPropertiesTrait {

    public function __get(string $name) {
        if (property_exists(self::class, $name)) {
            $method = 'get' . ucfirst($name);

            if (method_exists($this, $method)) {
                return $this->{$method}();
            }
        }

        trigger_error(sprintf('Undefined property: %s::$%s', static::class, $name));
        return null;
    }

    public function __isset($name) {
        if (property_exists(self::class, $name)) {
            return isset($this->$name);
        }

        return false;
    }

}
