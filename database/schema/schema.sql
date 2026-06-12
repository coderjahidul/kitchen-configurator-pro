-- Kitchen Configurator Pro — Database Schema v1.0.0
-- Replace {prefix} with WordPress table prefix (e.g. wp_)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------------
-- Migration tracking
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_migrations` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `version`       VARCHAR(20)     NOT NULL,
    `class_name`    VARCHAR(191)    NOT NULL,
    `executed_at`   DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Kitchen layouts
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_layouts` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`            VARCHAR(100)    NOT NULL,
    `name`            VARCHAR(191)    NOT NULL,
    `description`     TEXT            NULL,
    `thumbnail_url`   VARCHAR(500)    NULL,
    `config_json`     LONGTEXT        NULL,
    `sort_order`      INT             NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL,
    `updated_at`      DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Cabinet categories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_cabinet_categories` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(100)    NOT NULL,
    `name`        VARCHAR(191)    NOT NULL,
    `description` TEXT            NULL,
    `sort_order`  INT             NOT NULL DEFAULT 0,
    `is_active`   TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`  DATETIME        NOT NULL,
    `updated_at`  DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Cabinets
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_cabinets` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id`           BIGINT UNSIGNED NOT NULL,
    `slug`                  VARCHAR(100)    NOT NULL,
    `name`                  VARCHAR(191)    NOT NULL,
    `description`           TEXT            NULL,
    `sku_prefix`            VARCHAR(50)     NULL,
    `default_width`         INT UNSIGNED    NOT NULL,
    `default_height`        INT UNSIGNED    NOT NULL,
    `default_depth`         INT UNSIGNED    NOT NULL,
    `min_width`             INT UNSIGNED    NOT NULL,
    `max_width`             INT UNSIGNED    NOT NULL,
    `min_height`            INT UNSIGNED    NOT NULL,
    `max_height`            INT UNSIGNED    NOT NULL,
    `min_depth`             INT UNSIGNED    NOT NULL,
    `max_depth`             INT UNSIGNED    NOT NULL,
    `width_step`            INT UNSIGNED    NOT NULL DEFAULT 10,
    `height_step`           INT UNSIGNED    NOT NULL DEFAULT 10,
    `depth_step`            INT UNSIGNED    NOT NULL DEFAULT 10,
    `base_price`            DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `dimension_price_json`  LONGTEXT        NULL,
    `image_url`             VARCHAR(500)    NULL,
    `sort_order`            INT             NOT NULL DEFAULT 0,
    `is_active`             TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`            DATETIME        NOT NULL,
    `updated_at`            DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category_active` (`category_id`, `is_active`),
    KEY `idx_active_sort` (`is_active`, `sort_order`),
    CONSTRAINT `fk_cabinets_category`
        FOREIGN KEY (`category_id`) REFERENCES `{prefix}kcp_cabinet_categories` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Materials
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_materials` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`             VARCHAR(100)    NOT NULL,
    `name`             VARCHAR(191)    NOT NULL,
    `material_type`    VARCHAR(50)     NOT NULL,
    `description`      TEXT            NULL,
    `price_modifier`   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `price_per_sqm`    DECIMAL(12,4)   NULL,
    `price_multiplier` DECIMAL(8,4)    NOT NULL DEFAULT 1.0000,
    `thumbnail_url`    VARCHAR(500)    NULL,
    `sort_order`       INT             NOT NULL DEFAULT 0,
    `is_active`        TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`       DATETIME        NOT NULL,
    `updated_at`       DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_type_active` (`material_type`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Colors
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_colors` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `material_id`     BIGINT UNSIGNED NOT NULL,
    `slug`            VARCHAR(100)    NOT NULL,
    `name`            VARCHAR(191)    NOT NULL,
    `hex_code`        CHAR(7)         NULL,
    `price_modifier`  DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `thumbnail_url`   VARCHAR(500)    NULL,
    `sort_order`      INT             NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL,
    `updated_at`      DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_material_slug` (`material_id`, `slug`),
    KEY `idx_material_active` (`material_id`, `is_active`),
    CONSTRAINT `fk_colors_material`
        FOREIGN KEY (`material_id`) REFERENCES `{prefix}kcp_materials` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Handles
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_handles` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`            VARCHAR(100)    NOT NULL,
    `name`            VARCHAR(191)    NOT NULL,
    `description`     TEXT            NULL,
    `price`           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `thumbnail_url`   VARCHAR(500)    NULL,
    `sort_order`      INT             NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL,
    `updated_at`      DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Accessories
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_accessories` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`            VARCHAR(100)    NOT NULL,
    `name`            VARCHAR(191)    NOT NULL,
    `category`        VARCHAR(50)     NOT NULL DEFAULT 'general',
    `description`     TEXT            NULL,
    `price`           DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `is_per_cabinet`  TINYINT(1)      NOT NULL DEFAULT 1,
    `thumbnail_url`   VARCHAR(500)    NULL,
    `sort_order`      INT             NOT NULL DEFAULT 0,
    `is_active`       TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`      DATETIME        NOT NULL,
    `updated_at`      DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_category_active` (`category`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Pricing rules
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_pricing_rules` (
    `id`                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`              VARCHAR(191)    NOT NULL,
    `rule_type`         VARCHAR(50)     NOT NULL,
    `entity_type`       VARCHAR(50)     NULL,
    `entity_id`         BIGINT UNSIGNED NULL,
    `conditions_json`   LONGTEXT        NOT NULL,
    `calculation_json`  LONGTEXT        NOT NULL,
    `priority`          INT             NOT NULL DEFAULT 100,
    `is_active`         TINYINT(1)      NOT NULL DEFAULT 1,
    `valid_from`        DATETIME        NULL,
    `valid_until`       DATETIME        NULL,
    `created_at`        DATETIME        NOT NULL,
    `updated_at`        DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_active_priority` (`is_active`, `priority`),
    KEY `idx_entity` (`entity_type`, `entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Projects
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_projects` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`        CHAR(36)        NOT NULL,
    `user_id`     BIGINT UNSIGNED NULL,
    `session_id`  VARCHAR(64)     NULL,
    `name`        VARCHAR(191)    NOT NULL,
    `created_at`  DATETIME        NOT NULL,
    `updated_at`  DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_uuid` (`uuid`),
    KEY `idx_user` (`user_id`),
    KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Configurations
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_configurations` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `uuid`                    CHAR(36)        NOT NULL,
    `project_id`              BIGINT UNSIGNED NULL,
    `layout_id`               BIGINT UNSIGNED NOT NULL,
    `user_id`                 BIGINT UNSIGNED NULL,
    `session_id`              VARCHAR(64)     NULL,
    `title`                   VARCHAR(191)    NOT NULL,
    `configuration_json`      LONGTEXT        NOT NULL,
    `pricing_snapshot_json`   LONGTEXT        NULL,
    `total_price`             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `price_hash`              VARCHAR(64)     NULL,
    `status`                  VARCHAR(20)     NOT NULL DEFAULT 'draft',
    `wc_order_id`             BIGINT UNSIGNED NULL,
    `wc_cart_item_key`        VARCHAR(64)     NULL,
    `quoted_at`               DATETIME        NULL,
    `created_at`              DATETIME        NOT NULL,
    `updated_at`              DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_uuid` (`uuid`),
    KEY `idx_user_status` (`user_id`, `status`),
    KEY `idx_session` (`session_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_status_updated` (`status`, `updated_at`),
    KEY `idx_wc_order` (`wc_order_id`),
    CONSTRAINT `fk_configurations_project`
        FOREIGN KEY (`project_id`) REFERENCES `{prefix}kcp_projects` (`id`)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_configurations_layout`
        FOREIGN KEY (`layout_id`) REFERENCES `{prefix}kcp_layouts` (`id`)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Configuration history (audit trail)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_configuration_history` (
    `id`                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `configuration_id`        BIGINT UNSIGNED NOT NULL,
    `configuration_json`      LONGTEXT        NOT NULL,
    `pricing_snapshot_json`   LONGTEXT        NULL,
    `action`                  VARCHAR(50)     NOT NULL,
    `actor_user_id`           BIGINT UNSIGNED NULL,
    `created_at`              DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_configuration_created` (`configuration_id`, `created_at`),
    CONSTRAINT `fk_history_configuration`
        FOREIGN KEY (`configuration_id`) REFERENCES `{prefix}kcp_configurations` (`id`)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Worktops (countertops)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_worktops` (
    `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`                   VARCHAR(100)    NOT NULL,
    `name`                   VARCHAR(191)    NOT NULL,
    `description`            TEXT            NULL,
    `default_length`         INT UNSIGNED    NOT NULL DEFAULT 3000,
    `default_depth`          INT UNSIGNED    NOT NULL DEFAULT 600,
    `min_length`             INT UNSIGNED    NOT NULL DEFAULT 600,
    `max_length`             INT UNSIGNED    NOT NULL DEFAULT 5000,
    `min_depth`              INT UNSIGNED    NOT NULL DEFAULT 400,
    `max_depth`              INT UNSIGNED    NOT NULL DEFAULT 1200,
    `length_step`            INT UNSIGNED    NOT NULL DEFAULT 10,
    `depth_step`             INT UNSIGNED    NOT NULL DEFAULT 10,
    `base_price`             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `price_per_sqm`          DECIMAL(12,4)   NULL,
    `price_per_linear_meter` DECIMAL(12,4)   NULL,
    `thumbnail_url`          VARCHAR(500)    NULL,
    `sort_order`             INT             NOT NULL DEFAULT 0,
    `is_active`              TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`             DATETIME        NOT NULL,
    `updated_at`             DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- Plinths (kickboards)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `{prefix}kcp_plinths` (
    `id`                     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug`                   VARCHAR(100)    NOT NULL,
    `name`                   VARCHAR(191)    NOT NULL,
    `description`            TEXT            NULL,
    `default_height`         INT UNSIGNED    NOT NULL DEFAULT 150,
    `min_height`             INT UNSIGNED    NOT NULL DEFAULT 100,
    `max_height`             INT UNSIGNED    NOT NULL DEFAULT 200,
    `height_step`            INT UNSIGNED    NOT NULL DEFAULT 10,
    `default_length`         INT UNSIGNED    NOT NULL DEFAULT 3000,
    `min_length`             INT UNSIGNED    NOT NULL DEFAULT 600,
    `max_length`             INT UNSIGNED    NOT NULL DEFAULT 10000,
    `length_step`            INT UNSIGNED    NOT NULL DEFAULT 10,
    `base_price`             DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    `price_per_linear_meter` DECIMAL(12,4)   NOT NULL DEFAULT 0.0000,
    `thumbnail_url`          VARCHAR(500)    NULL,
    `sort_order`             INT             NOT NULL DEFAULT 0,
    `is_active`              TINYINT(1)      NOT NULL DEFAULT 1,
    `created_at`             DATETIME        NOT NULL,
    `updated_at`             DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`),
    KEY `idx_active_sort` (`is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
