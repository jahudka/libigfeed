<?php

declare(strict_types=1);

namespace IgFeed\Tests\Lib;

use IgFeed\Lib\AccessTokenException;
use IgFeed\Lib\Client;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;


class ClientOAuthTest extends TestCase
{
    private static Client $client;
    private static vfsStreamDirectory $vfs;


    public static function setUpBeforeClass() : void
    {
        self::$vfs = vfsStream::setup();

        $httpClient = new MockHttpClient([
            self::createMockResponse('oauth-token-short'),
            self::createMockResponse('oauth-token-long'),
            self::createMockResponse('oauth-err', 400),
        ]);

        self::$client = new Client(
            $httpClient,
            'clientId123456',
            'clientSecret123456',
            self::$vfs->url() . '/instagram.token',
        );
    }

    private static function createMockResponse(string $file, int $status = 200) : MockResponse
    {
        return MockResponse::fromFile(sprintf(__DIR__ . '/../fixtures/responses/%s.json', $file), [
            'http_code' => $status,
            'response_headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }


    public function testIsNotConnected() : void
    {
        $this->assertFalse(self::$client->isConnected());
    }

    /**
     * @depends testIsNotConnected
     */
    public function testRequestsThrow() : void
    {
        $this->expectException(AccessTokenException::class);
        iterator_to_array(self::$client->getLatestMedia());
    }

    /**
     * @depends testRequestsThrow
     */
    public function testGetAuthorizationUrl() : void
    {
        $url = self::$client->getAuthorizationUrl('http://dummy.url');
        $this->assertTrue((bool) filter_var($url, FILTER_VALIDATE_URL));
    }

    /**
     * @depends testGetAuthorizationUrl
     */
    public function testExchangeCodeForAccessToken() : void
    {
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
    public function testInvalidToken() : void
    {
        $this->expectException(AccessTokenException::class);
        iterator_to_array(self::$client->getLatestMedia());
    }
}
