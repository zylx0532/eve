<?php declare(strict_types=1);

/*
 * (c) Rob Bast <rob.bast@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace App;

use Http\HttplugBundle\HttplugBundle;
use Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Bundle\WebServerBundle\WebServerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    /**
     * @var string
     */
    protected $name = 'app';
    /**
     * @var array
     */
    protected $environments = ['testing', 'development', 'production'];

    /**
     * @param string $environment
     * @param bool $debug
     *
     * @throws \RuntimeException
     */
    public function __construct(string $environment, bool $debug)
    {
        if (!in_array($environment, $this->environments, true)) {
            throw new \RuntimeException(sprintf(
                'Unsupported environment "%s", expected one of: %s',
                $environment,
                implode(', ', $this->environments)
            ));
        }

        parent::__construct($environment, $debug);
    }

    /**
     * @return \Symfony\Component\HttpKernel\Bundle\BundleInterface[]
     */
    public function registerBundles(): array
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new MonologBundle(),
            new SensioFrameworkExtraBundle(),
            new HttplugBundle(),

            new AppBundle(),
        ];

        if ($this->isTestingEnvironment() || $this->isDevelopmentEnvironment()) {
            $bundles[] = new DebugBundle();
            $bundles[] = new WebProfilerBundle();
            $bundles[] = new WebServerBundle();
        }

        return $bundles;
    }

    /**
     * @return string
     */
    public function getRootDir(): string
    {
        return __DIR__;
    }

    /**
     * @return string
     */
    public function getCacheDir(): string
    {
        return sprintf('%s/var/%s/cache', dirname(__DIR__), $this->getEnvironment());
    }

    /**
     * @return string
     */
    public function getLogDir()
    {
        return sprintf('%s/var/%s/logs', dirname(__DIR__), $this->getEnvironment());
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/config/config_' . $this->getEnvironment() . '.yml');
    }

    /**
     * @return bool
     */
    public function isTestingEnvironment(): bool
    {
        return 'testing' === $this->getEnvironment();
    }

    /**
     * @return bool
     */
    public function isDevelopmentEnvironment(): bool
    {
        return 'development' === $this->getEnvironment();
    }

    /**
     * @return bool
     */
    public function isProductionEnvironment(): bool
    {
        return 'production' === $this->getEnvironment();
    }
}
