<?php

declare(strict_types=1);

namespace IgFeed\Lib;

use DateTimeImmutable;


/**
 * @property-read string $id
 * @property-read string $mediaType
 * @property-read string $mediaUrl
 * @property-read string $permalink
 * @property-read DateTimeImmutable $publishedAt
 * @property-read string|null $caption
 */
class Media {
    use MagicPropertiesTrait;

    public const IMAGE = 'IMAGE';
    public const VIDEO = 'VIDEO';
    public const CAROUSEL_ALBUM = 'CAROUSEL_ALBUM';

    /** @var string */
    private $id;

    /** @var string */
    private $mediaType;

    /** @var string */
    private $mediaUrl;

    /** @var string */
    private $permalink;

    /** @var DateTimeImmutable */
    private $publishedAt;

    /** @var string|null */
    private $caption;

    public function __construct(
        string $id,
        string $mediaType,
        string $mediaUrl,
        string $permalink,
        DateTimeImmutable $publishedAt,
        ?string $caption
    ) {
        $this->id = $id;
        $this->mediaType = $mediaType;
        $this->mediaUrl = $mediaUrl;
        $this->permalink = $permalink;
        $this->publishedAt = $publishedAt;
        $this->caption = $caption;
    }

    public function getId() : string
    {
        return $this->id;
    }

    public function getMediaType() : string
    {
        return $this->mediaType;
    }

    public function isImage() : bool {
        return $this->mediaType === self::IMAGE;
    }

    public function isVideo() : bool {
        return $this->mediaType === self::VIDEO;
    }

    public function isCarouselAlbum() : bool {
        return $this->mediaType === self::CAROUSEL_ALBUM;
    }

    public function getMediaUrl() : string
    {
        return $this->mediaUrl;
    }

    public function getPermalink() : string
    {
        return $this->permalink;
    }

    public function getPublishedAt() : DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getCaption() : ?string
    {
        return $this->caption;
    }
}
