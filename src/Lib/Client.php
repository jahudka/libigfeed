<?php

declare(strict_types=1);

namespace IgFeed\Lib;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;


class Client {

    private const API_URL = 'https://api.instagram.com';


    /** @var HttpClient */
    private $httpClient;

    /** @var string */
    private $clientId;

    /** @var string */
    private $clientSecret;

    /** @var string */
    private $accountId;

    /** @var string */
    private $tokenStoragePath;


    /** @var string */
    private $token = null;


    public function __construct(
        HttpClient $httpClient,
        string $clientId,
        string $clientSecret,
        string $accountId,
        string $tokenStoragePath
    ) {
        $this->httpClient = $httpClient;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->accountId = $accountId;
        $this->tokenStoragePath = $tokenStoragePath;
    }


    public function isConnected() : bool {
        return $this->getToken(false) !== null;
    }


    /**
     * @param string $type
     * @param string $after
     * @param int $limit
     * @return Post[]
     * @throws AccessTokenException
     */
    public function getLatestPosts(?string $type = null, ?string $after = null, ?int $limit = null) : array {
        $next = null;
        $posts = [];
        $count = 0;

        do {
            $payload = $this->sendApiRequest(sprintf('/v1/users/%s/media/recent', $this->accountId), 'GET', [
                'access_token' => $this->getToken(),
                'min_id' => $after,
                'max_id' => $next,
            ]);

            foreach ($payload['data'] as $post) {
                if ($type === null || $post['type'] === $type) {
                    $posts[] = $this->createPost($post);

                    if ($limit !== null && ++$count >= $limit) {
                        break 2;
                    }
                }
            }
        } while ($next = $payload['pagination']['next_max_id'] ?? null);

        return $posts;
    }

    public function download(Media $media, string $path) : void {
        if (($fp = @fopen($path, 'wb')) === false) {
            throw new \RuntimeException(sprintf('Failed to open %s for writing', $path));
        }

        try {
            $response = $this->httpClient->get($media->getUrl());
            $body = $response->getBody();

            while (!$body->eof()) {
                fwrite($fp, $body->read(2048));
            }
        } finally {
            fclose($fp);
        }
    }

    public function getAuthorizationUrl(string $redirectUri) : string {
        return sprintf('%s/oauth/authorize?%s', self::API_URL, http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scopes' => 'basic public_content',
        ]));
    }

    public function exchangeCodeForAccessToken(string $redirectUri, string $code) : void {
        $payload = $this->sendApiRequest('/oauth/access_token', 'POST', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (isset($payload['access_token'])) {
            $this->saveToken($payload['access_token']);
        } else {
            $this->cleanToken();
            throw new AccessTokenException();
        }
    }


    private function createPost(array $data) : Post {
        return new Post(
            $data['id'],
            $data['type'],
            $data['link'],
            new \DateTimeImmutable('@' . $data['created_time']),
            $data['images']['standard_resolution']['width'],
            $data['images']['standard_resolution']['height'],
            $data['likes']['count'] ?? 0,
            $data['comments']['count'] ?? 0,
            $data['caption']['text'] ?? null,
            $this->createMediaCollection($data['images']),
            $data['type'] === Post::VIDEO ? $this->createMediaCollection($data['videos']) : null
        );
    }

    private function createMediaCollection(array $media) : array {
        return array_map(function(array $info) : Media {
            return new Media($info['url'], $info['width'], $info['height']);
        }, $media);
    }


    private function sendApiRequest(string $endpoint, string $method = 'GET', array $params = []) : array {
        try {
            $options = array_filter([
                'http_errors' => true,
                $method === 'GET' ? 'query' : 'form_params' => array_filter($params),
            ]);

            $response = $this->httpClient->request(
                $method,
                self::API_URL . $endpoint,
                $options
            );

            return json_decode($response->getBody()->getContents(), true);

        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                $payload = json_decode($e->getResponse()->getBody()->getContents(), true);

                if (isset($payload['meta']['error_type'])) {
                    switch ($payload['meta']['error_type']) {
                        case 'OAuthAccessTokenException':
                            $this->cleanToken();
                            throw new AccessTokenException($payload['meta']['error_message']);

                        case 'OAuthRateLimitException':
                            throw new RateLimitException();
                    }
                }
            }

            throw $e;
        }
    }

    private function getToken(bool $need = true) : ?string {
        if ($this->token) {
            return $this->token;
        } else if ($token = @file_get_contents($this->tokenStoragePath)) {
            return $this->token = $token;
        } else if ($need) {
            throw new AccessTokenException();
        } else {
            return null;
        }
    }

    private function saveToken(string $token) : void {
        @mkdir(dirname($this->tokenStoragePath), 0755, true);
        file_put_contents($this->tokenStoragePath, $token);
        $this->token = $token;
    }

    private function cleanToken() : void {
        $this->token = null;
        @unlink($this->tokenStoragePath);
    }

}
