<?php

declare(strict_types=1);

namespace Kaveraa\UnaccentSearch\Laravel;

use Illuminate\Contracts\Database\Query\Expression as ExpressionContract;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\ServiceProvider;
use Kaveraa\UnaccentSearch\Mode;
use Kaveraa\UnaccentSearch\Normalizer;
use Kaveraa\UnaccentSearch\SqlExpression;

/**
 * Ajoute au Query Builder (et donc à Eloquent) des méthodes de recherche
 * insensibles à la casse et aux accents :
 *
 *     Product::whereLikeUnaccent('name', $request->search)->get();
 *     Product::whereAnyLikeUnaccent(['name', 'code'], $request->search)->get();
 *
 * Chargé automatiquement par la découverte de paquets de Laravel.
 */
class UnaccentSearchServiceProvider extends ServiceProvider
{
    /**
     * Nom de colonne accepté : « colonne », « table.colonne » ou une clé JSON « colonne->cle->sous_cle ».
     */
    private const COLUMN_PATTERN = '/^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?(->[A-Za-z0-9_]+)*$/';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/unaccent-search.php', 'unaccent-search');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/unaccent-search.php' => $this->app->configPath('unaccent-search.php'),
            ], 'unaccent-search-config');
        }

        Normalizer::extend((array) $this->app['config']->get('unaccent-search.replacements', []));

        self::registerMacros();
    }

    /**
     * Enregistre les macros sur Illuminate\Database\Query\Builder.
     * Appelé par boot() ; public pour pouvoir être utilisé hors ServiceProvider.
     */
    public static function registerMacros(): void
    {
        Builder::macro('whereLikeUnaccent', function (
            string|ExpressionContract $column,
            ?string $term,
            Mode|string $mode = Mode::Contains,
            string $boolean = 'and',
            bool $not = false,
        ): Builder {
            /** @var Builder $this */
            if ($term === null || trim($term) === '') {
                return $this;
            }

            $sql = UnaccentSearchServiceProvider::columnSql($this, $column);
            $operator = $not ? 'not like' : 'like';

            return $this->whereRaw(
                "{$sql} {$operator} ? escape '".Normalizer::LIKE_ESCAPE."'",
                [Normalizer::pattern($term, $mode)],
                $boolean,
            );
        });

        Builder::macro('orWhereLikeUnaccent', function (
            string|ExpressionContract $column,
            ?string $term,
            Mode|string $mode = Mode::Contains,
        ): Builder {
            /** @var Builder $this */
            return $this->whereLikeUnaccent($column, $term, $mode, 'or');
        });

        Builder::macro('whereNotLikeUnaccent', function (
            string|ExpressionContract $column,
            ?string $term,
            Mode|string $mode = Mode::Contains,
            string $boolean = 'and',
        ): Builder {
            /** @var Builder $this */
            return $this->whereLikeUnaccent($column, $term, $mode, $boolean, true);
        });

        Builder::macro('orWhereNotLikeUnaccent', function (
            string|ExpressionContract $column,
            ?string $term,
            Mode|string $mode = Mode::Contains,
        ): Builder {
            /** @var Builder $this */
            return $this->whereLikeUnaccent($column, $term, $mode, 'or', true);
        });

        Builder::macro('whereAnyLikeUnaccent', function (
            array $columns,
            ?string $term,
            Mode|string $mode = Mode::Contains,
            string $boolean = 'and',
        ): Builder {
            /** @var Builder $this */
            if ($term === null || trim($term) === '' || $columns === []) {
                return $this;
            }

            return $this->where(function (Builder $query) use ($columns, $term, $mode): void {
                foreach ($columns as $column) {
                    $query->orWhereLikeUnaccent($column, $term, $mode);
                }
            }, boolean: $boolean);
        });

        Builder::macro('orWhereAnyLikeUnaccent', function (
            array $columns,
            ?string $term,
            Mode|string $mode = Mode::Contains,
        ): Builder {
            /** @var Builder $this */
            return $this->whereAnyLikeUnaccent($columns, $term, $mode, 'or');
        });
    }

    /**
     * Expression SQL normalisée pour une colonne (ou une expression brute DB::raw()).
     *
     * @internal
     */
    public static function columnSql(Builder $query, string|ExpressionContract $column): string
    {
        $grammar = $query->getGrammar();

        if ($column instanceof ExpressionContract) {
            $sql = (string) $column->getValue($grammar);
        } else {
            if (! preg_match(self::COLUMN_PATTERN, $column)) {
                throw new \InvalidArgumentException("Nom de colonne invalide : {$column}");
            }
            $sql = $grammar->wrap($column);
        }

        return SqlExpression::wrap($sql, $query->getConnection()->getDriverName());
    }
}
