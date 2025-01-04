# LibIgFeed

*a library which makes it a little easier to embed an Instagram feed in your website*

This library encapsulates a little part of the logic needed to embed an Instagram feed
in a website. Provided with the appropriate credentials it will fetch a list of the latest
posts from your Instagram account which you can then persist locally using any persistence
layer you wish. Additionally, you can download the media represented by a post for
further processing, such as generating responsive thumbnails.

## Dependencies
 - PHP >= 8.4 with `ext-json`
 - `symfony/http-client`: `^7.2`

## Installation

```bash
composer require jahudka/libigfeed
```

## Usage

 1. Follow [these instructions](https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login/create-a-meta-app-with-instagram)
    to create a new Meta App. Don't connect a Business - you'll be using the 
    app in development mode, so it's not needed.

 2. When you get add Instagram in Step 6, you'll be asked to choose which 
    authentication path you want to set up: either via Instagram, or via 
    Facebook. Select Instagram.

 3. At the top of the following screen, you can find your App ID and secret.
    Copy those down, you'll need them in a bit.

 4. On the same screen, scroll down all the way to Step 3 - Set up Instagram 
    business login. Input your OAuth authentication endpoint URL. You can 
    ignore the generated Instagram authentication URL.

 5. Paste the App ID and secret to the constructor of the `IgFeed\Lib\Client` 
    class like so:
    ```php
    // create an instance of the client:
    $client = new IgFeed\Lib\Client(
        HttpClient::create(),
        $appId,
        $appSecret,
        $cacheDir . '/instagram.token' // the library will store the token
                // obtained during the OAuth authentication flow in this file
    );
    ```

 6. You also need to set up the OAuth authentication endpoint mentioned in 
    step 4. All this endpoint needs to do is call 
    `$client->exchangeCodeForAccessToken($redirectUri, $code)`, where
   `$redirectUri` is the URL of the endpoint and `$code` is the authorization 
    code returned by Instagram from the OAuth authentication flow in the 
    `code` query string parameter.

 7. To actually trigger the OAuth authentication flow you need to go to a 
    special URL. You usually only need to do this once in a blue moon, because
    the access token is valid for 60 days and can be renewed automatically, 
    which the library does for you. The way I usually implement this is:
     - In the admin dashboard of the website I call `$client->isConnected()`.
     - If it returns `false`, I display a warning message with a link to
       `$client->getAuthorizationUrl($redirectUri)`.

 8. When you have successfully authenticated your website you can use the 
    `$client->getLatestMedia()` method to get the latest content from your 
    Instagram feed. The method returns a `Generator` of `IgFeed\Lib\Media` 
    instances. This method will also automatically refresh the access token 
    in the background if it's less than 24 hours from expiration.

    The idea is to set up a cron job to synchronise a local copy of your
    Instagram feed every couple of hours - that way the access token should 
    safely refresh when needed, and your website shouldn't be slowed down by 
    loading your Instagram content on-demand. You should also use 
    `$client->download($media, $dst)` to download the actual media files so 
    that your site doesn't depend on them loading from Instagram.
