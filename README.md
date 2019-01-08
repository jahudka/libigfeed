# LibIgFeed

*a library which makes it a little easier to embed an Instagram feed in your website*

This library encapsulates a little part of the logic needed to embed an Instagram feed
in a website. Provided with the appropriate credentials it will fetch a list of the latest
posts from a given Instagram account which you can then persist locally using any persistence
layer you wish. Additionally you can download the media represented by a post for
further processing, such as generating responsive thumbnails.

## Dependencies
 - PHP >= 7.1 with the `ext-json` available
 - `guzzlehttp/guzzle`: `^6.3.3`

## Installation

```bash
composer require jahudka/libigfeed
```

## Usage

 1. Go to the [Instagram developer portal](https://www.instagram.com/developer/clients/manage/)
    and register a new client if you haven't done so already. Of special note is the *Valid
    redirect URIs* field: later you'll need to authenticate your app to get access to the Instagram
    API and for that you'll need to set up an OAuth authentication page. This will be a simple
    PHP script we'll get to later; for now think up the URL it will be available at and put that
    in the *Valid redirect URIs* field.
    
 2. Next, go to the Management page of your new client; at the top of the page there will be
    two important pieces of information: the Client ID and the Client Secret.
    
 3. Initialise the client using the Client ID and Client Secret obtained in the previous step:
    ```php
    // create a HTTP client:
    $httpClient = new GuzzleHttp\Client();

    // create an instance of the client:
    $client = new IgFeed\Lib\Client(
        $httpClient,
        $clientId,
        $clientSecret,
        'self', // or another Instagram account ID, see below
        $cacheDir . '/instagram.token' // the library will store the token
                // obtained during the OAuth authentication flow in this file
    );
    ```

    N.b. Unless you submit your app for review using the Developer portal it will run in
    Sandbox mode. This means that it'll only be able to gain access to your own account
    plus other developer accounts which you manually invite using the Developer portal
    and who then accept your invitation; furthermore the app will only gain access to
    the 20 last posts from the feed. For most "promotional" embedded feeds this should
    be okay.
    
 4. Remember the OAuth authentication page we talked about in Step 1? The page will need
    to do something like this:
    ```php
    $redirectUri = 'http://absolute.url/to/this/page';

    if ($client->isConnected()) {
        echo "Instagram client is connected.";
    } else if (!empty($_GET['error'])) {
        echo "Error: " . $_GET['error_description'];
    } else if (!empty($_GET['code'])) {
        $client->exchangeCodeForAccessToken($redirectUri, $_GET['code']);
    
        // The client is now ready to issue requests against the Instagram API.
        // Reload the page without the $code query parameter and display
        // a nice message to the user, i.e. yourself.. or do whatever.
        header('Location: ' . $redirectUri);
    } else {
        header('Location: ' . $client->getAuthorizationUrl($redirectUri));
    }

    exit;
    ```
    
 5. Now you can set up a script to be called using a cron job or a similar mechanism.
    This script should call the `$client->getLatestPosts()` method and persist
    the returned array of `IgFeed\Lib\Post` instances e.g. to a database.
    To download the actual images and / or videos you can use the `$client->download()`
    method.

## Usage within a Nette application
 1. Register and configure the `IgFeed\Bridges\NetteDI\IgFeedExtension` in your `config.neon`:
    ```neon
    extensions:
        igFeed: IgFeed\Bridges\NetteDI\IgFeedExtension
        # ...
    
    igFeed:
        httpClient: @myGuzzleClientService
        clientId: '...'
        clientSecret: '...'
        accountId: self
        tokenStoragePath: %tempDir%/instagram.token
    ```

 2. Let Nette DI do its magic and inject the `IgFeed\Lib\Client` instance
    in your presenters and services as needed.
 
 3. Prosper :-)
