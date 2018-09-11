<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Esi;

use Http\Client\HttpAsyncClient;
use Http\Client\HttpClient;
use Http\Message\RequestFactory;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\RequestInterface;

class ApiClient
{
    private $factory;
    private $httpClient;
    private $httpAsyncClient;

    public function __construct(RequestFactory $factory, HttpClient $httpClient, HttpAsyncClient $httpAsyncClient)
    {
        $this->factory = $factory;
        $this->httpClient = $httpClient;
        $this->httpAsyncClient = $httpAsyncClient;
    }

    public function getBloodlines(): array
    {
        $endpoint = new Endpoint\Universe\Bloodlines();
        $request = $this->createRequest($endpoint);
        $response = $this->httpClient->sendRequest($request);
        $data = array_column(json_decode($response->getBody()->getContents(), true), null, 'bloodline_id');

        return $data;
    }

    public function getRaces(): array
    {
        $endpoint = new Endpoint\Universe\Races();
        $request = $this->createRequest($endpoint);
        $response = $this->httpClient->sendRequest($request);
        $data = array_column(json_decode($response->getBody()->getContents(), true), null, 'race_id');

        return $data;
    }

    public function getNames(array $identifiers): array
    {
        $endpoint = new Endpoint\Universe\Names();
        $options = ['body' => \GuzzleHttp\Psr7\stream_for(json_encode(array_values($identifiers)))];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = array_column(json_decode($response->getBody()->getContents(), true), null, 'id');

        return $data;
    }

    public function getCharacter(AccessToken $token, int $characterId): array
    {
        $endpoint = new Endpoint\Characters\Character(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    public function getWallet(AccessToken $token, int $characterId): float
    {
        $endpoint = new Endpoint\Wallet\Wallet(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    public function getPortrait(AccessToken $token, int $characterId): array
    {
        $endpoint = new Endpoint\Characters\Portrait(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    public function getSkills(AccessToken $token, int $characterId): array
    {
        $endpoint = new Endpoint\Skills\Skills(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    public function getSkillQueue(AccessToken $token, int $characterId): array
    {
        $endpoint = new Endpoint\Skills\SkillQueue(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    public function createRequest(EndpointInterface $endpoint, array $options = []): RequestInterface
    {
        $options = $this->parseOptions($endpoint, $options);

        return $this->factory->createRequest(
            $endpoint->method(),
            $endpoint->path(),
            $options['headers'],
            $options['body'],
            $options['version']
        );
    }

    protected function parseOptions(EndpointInterface $endpoint, array $options): array
    {
        $defaults = [
            'headers' => $endpoint->headers(),
            'body' => null,
            'version' => '1.1',
        ];

        return \App\array_merge_recursive_overwrite($defaults, $options);
    }
}
