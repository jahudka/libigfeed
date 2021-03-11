<?php

declare(strict_types=1);

namespace IgFeed\Bridges\NetteDI;

use GuzzleHttp\Client as HttpClient;
use IgFeed\Lib\Client;
use Nette\DI\CompilerExtension;
use Nette\DI\Statement;


class IgFeedExtension extends CompilerExtension {

    public $defaults = [
        'httpClient' => null,
        'clientId' => null,
        'clientSecret' => null,
        'tokenStoragePath' => null,
    ];


    public function loadConfiguration() {
        $config = $this->validateConfig($this->defaults);
        $builder = $this->getContainerBuilder();

        if (empty($config['httpClient'])) {
            $config['httpClient'] = new Statement(HttpClient::class);
        }

        $builder->addDefinition($this->prefix('client'))
            ->setType(Client::class)
            ->setArguments($config);
    }

}
