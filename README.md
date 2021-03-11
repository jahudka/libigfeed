# LibIgFeed

*a library which makes it a little easier to embed an Instagram feed in your website*

This library encapsulates a little part of the logic needed to embed an Instagram feed
in a website. Provided with the appropriate credentials it will fetch a list of the latest
posts from your Instagram account which you can then persist locally using any persistence
layer you wish. Additionally, you can download the media represented by a post for
further processing, such as generating responsive thumbnails.

## Dependencies
 - PHP >= 7.1 with `ext-json`
 - `guzzlehttp/guzzle`: `^7.0.1`

## Installation

```bash
composer require jahudka/libigfeed
```

## Usage

 1. Follow [these instructions](https://developers.facebook.com/docs/instagram-basic-display-api/getting-started)
    to create a new Facebook App, configure Instagram Basic Display and add yourself as a test user.
    Use something meaningful for the Valid OAuth Redirect URIs, you'll need it later.
    Stop at step 4 ("Authenticate the Test User").
    
 2. When you're done, you need to find out your Instagram App ID and Secret. From the dashboard
    of your new app select Instagram Basic Display / Basic Display in the Products section of the sidebar.
    Scroll down a bit and you'll find them.
    
 3. Pass these to the constructor of the `IgFeed\Lib\Client` class like so:
    ```php
    // create a HTTP client:
    $httpClient = new GuzzleHttp\Client();

    // create an instance of the client:
    $client = new IgFeed\Lib\Client(
        $httpClient,
        $instagramAppId,
        $instagramAppSecret,
        $cacheDir . '/instagram.token' // the library will store the token
                // obtained during the OAuth authentication flow in this file
    );
    ```

 4. You also need to set up the OAuth authentication endpoint mentioned in step 1.
    All this endpoint needs to do is call `$client->exchangeCodeForAccessToken($redirectUri, $code)`,
    where `$redirectUri` is the URL of the endpoint and `$code` is the authorization code
    returned by Instagram from the OAuth authentication flow in the `code` query string parameter.

 5. To actually trigger the OAuth authentication flow you need to go to a special
    URL. You usually only need to do this once in a blue moon, because the access
    token is valid for 60 days and can be renewed automatically, which the library
    does for you. The way I usually implement this is:
     - In the admin dashboard of the website I call `$client->isConnected()`.
     - If it returns `false`, I display a warning message with a link to
       `$client->getAuthorizationUrl($redirectUri)`.

 6. When you have successfully authenticated your website you can use the `$client->getLatestMedia()`
    method to get the latest content from your Instagram feed. The method returns an array
    of `IgFeed\Lib\Media` instances. This method will also automatically refresh
    the access token in the background if it's less than 24 hours from expiration.
    
    The idea is to set up a cron job to synchronise a local copy of your Instagram feed
    every couple of hours - that way the access token should safely refresh when needed,
    and your website shouldn't be slowed down by loading your Instagram content on-demand.
    You can also use `$client->download($media, $dst)` to download the actual media files
    so that your site doesn't depend on them loading from Instagram.

## Usage within a Nette application
 1. Register and configure the `IgFeed\Bridges\NetteDI\IgFeedExtension` in your `config.neon`:
    ```neon
    extensions:
        igFeed: IgFeed\Bridges\NetteDI\IgFeedExtension
        # ...
    
    igFeed:
        httpClient: @myGuzzleClientService  # optional
        clientId: '...'
        clientSecret: '...'
        tokenStoragePath: %tempDir%/instagram.token
    ```

 2. Let Nette DI do its magic and inject the `IgFeed\Lib\Client` instance
    in your presenters and services as needed.
 
 3. Prosper :-)
