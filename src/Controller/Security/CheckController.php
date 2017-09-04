<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller\Security;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * @Route(service="controller.security.check")
 */
class CheckController
{
    /**
     * @Route("/security/check", name="security.check")
     * @Method({"GET"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function __invoke(Request $request)
    {
        // void, handled by guard
    }
}
