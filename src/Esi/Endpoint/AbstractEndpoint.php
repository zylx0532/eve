<?php

namespace App\Esi\Endpoint;

use App\Esi\Endpoint;

abstract class AbstractEndpoint implements Endpoint
{
    /**
     * @var array
     */
    protected $placeholders;

    /**
     * @param array $placeholders
     * @param array $options
     */
    public function __construct(array $placeholders = [], array $options = [])
    {
        $this->placeholders = $placeholders;
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return 'GET';
    }

    /**
     * @return array
     */
    public function headers(): array
    {
        return [];
    }

    /**
     * @param string $path
     * @param array $placeholders
     *
     * @return string
     */
    protected function buildPath(string $path, array $placeholders = []): string
    {
        $path = preg_replace_callback('/{([a-zA-Z_-]+)}/', function ($matches) use ($placeholders) {
            $placeholder = $matches[1];

            if (!array_key_exists($placeholder, $placeholders)) {
                throw new \RuntimeException(sprintf('No value provided for placeholder "%s".', $placeholder));
            }

            return $placeholders[$placeholder];
        }, $path);

        return $path;
    }
}
