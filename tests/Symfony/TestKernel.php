<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Tests\Symfony;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Kaveraa\UnaccentSearch\Symfony\UnaccentSearchBundle;
use Kaveraa\UnaccentSearch\Tests\Support\TestDatabase;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * Application Symfony minimale : FrameworkBundle + DoctrineBundle + UnaccentSearchBundle,
 * sans aucune configuration DQL manuelle.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @param array<string, string> $replacements configuration unaccent_search.replacements
     */
    public function __construct(
        private readonly bool $multipleEntityManagers = false,
        private readonly array $replacements = [],
    ) {
        parent::__construct('test', false);
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new DoctrineBundle();
        yield new UnaccentSearchBundle();
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__, 2);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/unaccent-search-tests/'.md5(serialize([$this->multipleEntityManagers, $this->replacements, TestDatabase::driver()]));
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir().'/log';
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', ['secret' => 'test', 'test' => true]);

        $params = TestDatabase::doctrineParams();
        $dbal = [
            'driver' => $params['driver'],
            'memory' => $params['memory'] ?? null,
            'host' => $params['host'] ?? null,
            'port' => $params['port'] ?? null,
            'dbname' => $params['dbname'] ?? null,
            'user' => $params['user'] ?? null,
            'password' => $params['password'] ?? null,
            'charset' => $params['charset'] ?? null,
        ];
        $dbal = array_filter($dbal, fn ($v) => $v !== null);

        $mapping = [
            'Test' => [
                'type' => 'attribute',
                'dir' => \dirname(__DIR__).'/Doctrine/Entity',
                'prefix' => 'Kaveraa\UnaccentSearch\Tests\Doctrine\Entity',
                'is_bundle' => false,
            ],
        ];

        if ($this->multipleEntityManagers) {
            $container->extension('doctrine', [
                'dbal' => $dbal,
                'orm' => [
                    'default_entity_manager' => 'main',
                    'entity_managers' => [
                        'main' => ['mappings' => $mapping],
                        'archive' => ['mappings' => $mapping],
                    ],
                ],
            ]);
        } else {
            $container->extension('doctrine', [
                'dbal' => $dbal,
                'orm' => ['mappings' => $mapping],
            ]);
        }

        if ($this->replacements !== []) {
            $container->extension('unaccent_search', ['replacements' => $this->replacements]);
        }
    }
}
