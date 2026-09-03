<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Driver-agnostic schema operations for migrations.
 *
 * The schema was originally written against SQL Server and reached into
 * sys.check_constraints / sys.indexes directly. These helpers express the same
 * operations for sqlsrv, mysql, mariadb, pgsql and sqlite so a single migration
 * history runs on any of them.
 *
 * SQLite cannot add or drop a constraint without rebuilding the table, so
 * constraint operations are skipped there. The status/role/type columns those
 * constraints guarded are validated in the application layer, and later
 * migrations drop the constraints anyway, so every driver converges on the
 * same end state.
 */
class SchemaCompat
{
    public static function driver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Drop every CHECK constraint on a table, optionally only those whose
     * definition mentions a given column.
     */
    public static function dropCheckConstraints(string $table, ?string $column = null): void
    {
        foreach (static::checkConstraintNames($table, $column) as $name) {
            static::dropCheckConstraint($table, $name);
        }
    }

    /**
     * Drop every CHECK constraint in the current schema.
     */
    public static function dropAllCheckConstraints(): void
    {
        try {
            $rows = match (static::driver()) {
                'sqlsrv' => DB::select(
                    'SELECT t.name AS table_name, cc.name AS constraint_name
                       FROM sys.check_constraints cc
                       JOIN sys.tables t ON cc.parent_object_id = t.object_id'
                ),
                'pgsql' => DB::select(
                    "SELECT rel.relname AS table_name, con.conname AS constraint_name
                       FROM pg_constraint con
                       JOIN pg_class rel ON rel.oid = con.conrelid
                       JOIN pg_namespace ns ON ns.oid = rel.relnamespace
                      WHERE con.contype = 'c'
                        AND ns.nspname = current_schema()"
                ),
                'mysql', 'mariadb' => DB::select(
                    "SELECT tc.TABLE_NAME AS table_name,
                            tc.CONSTRAINT_NAME AS constraint_name
                       FROM information_schema.TABLE_CONSTRAINTS tc
                      WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
                        AND tc.CONSTRAINT_TYPE = 'CHECK'"
                ),
                default => [],
            };
        } catch (Throwable) {
            return;
        }

        foreach ($rows as $row) {
            static::dropCheckConstraint($row->table_name, $row->constraint_name);
        }
    }

    public static function dropCheckConstraint(string $table, string $name): void
    {
        if (static::driver() === 'sqlite') {
            return;
        }

        // Look before dropping rather than dropping and swallowing the error:
        // PostgreSQL runs each migration in a transaction and a failed statement
        // aborts it, so a "harmless" failed DROP would sink every statement after it.
        if (! in_array($name, static::checkConstraintNames($table), true)) {
            return;
        }

        $clause = static::driver() === 'mysql' ? 'DROP CHECK' : 'DROP CONSTRAINT';

        DB::statement(sprintf(
            'ALTER TABLE %s %s %s',
            static::quote($table),
            $clause,
            static::quote($name)
        ));
    }

    /**
     * Add a CHECK constraint. The expression must be ANSI SQL so it parses on
     * every driver, e.g. "status IN ('draft', 'active')".
     */
    public static function addCheckConstraint(string $table, string $name, string $expression): void
    {
        if (static::driver() === 'sqlite' || ! Schema::hasTable($table)) {
            return;
        }

        // Keep re-runs idempotent; MySQL and Postgres both reject a duplicate name.
        static::dropCheckConstraint($table, $name);

        DB::statement(sprintf(
            'ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s)',
            static::quote($table),
            static::quote($name),
            $expression
        ));
    }

    /**
     * Names of the CHECK constraints on a table, filtered to those referencing
     * $column when one is given.
     *
     * @return list<string>
     */
    public static function checkConstraintNames(string $table, ?string $column = null): array
    {
        try {
            $rows = match (static::driver()) {
                'sqlsrv' => DB::select(
                    'SELECT cc.name AS constraint_name, cc.definition AS definition
                       FROM sys.check_constraints cc
                      WHERE cc.parent_object_id = OBJECT_ID(?)',
                    [$table]
                ),
                'pgsql' => DB::select(
                    "SELECT con.conname AS constraint_name,
                            pg_get_constraintdef(con.oid) AS definition
                       FROM pg_constraint con
                       JOIN pg_class rel ON rel.oid = con.conrelid
                       JOIN pg_namespace ns ON ns.oid = rel.relnamespace
                      WHERE con.contype = 'c'
                        AND rel.relname = ?
                        AND ns.nspname = current_schema()",
                    [$table]
                ),
                'mysql', 'mariadb' => DB::select(
                    "SELECT tc.CONSTRAINT_NAME AS constraint_name,
                            cc.CHECK_CLAUSE AS definition
                       FROM information_schema.TABLE_CONSTRAINTS tc
                       JOIN information_schema.CHECK_CONSTRAINTS cc
                         ON cc.CONSTRAINT_NAME = tc.CONSTRAINT_NAME
                        AND cc.CONSTRAINT_SCHEMA = tc.CONSTRAINT_SCHEMA
                      WHERE tc.CONSTRAINT_SCHEMA = DATABASE()
                        AND tc.TABLE_NAME = ?
                        AND tc.CONSTRAINT_TYPE = 'CHECK'",
                    [$table]
                ),
                default => [],
            };
        } catch (Throwable) {
            // MySQL below 8.0.16 has no CHECK_CONSTRAINTS view, and parses
            // CHECK without enforcing it, so there is nothing to drop.
            return [];
        }

        $names = [];

        foreach ($rows as $row) {
            $definition = (string) ($row->definition ?? '');

            if ($column !== null && stripos($definition, $column) === false) {
                continue;
            }

            $names[] = $row->constraint_name;
        }

        return $names;
    }

    /**
     * Drop every foreign key covering a column, whatever the key is named.
     */
    public static function dropForeignKeysForColumn(string $table, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }

        // SQLite can only drop a foreign key as part of a table rebuild, which
        // Laravel performs when the key is identified by column rather than name.
        if (static::driver() === 'sqlite') {
            try {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign([$column]));
            } catch (Throwable) {
            }

            return;
        }

        try {
            $foreignKeys = Schema::getForeignKeys($table);
        } catch (Throwable) {
            return;
        }

        foreach ($foreignKeys as $foreignKey) {
            if (! in_array($column, $foreignKey['columns'], true)) {
                continue;
            }

            try {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropForeign($foreignKey['name']));
            } catch (Throwable) {
            }
        }
    }

    public static function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! static::hasIndex($table, $index)) {
            return;
        }

        if (in_array(static::driver(), ['mysql', 'mariadb'], true)) {
            static::coverForeignKeysBeforeDroppingIndex($table, $index);
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($index));
    }

    /**
     * MySQL requires every foreign key to be backed by an index whose leading
     * columns are the key's columns, and refuses to drop the index that backs
     * one (error 1553). When $index is that index, add a plain index on the key
     * columns first so the drop can proceed. The extra index is redundant once
     * the migration recreates a wider one, but harmless.
     */
    protected static function coverForeignKeysBeforeDroppingIndex(string $table, string $index): void
    {
        try {
            $indexes = Schema::getIndexes($table);
            $foreignKeys = Schema::getForeignKeys($table);
        } catch (Throwable) {
            return;
        }

        $target = null;
        foreach ($indexes as $candidate) {
            if (strcasecmp($candidate['name'], $index) === 0) {
                $target = $candidate;
                break;
            }
        }

        if ($target === null) {
            return;
        }

        foreach ($foreignKeys as $foreignKey) {
            $columns = $foreignKey['columns'];

            if (array_slice($target['columns'], 0, count($columns)) !== $columns) {
                continue;
            }

            $alreadyCovered = false;
            foreach ($indexes as $other) {
                if (strcasecmp($other['name'], $index) !== 0
                    && array_slice($other['columns'], 0, count($columns)) === $columns) {
                    $alreadyCovered = true;
                    break;
                }
            }

            if ($alreadyCovered) {
                continue;
            }

            $name = $table.'_'.implode('_', $columns).'_fk_index';
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }

    /**
     * @param  list<string>  $columns
     */
    public static function createIndexIfMissing(string $table, string $index, array $columns): void
    {
        if (! Schema::hasTable($table) || static::hasIndex($table, $index)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $index));
    }

    public static function hasIndex(string $table, string $index): bool
    {
        try {
            return Schema::hasIndex($table, $index);
        } catch (Throwable) {
            return false;
        }
    }

    protected static function quote(string $identifier): string
    {
        return match (static::driver()) {
            'sqlsrv' => '['.str_replace(']', ']]', $identifier).']',
            'mysql', 'mariadb' => '`'.str_replace('`', '``', $identifier).'`',
            default => '"'.str_replace('"', '""', $identifier).'"',
        };
    }
}