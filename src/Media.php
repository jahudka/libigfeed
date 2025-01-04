<?php

declare(strict_types=1);

namespace IgFeed\Lib;

use DateTimeImmutable;


final readonly class Media
{
    public function __construct(
        public string $id,
        public MediaType $mediaType,
        public string $mediaUrl,
        public string $permalink,
        public DateTimeImmutable $publishedAt,
        public string|null $caption = null,
    ) {}

    public function isImage() : bool
    {
        return $this->mediaType === MediaType::Image;
    }

    public function isVideo() : bool
    {
        return $this->mediaType === MediaType::Video;
    }

    public function isCarouselAlbum() : bool
    {
        return $this->mediaType === MediaType::CarouselAlbum;
    }
}
