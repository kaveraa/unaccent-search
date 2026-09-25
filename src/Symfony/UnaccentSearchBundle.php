<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Symfony;

use Kaveraa\UnaccentSearch\Doctrine\SqliteMiddleware;
use Kaveraa\UnaccentSearch\Doctrine\UnaccentFunction;
use Kaveraa\UnaccentSearch\Normalizer;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Bundle Symfony : enregistre la fonction DQL UNACCENT auprès de DoctrineBundle,
 * et le middleware SQLite (voir SqliteMiddleware).
 *
 * Activation dans config/bundles.php :
 *
 *     Kaveraa\UnaccentSearch\Symfony\UnaccentSearchBundle::class => ['all' => true],
 *
 * Configuration facultative (config/packages/unaccent_search.yaml) :
 *
 *     unaccent_search:
 *         replacements: { 'ł': 'l', 'š': 's' }
 */
final class UnaccentSearchBundle extends AbstractBundle
{
    protected string $extensionAlias = 'unaccent_search';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->arrayNode('replacements')
                    ->info('Caractères à ajouter à la table de remplacement (clé en minuscule => équivalent sans accent).')
                    ->useAttributeAsKey('character')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->end()
                    ->defaultValue([])
                ->end()
            ->end();
    }

    /**
     * @param array{replacements: array<string, string>} $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->parameters()->set('unaccent_search.replacements', $config['replacements']);

        // Enregistre unaccent_search() sur les connexions SQLite (sans effet sur les autres bases)
        $container->services()
            ->set('unaccent_search.sqlite_middleware', SqliteMiddleware::class)
            ->tag('doctrine.middleware');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (! $builder->hasExtension('doctrine')) {
            return;
        }

        $dql = ['string_functions' => [UnaccentFunction::NAME => UnaccentFunction::class]];

        // Avec plusieurs entity managers déclarés, DoctrineBundle refuse une clé dql à la racine de orm :
        // la fonction est alors ajoutée à chacun d'eux.
        $entityManagers = [];
        foreach ($builder->getExtensionConfig('doctrine') as $config) {
            foreach (array_keys($config['orm']['entity_managers'] ?? []) as $name) {
                $entityManagers[$name] = ['dql' => $dql];
            }
        }

        $builder->prependExtensionConfig('doctrine', [
            'orm' => $entityManagers === [] ? ['dql' => $dql] : ['entity_managers' => $entityManagers],
        ]);
    }

    public function boot(): void
    {
        if ($this->container?->hasParameter('unaccent_search.replacements')) {
            /** @var array<string, string> $replacements */
            $replacements = $this->container->getParameter('unaccent_search.replacements');
            Normalizer::extend($replacements);
        }
    }
}
