<?php

/**
 * Скрипт служит для импорта данных по заказу из RetailCRM.
 * Скрипт принимает на вход идентификатор заказа.
 * Далее уже в специальном методе будут получены все данные заказа из RetailCRM и импортированы в Simpla CMS
 */

$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // Определяем директорию скрипта (полезно для запуска из cron'а)

// Задаём директорию выполнения скрипта
chdir($path_parts['dirname']);

// Подключаем API Simpla
require_once ('../api/Simpla.php');

// Подключим зависимые библиотеки (API RetailCRM)
require_once ('../vendor/autoload.php');

// Подключаем класс с методами для экспорта и импорта заказов, оплат и клиентов
require_once ('../api/Retail.php');

if (isset($_POST['id']) && is_scalar($_POST['id'])) {
    $orderId = trim((string) $_POST['id']);
    $obRetail = new Retail();
    $obRetail->setOrderRetailData($orderId);
}
