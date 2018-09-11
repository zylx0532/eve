<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Controller;

use App\Esi\ApiClient;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class IndexController
{
    private $engine;
    private $apiClient;

    public function __construct(EngineInterface $engine, ApiClient $apiClient)
    {
        $this->engine = $engine;
        $this->apiClient = $apiClient;
    }

    public function __invoke(Request $request, UserInterface $user): Response
    {
        /** @var \App\Entity\User $user */
        $accessToken = $user->getAccessToken();
        $characterId = $user->getId();

        $bloodlines = $this->apiClient->getBloodlines();
        $character = $this->apiClient->getCharacter($accessToken, $characterId);
        $portrait = $this->apiClient->getPortrait($accessToken, $characterId);
        $races = $this->apiClient->getRaces();
        $skills = $this->apiClient->getSkills($accessToken, $characterId);
        $wallet = $this->apiClient->getWallet($accessToken, $characterId);

        $response = new Response($this->engine->render('pilot/overview.html.twig', [
            'bloodline' => $bloodlines[$character['bloodline_id']],
            'character_details' => $character,
            'character_portrait' => $portrait,
            'race' => $races[$character['race_id']],
            'sp' => $skills['total_sp'],
            'wallet' => $wallet,
        ]), 200);

        return $response;
    }
}
