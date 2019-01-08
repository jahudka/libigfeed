<?php

declare(strict_types=1);

namespace IgFeed\Lib;


/**
 * @property-read string $id
 * @property-read string $type
 * @property-read string $url
 * @property-read \DateTimeImmutable $postedAt
 * @property-read int $width
 * @property-read int $height
 * @property-read int $likes
 * @property-read int $comments
 * @property-read string|null $caption
 * @property-read array $images
 * @property-read array|null $videos
 */
class Post {
    use MagicPropertiesTrait;

    public const IMAGE = 'image',
        VIDEO = 'video';

    /** @var string */
    private $id;

    /** @var string */
    private $type;

    /** @var string */
    private $url;

    /** @var \DateTimeImmutable */
    private $postedAt;

    /** @var int */
    private $width;

    /** @var int */
    private $height;

    /** @var int */
    private $likes;

    /** @var int */
    private $comments;

    /** @var string|null */
    private $caption;

    /** @var Media[] */
    private $images;

    /** @var Media[]|null */
    private $videos;


    public function __construct(
        string $id,
        string $type,
        string $url,
        \DateTimeImmutable $postedAt,
        int $width,
        int $height,
        int $likes,
        int $comments,
        ?string $caption,
        array $images,
        ?array $videos = null
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->url = $url;
        $this->postedAt = $postedAt;
        $this->width = $width;
        $this->height = $height;
        $this->likes = $likes;
        $this->comments = $comments;
        $this->caption = $caption;
        $this->images = $images;
        $this->videos = $type === self::VIDEO ? $videos : null;
    }

    public function getId() : string {
        return $this->id;
    }

    public function getType() : string {
        return $this->type;
    }

    public function isVideo() : bool {
        return $this->type === self::VIDEO;
    }

    public function getUrl() : string {
        return $this->url;
    }

    public function getPostedAt() : \DateTimeImmutable {
        return $this->postedAt;
    }

    public function getWidth() : int {
        return $this->width;
    }

    public function getHeight() : int {
        return $this->height;
    }

    public function getLikes() : int {
        return $this->likes;
    }

    public function getComments() : int {
        return $this->comments;
    }

    public function getCaption() : ?string {
        return $this->caption;
    }

    /** @return Media[] */
    public function getImages() : array {
        return $this->images;
    }

    public function getImage(string $resolution) : Media {
        $this->assertResolutionAvailable($this->images, $resolution);
        return $this->images[$resolution];
    }

    /** @return Media[] */
    public function getVideos() : array {
        $this->assertPostIsVideo();
        return $this->videos;
    }

    public function getVideo(string $resolution) : Media {
        $this->assertPostIsVideo();
        $this->assertResolutionAvailable($this->videos, $resolution);
        return $this->videos[$resolution];
    }


    protected function assertPostIsVideo() : void {
        if ($this->type !== self::VIDEO) {
            throw new \RuntimeException('Cannot get video media from an image post');
        }
    }

    protected function assertResolutionAvailable(array $media, string $resolution) : void {
        if (!isset($media[$resolution])) {
            throw new \InvalidArgumentException('Invalid or unavailable resolution: ' . $resolution);
        }
    }

}
