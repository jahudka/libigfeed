<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use IgFeed\Lib\Client;
use IgFeed\Lib\Post;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;


class ClientFetchDataTest extends TestCase {

    /** @var Client */
    protected static $client;

    /** @var vfsStreamDirectory */
    protected static $vfs;


    public static function setUpBeforeClass() : void {
        self::$vfs = vfsStream::setup();
        self::$vfs->addChild(vfsStream::newFile('instagram.token')->setContent('foo.barbar.bazbazbaz'));
        self::$vfs->addChild(vfsStream::newFile('dummy.jpg')->setContent('not-really-jpg-binary-data'));

        $handler = new MockHandler([
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/posts-p1.json', 'rb'), '2.0'),
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/posts-p2.json', 'rb'), '2.0'),
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/posts-empty.json', 'rb'), '2.0'),
            new Response(200, [], fopen(self::$vfs->url() . '/dummy.jpg', 'rb'), '2.0'),
        ]);

        $stack = HandlerStack::create($handler);
        $httpClient = new HttpClient(['handler' => $stack]);

        self::$client = new Client(
            $httpClient,
            'clientId123456',
            'clientSecret123456',
            'self',
            self::$vfs->url() . '/instagram.token'
        );
    }


    public function testGetLatestPosts() : Post {
        $posts = self::$client->getLatestPosts();
        $this->assertIsArray($posts);
        $this->assertEquals(8, count($posts));
        array_map(function($post) { $this->assertInstanceOf(Post::class, $post); }, $posts);

        $last = end($posts);

        $more = self::$client->getLatestPosts(null, $last->getId());
        $this->assertIsArray($more);
        $this->assertEquals(0, count($more));

        return $last;
    }


    /**
     * @depends testGetLatestPosts
     * @param Post $post
     */
    public function testDownload(Post $post) : void {
        $path = self::$vfs->url() . '/downloaded.jpg';
        self::$client->download($post->getImage('standard_resolution'), $path);
        $this->assertTrue(self::$vfs->hasChild('downloaded.jpg'));
        $this->assertEquals('not-really-jpg-binary-data', self::$vfs->getChild('downloaded.jpg')->getContent());
    }

}
