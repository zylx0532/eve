<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App\Session\Storage\Handler;

use Predis\Client;

class PredisSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var \Predis\Client
     */
    protected $redis;
    /**
     * @var int
     */
    protected $ttl;
    /**
     * @var string
     */
    protected $prefix;
    /**
     * @var int Default PHP max execution time in seconds
     */
    const DEFAULT_MAX_EXECUTION_TIME = 30;
    /**
     * @var bool Indicates an sessions should be locked
     */
    protected $locking;
    /**
     * @var bool Indicates an active session lock
     */
    protected $locked;
    /**
     * @var string Session lock key
     */
    private $lockKey;
    /**
     * @var string Session lock token
     */
    private $token;
    /**
     * @var int Microseconds to wait between acquire lock tries
     */
    private $spinLockWait;
    /**
     * @var int Maximum amount of seconds to wait for the lock
     */
    private $lockMaxWait;

    /**
     * Redis session storage constructor.
     *
     * @param \Predis\Client $redis Redis database connection
     * @param array $options Session options
     * @param string $prefix Prefix to use when writing session data
     * @param bool $locking
     * @param int $spinLockWait
     */
    public function __construct(Client $redis, array $options = [], $prefix = 'session.', $locking = true, $spinLockWait = 150000)
    {
        $this->redis = $redis;
        $this->ttl = isset($options['gc_maxlifetime']) ? (int) $options['gc_maxlifetime'] : 0;

        if (isset($options['cookie_lifetime']) && $options['cookie_lifetime'] > $this->ttl) {
            $this->ttl = (int) $options['cookie_lifetime'];
        }

        $this->prefix = $prefix;
        $this->locking = $locking;
        $this->locked = false;
        $this->lockKey = null;
        $this->spinLockWait = $spinLockWait;
        $this->lockMaxWait = ini_get('max_execution_time');

        if (!$this->lockMaxWait) {
            $this->lockMaxWait = self::DEFAULT_MAX_EXECUTION_TIME;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Lock the session data.
     *
     * @param mixed $sessionId
     *
     * @return bool
     */
    protected function lockSession($sessionId)
    {
        $attempts = (1000000 / $this->spinLockWait) * $this->lockMaxWait;

        $this->token = uniqid();
        $this->lockKey = $sessionId . '.lock';

        for ($i = 0; $i < $attempts; ++$i) {
            // We try to aquire the lock
            $setFunction = function (Client $redis, $key, $token, $ttl) {
                return $redis->set(
                    $key,
                    $token,
                    'PX',
                    $ttl,
                    'NX'
                );
            };

            $success = $setFunction($this->redis, $this->getRedisKey($this->lockKey), $this->token, $this->lockMaxWait * 1000 + 1);

            if ($success) {
                $this->locked = true;

                return true;
            }

            usleep($this->spinLockWait);
        }

        return false;
    }

    /**
     * Unlock the session data.
     */
    private function unlockSession()
    {
        // If we have the right token, then delete the lock
        $script = <<<LUA
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;

        $this->redis->eval($script, 1, $this->getRedisKey($this->lockKey), $this->token);

        $this->locked = false;
        $this->token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        if ($this->locking) {
            if ($this->locked) {
                $this->unlockSession();
            }
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($this->locking) {
            if (!$this->locked) {
                if (!$this->lockSession($sessionId)) {
                    return false;
                }
            }
        }

        return $this->redis->get($this->getRedisKey($sessionId)) ?: '';
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        if (0 < $this->ttl) {
            $this->redis->setex($this->getRedisKey($sessionId), $this->ttl, $data);
        } else {
            $this->redis->set($this->getRedisKey($sessionId), $data);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        $this->redis->del($this->getRedisKey($sessionId));
        $this->close();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime)
    {
        return true;
    }

    /**
     * Change the default TTL.
     *
     * @param int $ttl
     */
    public function setTtl($ttl)
    {
        $this->ttl = $ttl;
    }

    /**
     * Prepends the given key with a user-defined prefix (if any).
     *
     * @param string $key key
     *
     * @return string prefixed key
     */
    protected function getRedisKey($key)
    {
        if (empty($this->prefix)) {
            return $key;
        }

        return $this->prefix . $key;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }
}
