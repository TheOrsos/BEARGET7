#!/usr/bin/env php
<?php
require_once 'db_connect.php';

try {
    $sql = "
    ALTER TABLE `settlement_payments`
    ADD COLUMN `from_account_id` INT(11) NULL DEFAULT NULL AFTER `amount`,
    ADD COLUMN `to_account_id` INT(11) NULL DEFAULT NULL AFTER `from_account_id`,
    ADD CONSTRAINT `fk_settlement_from_account` FOREIGN KEY (`from_account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `fk_settlement_to_account` FOREIGN KEY (`to_account_id`) REFERENCES `accounts`(`id`) ON DELETE SET NULL;
    ";

    $conn->query($sql);

    echo "Table 'settlement_payments' altered successfully." . PHP_EOL;

} catch (Exception $e) {
    echo "Error altering table: " . $e->getMessage() . PHP_EOL;
} finally {
    $conn->close();
}
?>