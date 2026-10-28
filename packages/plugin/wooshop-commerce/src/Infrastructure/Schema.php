<?php
declare(strict_types=1);
namespace WooShop\Commerce\Infrastructure;

use WooShop\Commerce\Support\DomainError;

final class Schema
{
    public const VERSION = 1;

    public static function install(Database $db): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $prefix = $db->tablePrefix();
        $charset = $db->wpdb()->get_charset_collate();
        $definitions = [
            "CREATE TABLE {$prefix}entities (
                id char(36) NOT NULL,
                kind varchar(64) NOT NULL,
                owner_id varchar(128) NOT NULL DEFAULT '0',
                payload longtext NOT NULL,
                version bigint unsigned NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY kind_owner (kind,owner_id,id),
                KEY kind_updated (kind,updated_at)
            ) ENGINE=InnoDB {$charset};",
            "CREATE TABLE {$prefix}jobs (
                id char(36) NOT NULL,
                handler varchar(100) NOT NULL,
                job_class varchar(30) NOT NULL,
                payload longtext NOT NULL,
                actor_id bigint unsigned NOT NULL DEFAULT 0,
                effect_key char(64) NOT NULL,
                state varchar(20) NOT NULL DEFAULT 'pending',
                attempts int unsigned NOT NULL DEFAULT 0,
                available_at datetime NOT NULL,
                expires_at datetime NULL,
                lease_token char(36) NULL,
                lease_until datetime NULL,
                error_code varchar(80) NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY effect_key (effect_key),
                KEY pending (state,available_at),
                KEY lease_expiry (state,lease_until)
            ) ENGINE=InnoDB {$charset};",
            "CREATE TABLE {$prefix}events (
                id char(36) NOT NULL,
                event_name varchar(100) NOT NULL,
                aggregate_id varchar(100) NOT NULL,
                aggregate_version bigint unsigned NOT NULL,
                effect_key char(64) NOT NULL,
                payload longtext NOT NULL,
                state varchar(20) NOT NULL DEFAULT 'pending',
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY effect_key (effect_key),
                KEY delivery (state,created_at)
            ) ENGINE=InnoDB {$charset};",
            "CREATE TABLE {$prefix}operations (
                operation_key char(64) NOT NULL,
                actor_id bigint unsigned NOT NULL,
                request_digest char(64) NOT NULL,
                response longtext NULL,
                state varchar(20) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (operation_key)
            ) ENGINE=InnoDB {$charset};",
            "CREATE TABLE {$prefix}rate_limits (
                bucket char(64) NOT NULL,
                hits int unsigned NOT NULL DEFAULT 1,
                expires_at bigint unsigned NOT NULL,
                PRIMARY KEY  (bucket),
                KEY expiry (expires_at)
            ) ENGINE=InnoDB {$charset};",
        ];
        if (class_exists(\WooShop\Commerce\Marketplace\Schema::class)) {
            $definitions = array_merge($definitions, \WooShop\Commerce\Marketplace\Schema::definitions($prefix, $charset));
        }
        foreach ($definitions as $definition) {
            dbDelta($definition);
        }
        foreach ($definitions as $definition) {
            if (!preg_match('/CREATE TABLE ([A-Za-z0-9_]+)/', $definition, $match)) { throw new DomainError('migration_definition_invalid', 'A table migration definition is invalid.', 503); }
            $table = $match[1];
            if ($db->wpdb()->get_var($db->wpdb()->prepare('SHOW TABLES LIKE %s', $db->wpdb()->esc_like($table))) !== $table) {
                throw new DomainError('migration_incomplete', 'A required plugin table is unavailable.', 503);
            }
            $engine = $db->wpdb()->get_var($db->wpdb()->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s', $table));
            if (strtoupper((string) $engine) !== 'INNODB') { throw new DomainError('transactional_storage_required', 'All commerce extension tables must use transactional InnoDB storage.', 503); }
        }
        update_option('wooshop_commerce_schema_version', self::VERSION, false);
    }
}
