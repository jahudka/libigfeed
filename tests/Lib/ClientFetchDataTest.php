<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use IgFeed\Lib\Client;
use IgFeed\Lib\Media;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;


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

        $httpClient = new MockHttpClient([
            self::createMockResponse('posts-p1'),
            self::createMockResponse('posts-p2'),
            self::createMockResponse('posts-p1'),
            self::createMockResponse(self::$vfs->url() . '/dummy.jpg'),
        ]);

        self::$client = new Client(
            $httpClient,
            'clientId123456',
            'clientSecret123456',
            self::$vfs->url() . '/instagram.token'
        );
    }

    private static function createMockResponse(string $file) : MockResponse
    {
        if (!str_contains($file, '/')) {
            $file = sprintf(__DIR__ . '/../fixtures/responses/%s.json', $file);
        }

        return MockResponse::fromFile($file, [
            'response_headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    public function testGetLatestPosts() : Media
    {
        $result = self::$client->getLatestMedia();
        $this->assertIsIterable($result);
        $items = [...$result];
        $this->assertCount(8, $items);
        array_map(function($media) { $this->assertInstanceOf(Media::class, $media); }, $items);

        $latest = reset($items);

        $next = self::$client->getLatestMedia(null, $latest->id);
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
