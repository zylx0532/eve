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

class Constellation extends AbstractEndpoint implements EndpointInterface
{
    public function path(): string
    {
        return $this->buildPath('/latest/universe/constellations/{constellation_id}/', $this->placeholders);
    }
}
