<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class Mongo
{
    private Client $client;
    private string $dbName;

    public function __construct(
        #[Autowire('%env(MONGODB_URI)%')] string $uri,
        #[Autowire('%env(MONGODB_DB)%')] string $dbName
    ) {
        $this->client = new Client($uri);
        $this->dbName = $dbName;
    }

    public function collection(string $name): Collection
    {
        return $this->client->selectDatabase($this->dbName)->selectCollection($name);
    }
}
