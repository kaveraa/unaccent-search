<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Kaveraa\UnaccentSearch\SqliteFunction;

/**
 * DBAL middleware: registers the unaccent_search() function on each SQLite connection.
 * It does nothing on other databases.
 *
 * Useful on SQLite when Doctrine reuses a cached SQL (query cache): the DQL function
 * UNACCENT() is then not called again and cannot register the function itself.
 * Added automatically by UnaccentSearchBundle. Without Symfony:
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
