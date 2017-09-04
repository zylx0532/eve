<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller\Skills;

use App\Esi\ApiClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Route(service="controller.skills.queued")
 */
class QueuedController
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $engine;
    /**
     * @var \App\Esi\ApiClient
     */
    private $api;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $engine
     * @param \App\Esi\ApiClient $api
     */
    public function __construct(EngineInterface $engine, ApiClient $api)
    {
        $this->engine = $engine;
        $this->api = $api;
    }

    /**
     * @Route("/skills/queued", name="skills.queued")
     * @Method({"GET"})
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function __invoke(Request $request, UserInterface $user): Response
    {
        /** @var \App\Entity\User $user */
        $accessToken = $user->getAccessToken();
        $characterId = $user->getId();

        $queue = $this->api->getSkillQueue($accessToken, $characterId);

        usort($queue, function ($left, $right) {
            if ($left['queue_position'] === $right['queue_position']) {
                throw new \RuntimeException('Two skills cannot occupy the same queue position.');
            }

            return $left['queue_position'] < $right['queue_position'] ? -1 : 1;
        });

        $identifiers = array_unique(array_column($queue, 'skill_id'));
        $names = $this->api->getNames($identifiers);

        $response = new Response($this->engine->render('skills/queued.html.twig', [
            'queue' => $queue,
            'names' => $names,
        ]), 200);

        return $response;
    }
}
