CREATE TABLE `mod_optivo_optin` (
    `auth_uid`   BIGINT(20) UNSIGNED    NOT NULL,
    `secret`     VARCHAR(32)            NOT NULL,
    `type`       ENUM('nl', 'reminder') NOT NULL,
    `date_added` TIMESTAMP              NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
    COLLATE ='latin1_swedish_ci'
    ENGINE =InnoDB;
