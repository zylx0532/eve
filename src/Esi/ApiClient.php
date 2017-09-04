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
    /**
     * @var \Http\Message\RequestFactory
     */
    private $factory;
    /**
     * @var \Http\Client\HttpClient
     */
    private $httpClient;
    /**
     * @var \Http\Client\HttpAsyncClient
     */
    private $httpAsyncClient;

    /**
     * @param \Http\Message\RequestFactory $factory
     * @param \Http\Client\HttpClient $httpClient
     * @param \Http\Client\HttpAsyncClient $httpAsyncClient
     */
    public function __construct(RequestFactory $factory, HttpClient $httpClient, HttpAsyncClient $httpAsyncClient)
    {
        $this->factory = $factory;
        $this->httpClient = $httpClient;
        $this->httpAsyncClient = $httpAsyncClient;
    }

    /**
     * @return array
     */
    public function getBloodlines()
    {
        $endpoint = new Endpoint\Universe\Bloodlines();
        $request = $this->createRequest($endpoint);
        $response = $this->httpClient->sendRequest($request);
        $data = array_column(json_decode($response->getBody()->getContents(), true), null, 'bloodline_id');

        return $data;
    }

    /**
     * @return array
     */
    public function getRaces()
    {
        $endpoint = new Endpoint\Universe\Races();
        $request = $this->createRequest($endpoint);
        $response = $this->httpClient->sendRequest($request);
        $data = array_column(json_decode($response->getBody()->getContents(), true), null, 'race_id');

        return $data;
    }

    /**
     * @param array $identifiers
     *
     * @return array
     */
    public function getNames(array $identifiers)
    {
        $endpoint = new Endpoint\Universe\Names();
        $options = ['body' => \GuzzleHttp\Psr7\stream_for(json_encode(array_values($identifiers)))];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = array_column(json_decode($response->getBody()->getContents(), true), null, 'id');

        return $data;
    }

    /**
     * @param \League\OAuth2\Client\Token\AccessToken $token
     * @param int $characterId
     *
     * @return array
     */
    public function getCharacter(AccessToken $token, int $characterId)
    {
        $endpoint = new Endpoint\Characters\Character(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    /**
     * @param \League\OAuth2\Client\Token\AccessToken $token
     * @param int $characterId
     *
     * @return float
     */
    public function getWallet(AccessToken $token, int $characterId)
    {
        $endpoint = new Endpoint\Characters\Wallet(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    /**
     * @param \League\OAuth2\Client\Token\AccessToken $token
     * @param int $characterId
     *
     * @return array
     */
    public function getPortrait(AccessToken $token, int $characterId)
    {
        $endpoint = new Endpoint\Characters\Portrait(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    /**
     * @param \League\OAuth2\Client\Token\AccessToken $token
     * @param int $characterId
     *
     * @return array
     */
    public function getSkills(AccessToken $token, int $characterId)
    {
        $endpoint = new Endpoint\Characters\Skills(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    /**
     * @param \League\OAuth2\Client\Token\AccessToken $token
     * @param int $characterId
     *
     * @return mixed
     */
    public function getSkillQueue(AccessToken $token, int $characterId)
    {
        $endpoint = new Endpoint\Characters\SkillQueue(['character_id' => $characterId]);
        $options = ['headers' => ['Authorization' => 'Bearer ' . $token]];
        $request = $this->createRequest($endpoint, $options);
        $response = $this->httpClient->sendRequest($request);
        $data = json_decode($response->getBody()->getContents(), true);

        return $data;
    }

    /**
     * @param \App\Esi\EndpointInterface $endpoint
     * @param array $options
     *
     * @return \Psr\Http\Message\RequestInterface
     */
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

    /**
     * Parses simplified options.
     *
     * @param \App\Esi\EndpointInterface $endpoint
     * @param array $options simplified options
     *
     * @return array extended options for use with getRequest
     */
    protected function parseOptions(EndpointInterface $endpoint, array $options)
    {
        $defaults = [
            'headers' => $endpoint->headers(),
            'body' => null,
            'version' => '1.1',
        ];

        return \App\Fn\array_merge_recursive_overwrite($defaults, $options);
    }
}
