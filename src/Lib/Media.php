<?php

declare(strict_types=1);

namespace IgFeed\Lib;


/**
 * @property-read string $url
 * @property-read int $width
 * @property-read int $height
 */
class Media {
    use MagicPropertiesTrait;

    private $url;

    private $width;

    private $height;


    public function __construct(string $url, int $width, int $height) {
        $this->url = $url;
        $this->width = $width;
        $this->height = $height;
    }

    public function getUrl() : string {
        return $this->url;
    }

    public function getWidth() : int {
        return $this->width;
    }

    public function getHeight() : int {
        return $this->height;
    }



}
