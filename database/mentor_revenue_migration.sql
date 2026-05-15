-- Mentor revenue and capacity migration.
-- Scoped to premium revenue allocation and student-mentor availability.

ALTER TABLE mentor_profiles
    ADD COLUMN IF NOT EXISTS max_student_capacity INT(11) NOT NULL DEFAULT 10,
    ADD COLUMN IF NOT EXISTS is_premium_mentor TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE mentor_tasks
    ADD COLUMN IF NOT EXISTS attachment_file VARCHAR(255) DEFAULT NULL;

ALTER TABLE mentor_messages
    ADD COLUMN IF NOT EXISTS read_at DATETIME DEFAULT NULL;

CREATE TABLE IF NOT EXISTS platform_settings (
    setting_key VARCHAR(120) NOT NULL,
    setting_value VARCHAR(255) NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO platform_settings (setting_key, setting_value)
VALUES ('mentor_platform_share_percent', '70')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

INSERT INTO platform_settings (setting_key, setting_value)
VALUES ('mentor_pool_share_percent', '30')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

CREATE TABLE IF NOT EXISTS subscription_revenue_allocations (
    allocation_id INT(11) NOT NULL AUTO_INCREMENT,
    subscription_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    platform_percent DECIMAL(5,2) NOT NULL DEFAULT 70.00,
    mentor_pool_percent DECIMAL(5,2) NOT NULL DEFAULT 30.00,
    platform_share DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    mentor_pool_share DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    allocated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (allocation_id),
    UNIQUE KEY uq_revenue_allocation_subscription (subscription_id),
    KEY idx_revenue_allocations_user (user_id),
    KEY idx_revenue_allocations_month (allocated_at),
    CONSTRAINT fk_revenue_allocations_subscription FOREIGN KEY (subscription_id) REFERENCES student_subscriptions(subscription_id) ON DELETE CASCADE,
    CONSTRAINT fk_revenue_allocations_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
