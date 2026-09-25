<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Doctrine;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Kaveraa\UnaccentSearch\SqlExpression;

/**
 * Fonction DQL UNACCENT(expression) : ramène une valeur en minuscules sans accents.
 *
 *     SELECT p FROM App\Entity\Product p WHERE UNACCENT(p.name) LIKE :term ESCAPE '!'
 *
 * Le paramètre :term doit être construit avec Normalizer::pattern(), ou utilisez
 * directement le helper UnaccentQuery.
 *
 * Enregistrement (fait automatiquement par UnaccentSearchBundle sous Symfony) :
 *
 *     $config->addCustomStringFunction('UNACCENT', UnaccentFunction::class);
 */
final class UnaccentFunction extends FunctionNode
{
    public const NAME = 'UNACCENT';

    private Node|string $expression;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->expression = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $sql = $this->expression instanceof Node
            ? $this->expression->dispatch($sqlWalker)
            : $this->expression;

        return SqlExpression::wrap($sql, self::platformName($sqlWalker->getConnection()->getDatabasePlatform()));
    }

    private static function platformName(AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof MariaDBPlatform => SqlExpression::MARIADB,
            $platform instanceof AbstractMySQLPlatform => SqlExpression::MYSQL,
            $platform instanceof PostgreSQLPlatform => SqlExpression::POSTGRESQL,
            $platform instanceof SQLitePlatform => SqlExpression::SQLITE,
            default => $platform::class,
        };
    }
}
