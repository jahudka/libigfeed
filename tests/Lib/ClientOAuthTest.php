<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use IgFeed\Lib\AccessTokenException;
use IgFeed\Lib\Client;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;


class ClientOAuthTest extends TestCase {

    /** @var Client */
    protected static $client;

    /** @var vfsStreamDirectory */
    protected static $vfs;


    public static function setUpBeforeClass() : void {
        self::$vfs = vfsStream::setup();

        $handler = new MockHandler([
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/oauth-token-short.json', 'rb'), '1.1'),
            new Response(200, [], fopen(__DIR__ . '/../fixtures/responses/oauth-token-long.json', 'rb'), '1.1'),
            new Response(400, [], fopen(__DIR__ . '/../fixtures/responses/oauth-err.json', 'rb'), '1.1'),
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


    public function testIsNotConnected() : void {
        $this->assertFalse(self::$client->isConnected());
    }

    /**
     * @depends testIsNotConnected
     */
    public function testRequestsThrow() : void {
        $this->expectException(AccessTokenException::class);
        self::$client->getLatestMedia();
    }

    /**
     * @depends testRequestsThrow
     */
    public function testGetAuthorizationUrl() : void {
        $url = self::$client->getAuthorizationUrl('http://dummy.url');
        $this->assertTrue((bool) filter_var($url, FILTER_VALIDATE_URL));
    }

    /**
     * @depends testGetAuthorizationUrl
     */
    public function testExchangeCodeForAccessToken() : void {
        self::$client->exchangeCodeForAccessToken('http://dummy.url', 'dummy.code');
        $this->assertTrue(self::$vfs->hasChild('instagram.token'));
        $this->assertEquals(vfsStreamContent::TYPE_FILE, self::$vfs->getChild('instagram.token')->getType());

        $data = self::$vfs->getChild('instagram.token')->getContent();
        $token = json_decode($data);
        $this->assertEquals('foo.barbar.bazbazbaz', $token->value);
        $this->assertIsInt($token->expires);
    }

    /**
     * @depends testExchangeCodeForAccessToken
     */
    public function testInvalidToken() : void {
        $this->expectException(AccessTokenException::class);
        self::$client->getLatestMedia();
    }

}
