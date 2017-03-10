<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Esi\Endpoint\Universe;

use App\Esi\Endpoint;
use App\Esi\Endpoint\AbstractEndpoint;

class Region extends AbstractEndpoint implements Endpoint
{
    public function path(): string
    {
        return $this->buildPath('/latest/universe/regions/{region_id}/', $this->placeholders);
    }
}
