<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Esi;

interface Endpoint
{
    /**
     * Either GET, PUT or POST.
     *
     * @return string
     */
    public function method(): string;

    /**
     * Additional headers to send with the request.
     *
     * @return array
     */
    public function headers(): array;

    /**
     * Path for the request.
     *
     * @return string
     */
    public function path(): string;
}
