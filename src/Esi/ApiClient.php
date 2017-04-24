<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Esi;

use Http\Message\RequestFactory;
use Psr\Http\Message\RequestInterface;

class ApiClient
{
    /**
     * @var \Http\Message\RequestFactory
     */
    private $factory;

    /**
     * @param \Http\Message\RequestFactory $factory
     */
    public function __construct(RequestFactory $factory)
    {
        $this->factory = $factory;
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
     * @param array $options Simplified options.
     *
     * @return array Extended options for use with getRequest.
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
