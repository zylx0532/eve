<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Esi\Endpoint\Universe;

use App\Esi\EndpointInterface;
use App\Esi\Endpoint\AbstractEndpoint;

class Names extends AbstractEndpoint implements EndpointInterface
{
    public function method(): string
    {
        return 'POST';
    }

    public function headers(): array
    {
        return ['Content-Type' => 'application/json'];
    }

    public function path(): string
    {
        return '/latest/universe/names/';
    }
}
