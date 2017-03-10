<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;

class LogoutSuccessHandler implements LogoutSuccessHandlerInterface
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface
     */
    private $engine;

    /**
     * @param \Symfony\Bundle\FrameworkBundle\Templating\EngineInterface $engine
     */
    public function __construct(EngineInterface $engine)
    {
        $this->engine = $engine;
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function onLogoutSuccess(Request $request)
    {
        $quotes = [[
            'author' => 'Elbert Hubbard',
            'text' => 'Do not take life too seriously. You will never get out of it alive.',
        ], [
            'author' => 'Margaret Mead',
            'text' => 'Always remember that you are absolutely unique. Just like everyone else.',
        ], [
            'author' => 'Voltaire, Dictionnaire Philosophique (1764)',
            'text' => 'Common sense is not so common.',
        ], [
            'author' => 'Dr. Seuss',
            'text' => 'Don\'t cry because it\'s over, smile because it happened.',
        ], [
            'author' => 'Oscar Wilde',
            'text' => 'Be yourself; everyone else is already taken.',
        ], [
            'author' => 'Mahatma Gandhi',
            'text' => 'Be the change that you wish to see in the world.',
        ], [
            'author' => 'Mark Twain',
            'text' => 'If you tell the truth, you don\'t have to remember anything.',
        ]];

        $response = new Response($this->engine->render('logout.html.twig', [
            'quote' => $quotes[array_rand($quotes)],
        ]), 200);

        return $response;
    }
}
