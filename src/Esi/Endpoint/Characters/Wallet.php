<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Esi\Endpoint\Characters;

use App\Esi\Endpoint\Endpoint;
use App\Esi\EndpointInterface;

class Wallet extends Endpoint implements EndpointInterface
{
    public function path(): string
    {
        return $this->buildPath('/latest/characters/{character_id}/wallet/', $this->placeholders);
    }
}
