<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Represents a class that loads UserInterface objects from some source for the authentication system.
 *
 * In a typical authentication configuration, a username (i.e. some unique
 * user identifier) credential enters the system (via form login, or any
 * method). The user provider that is configured with that authentication
 * method is asked to load the UserInterface object for the given username
 * (via loadUserByUsername) so that the rest of the process can continue.
 *
 * Internally, a user provider can load users from any source (databases,
 * configuration, web service). This is totally independent of how the
 * authentication information is submitted or what the UserInterface object
 * looks like.
 */
class UserProvider implements UserProviderInterface
{
    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not found.
     *
     * @param string $username
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function loadUserByUsername($username): UserInterface
    {
        return new User($username);
    }

    /**
     * Refreshes the user for the account interface.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param \Symfony\Component\Security\Core\User\UserInterface $user
     *
     * @return \Symfony\Component\Security\Core\User\UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        return $user;
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class): bool
    {
        return $class === User::class;
    }
}
