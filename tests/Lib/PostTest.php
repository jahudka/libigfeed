<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use IgFeed\Lib\Media;
use IgFeed\Lib\Post;
use PHPUnit\Framework\TestCase;

class PostTest extends TestCase {

    /** @var Post */
    protected $post;


    protected function setUp() {
        $this->post = new Post(
            '0123456789',
            Post::IMAGE,
            'http://dummy.url',
            new \DateTimeImmutable('2019-01-08T14:00:00Z'),
            800,
            600,
            32,
            5,
            'Test post',
            [
                'standard_resolution' => new Media('http://dummy.url/0123456789.jpg', 800, 600),
            ]
        );
    }


    /**
     * @expectedException \PHPUnit\Framework\Error\Notice
     */
    public function test__get() : void {
        $this->assertEquals('0123456789', $this->post->id);
        $this->assertNull($this->post->unknownProperty);
    }

    public function test__isset() : void {
        $this->assertTrue(isset($this->post->id));
        $this->assertFalse(isset($this->post->videos));
        $this->assertFalse(isset($this->post->unknownProperty));
    }
}
