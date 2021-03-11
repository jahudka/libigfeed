<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use IgFeed\Lib\Media;
use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase {

    /** @var Media */
    protected $media;


    protected function setUp() : void {
        $this->media = new Media(
            '0123456789',
            Media::IMAGE,
            'http://dummy.url/0123456789.jpg',
            'http://dummy.url',
            new \DateTimeImmutable('2019-01-08T14:00:00Z'),
            'Test post'
        );
    }


    public function test__get() : void {
        $this->assertEquals('0123456789', $this->media->id);
        $this->expectNotice();
        $this->assertNull($this->media->unknownProperty);
    }

    public function test__isset() : void {
        $this->assertTrue(isset($this->media->id));
        $this->assertTrue(isset($this->media->mediaUrl));
        $this->assertFalse(isset($this->media->unknownProperty));
    }
}
