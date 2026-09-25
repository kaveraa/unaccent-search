<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Kaveraa\UnaccentSearch\SqliteFunction;

/**
 * Middleware DBAL : enregistre la fonction unaccent_search() à chaque connexion SQLite.
 * Sans effet sur les autres bases.
 *
 * Utile sous SQLite quand Doctrine réutilise un SQL mis en cache (query cache) : la fonction
 * DQL UNACCENT() n'est alors pas rappelée et ne peut pas enregistrer la fonction elle-même.
 * Ajouté automatiquement par UnaccentSearchBundle. Sans Symfony :
 *
 *     $config->setMiddlewares([new SqliteMiddleware()]);
 */
final class SqliteMiddleware implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        return new class ($driver) extends AbstractDriverMiddleware {
            public function connect(#[\SensitiveParameter] array $params): DriverConnection
            {
                $connection = parent::connect($params);
                $native = $connection->getNativeConnection();

                if (($native instanceof \PDO && $native->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite')
                    || $native instanceof \SQLite3) {
                    SqliteFunction::register($native);
                }

                return $connection;
            }
        };
    }
}
