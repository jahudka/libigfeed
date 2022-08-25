<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use IgFeed\Lib\Client;
use IgFeed\Lib\Media;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;


class ClientFetchDataTest extends TestCase
{
    private static Client $client;
    private static vfsStreamDirectory $vfs;

    public static function setUpBeforeClass() : void
    {
        self::$vfs = vfsStream::setup();
        $token = json_encode(['value' => 'foo.barbar.bazbazbaz', 'expires' => time() + 604800]);
        self::$vfs->addChild(vfsStream::newFile('instagram.token')->setContent($token));
        self::$vfs->addChild(vfsStream::newFile('dummy.jpg')->setContent('not-really-jpg-binary-data'));

        $handler = new MockHandler([
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/posts-p1.json', 'rb'), '2.0'),
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/posts-p2.json', 'rb'), '2.0'),
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/posts-p1.json', 'rb'), '2.0'),
            new Response(200, [], fopen(self::$vfs->url() . '/dummy.jpg', 'rb'), '2.0'),
        ]);

        $stack = HandlerStack::create($handler);
        $httpClient = new HttpClient(['handler' => $stack]);

        self::$client = new Client(
            $httpClient,
            'clientId123456',
            'clientSecret123456',
            self::$vfs->url() . '/instagram.token'
        );
    }


    public function testGetLatestPosts() : Media
    {
        $result = self::$client->getLatestMedia();
        $this->assertIsIterable($result);
        $items = [...$result];
        $this->assertCount(8, $items);
        array_map(function($media) { $this->assertInstanceOf(Media::class, $media); }, $items);

        $latest = reset($items);

        $next = self::$client->getLatestMedia(null, $latest->getId());
        $this->assertIsIterable($next);
        $more = [...$next];
        $this->assertCount(0, $more);

        return $latest;
    }


    /**
     * @depends testGetLatestPosts
     */
    public function testDownload(Media $media) : void
    {
        $path = self::$vfs->url() . '/downloaded.jpg';
        self::$client->download($media, $path);
        $this->assertTrue(self::$vfs->hasChild('downloaded.jpg'));
        $this->assertEquals('not-really-jpg-binary-data', self::$vfs->getChild('downloaded.jpg')->getContent());
    }
}
