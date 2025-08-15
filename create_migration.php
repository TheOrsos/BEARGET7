<?php
require_once 'db_connect.php';

try {
    $sql = "
    CREATE TABLE `settlement_cash_withdrawals` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `fund_id` int(11) NOT NULL,
      `user_id` int(11) NOT NULL,
      `amount` decimal(10,2) NOT NULL,
      `status` varchar(20) NOT NULL DEFAULT 'pending',
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `confirmed_at` timestamp NULL DEFAULT NULL,
      PRIMARY KEY (`id`),
      KEY `fund_id` (`fund_id`),
      KEY `user_id` (`user_id`),
      CONSTRAINT `fk_scw_fund` FOREIGN KEY (`fund_id`) REFERENCES `shared_funds` (`id`) ON DELETE CASCADE,
      CONSTRAINT `fk_scw_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $conn->query($sql);

    echo "Table 'settlement_cash_withdrawals' created successfully." . PHP_EOL;

} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . PHP_EOL;
} finally {
    $conn->close();
}
?>