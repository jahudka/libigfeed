<?php

declare(strict_types=1);

namespace IgFeed\Lib;

use DateTimeImmutable;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Throwable;


class Client
{
    private const API_URL = 'https://api.instagram.com';
    private const GRAPH_URL = 'https://graph.instagram.com';

    private HttpClient $httpClient;
    private string $clientId;
    private string $clientSecret;
    private string $tokenStoragePath;
    private AccessToken | null $token = null;

    public function __construct(
        HttpClient $httpClient,
        string $clientId,
        string $clientSecret,
        string $tokenStoragePath
    ) {
        $this->httpClient = $httpClient;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->tokenStoragePath = $tokenStoragePath;
    }

    public function isConnected() : bool
    {
        return $this->getToken(false) !== null;
    }

    /**
     * @return iterable<Media>
     */
    public function getLatestMedia(
        string | null $type = null,
        string | null $after = null,
        int | null $limit = null,
    ) : iterable {
        $next = null;
        $count = 0;

        do {
            $payload = $this->sendGraphRequest('/me/media', 'GET', [
                'fields' => 'id,caption,media_type,media_url,permalink,timestamp',
                'access_token' => $this->getToken()->getValue(),
                'limit' => 25,
                'after' => $next,
            ]);

            foreach ($payload['data'] as $media) {
                if ($after !== null && $media['id'] === $after) {
                    break 2;
                } else if ($type === null || $media['media_type'] === $type) {
                    yield $this->createMedia($media);

                    if ($limit !== null && ++$count >= $limit) {
                        break 2;
                    }
                }
            }
        } while ($next = $payload['paging']['cursors']['after'] ?? null);
    }

    public function download(Media $media, string $path, bool $overwrite = false) : void
    {
        if (($fp = @fopen($path, $overwrite ? 'wb' : 'xb')) === false) {
            throw new RuntimeException(sprintf('Failed to open %s for writing', $path));
        }

        try {
            $response = $this->httpClient->get($media->getMediaUrl());
            $body = $response->getBody();

            while (!$body->eof()) {
                fwrite($fp, $body->read(2048));
            }
        } finally {
            fclose($fp);
        }
    }

    public function getAuthorizationUrl(string $redirectUri) : string
    {
        return sprintf('%s/oauth/authorize?%s', self::API_URL, http_build_query([
            'client_id' => $this->clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
        ]));
    }

    public function exchangeCodeForAccessToken(string $redirectUri, string $code) : void
    {
        try {
            $shortLivedToken = $this->exchangeCodeForShortLivedToken($redirectUri, $code);
            $this->token = $this->exchangeShortLivedTokenForLongLived($shortLivedToken);
            $this->saveToken($this->token);
        } catch (Throwable $e) {
            $this->clearToken();
            throw $e;
        }
    }

    private function exchangeCodeForShortLivedToken(string $redirectUri, string $code) : string
    {
        $payload = $this->sendApiRequest('/oauth/access_token', 'POST', [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (isset($payload['access_token'])) {
            return $payload['access_token'];
        } else {
            throw new AccessTokenException('Failed exchanging code for short-lived token');
        }
    }

    private function exchangeShortLivedTokenForLongLived(string $token) : AccessToken
    {
        $payload = $this->sendGraphRequest('/access_token', 'GET', [
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->clientSecret,
            'access_token' => $token,
        ]);

        if (isset($payload['access_token']) && isset($payload['expires_in'])) {
            return new AccessToken($payload['access_token'], time() + $payload['expires_in']);
        } else {
            throw new AccessTokenException('Failed exchanging short-lived token for long-lived');
        }
    }

    private function createMedia(array $data) : Media
    {
        return new Media(
            $data['id'],
            $data['media_type'],
            $data['media_url'],
            $data['permalink'],
            new DateTimeImmutable($data['timestamp']),
            $data['caption'] ?? null
        );
    }

    private function sendApiRequest(string $endpoint, string $method = 'GET', array $params = []) : array
    {
        return $this->sendRequest(self::API_URL . $endpoint, $method, $params);
    }

    private function sendGraphRequest(string $endpoint, string $method = 'GET', array $params = []) : array
    {
        return $this->sendRequest(self::GRAPH_URL . $endpoint, $method, $params);
    }

    private function sendRequest(string $url, string $method = 'GET', array $params = []) : array
    {
        try {
            $options = array_filter([
                'http_errors' => true,
                $method === 'GET' ? 'query' : 'form_params' => array_filter($params),
            ]);

            $response = $this->httpClient->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (ClientException $e) {
            if ($e->hasResponse()) {
                $payload = json_decode($e->getResponse()->getBody()->getContents(), true);

                if (isset($payload['error'])) {
                    switch ($payload['error']['code'] ?? -1) {
                        case 190:
                            $this->clearToken();
                            throw new AccessTokenException($payload['error']['message'] ?? 'OAuth Token expired');

                        case 4:
                        case 17:
                        case 32:
                        case 613:
                            throw new RateLimitException($payload['error']['message'] ?? 'Rate limit reached');

                        default:
                            throw new InstagramException($payload['error']['message'] ?? 'Unknown error when communicating with Instagram API');
                    }
                }
            }

            throw $e;
        }
    }

    private function getToken(bool $need = true) : AccessToken | null
    {
        if ($this->token) {
            return $this->token;
        } else if ($rawData = @file_get_contents($this->tokenStoragePath)) {
            $data = json_decode($rawData, true);

            if ($data['expires'] > time() + 86400) {
                return $this->token = new AccessToken($data['value'], $data['expires']);
            }

            if ($data['expires'] > time()) {
                try {
                    return $this->token = $this->renewToken($data['value']);
                } catch (Throwable $e) {
                    $this->clearToken();
                    throw $e;
                }
            }

            $this->clearToken();
        }

        if ($need) {
            throw new AccessTokenException();
        }

        return null;
    }

    private function renewToken(string $token) : AccessToken
    {
        $payload = $this->sendGraphRequest('/refresh_access_token', 'GET', [
            'grant_type' => 'ig_refresh_token',
            'access_token' => $token,
        ]);

        if (isset($payload['access_token']) && isset($payload['expires_in'])) {
            return new AccessToken($payload['access_token'], time() + $payload['expires_in']);
        } else {
            throw new AccessTokenException('Failed refreshing long-lived token');
        }
    }

    private function saveToken(AccessToken $token) : void
    {
        @mkdir(dirname($this->tokenStoragePath), 0750, true);

        touch($this->tokenStoragePath);
        chmod($this->tokenStoragePath, 0600);

        file_put_contents($this->tokenStoragePath, json_encode([
            'value' => $token->value,
            'expires' => $token->expires,
        ]));

        $this->token = $token;
    }

    private function clearToken() : void
    {
        $this->token = null;
        @unlink($this->tokenStoragePath);
    }
}
