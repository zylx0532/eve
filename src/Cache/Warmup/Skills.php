<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Cache\Warmup;

use App\Esi\ApiClient;
use App\Esi\Endpoint\Universe\Category;
use App\Esi\Endpoint\Universe\Group;
use App\Esi\Endpoint\Universe\Type;
use Http\Client\HttpAsyncClient;
use Http\Promise\Promise;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

class Skills implements CacheWarmerInterface
{
    /**
     * @var \App\Esi\ApiClient
     */
    private $apiClient;
    /**
     * @var \Http\Client\HttpAsyncClient
     */
    private $httpClient;
    /**
     * @var array
     */
    private $categories;

    /**
     * @param \App\Esi\ApiClient $apiClient
     * @param \Http\Client\HttpAsyncClient $httpClient
     * @param array $categories
     */
    public function __construct(ApiClient $apiClient, HttpAsyncClient $httpClient, array $categories = [])
    {
        $this->apiClient = $apiClient;
        $this->httpClient = $httpClient;
        $this->categories = $categories;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        // As the HttpClient has a cache-pool assigned already,
        // we don't have to deal with the cache directory.

        $request = $this->apiClient->createRequest(new Category(['category_id' => 16]));
        $promise = $this->httpClient->sendAsyncRequest($request);

        $promise->then(function (ResponseInterface $response) {
            $this->warmupGroups($response);
        })->wait(false);
    }

    protected function warmupGroups(ResponseInterface $response)
    {
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $groups = $data['groups'];

        $promises = [];
        foreach ($groups as $id) {
            $request = $this->apiClient->createRequest(new Group(['group_id' => $id]));
            $promises[] = $this->httpClient->sendAsyncRequest($request);
        }

        /** @var Promise $promise */
        foreach ($promises as $promise) {
            $promise->then(function (ResponseInterface $response) {
                $this->warmupTypes($response);
            })->wait(false);
        }
    }

    protected function warmupTypes(ResponseInterface $response)
    {
        $json = $response->getBody()->getContents();
        $data = json_decode($json, true);
        $types = $data['types'];

        $promises = [];
        foreach ($types as $id) {
            $request = $this->apiClient->createRequest(new Type(['type_id' => $id]));
            $promises[] = $this->httpClient->sendAsyncRequest($request);
        }

        /** @var Promise $promise */
        foreach ($promises as $promise) {
            $promise->wait(false);
        }
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * Optional warmers can be ignored on certain conditions.
     *
     * A warmer should return true if the cache can be
     * generated incrementally and on-demand.
     *
     * @return bool true if the warmer is optional, false otherwise
     */
    public function isOptional()
    {
        return true;
    }
}
