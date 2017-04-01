<?php
chdir('..'); // задаем директорию выполнение скрипта
// Подключаем API Simpla
require_once ('api/Simpla.php');
// Подключим зависимые библиотеки (API RetailCRM)
require_once ('vendor/autoload.php');
// Подключаем описание класса для экспорта заказов и клиентов
require_once ('api/Retail.php');

if (isset($_POST['id']) && !empty($_POST['id'])) {
    $orderId = strval($_POST['id']);
    $obRetail = new Retail();
    $obRetail->setOrderRetailData($orderId);
}
    
