<?php
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем директорию скрипта (полезно для запуска из cron'а)
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
// Подключаем API Simpla
require_once ('../../api/Simpla.php');
// Подключим зависимые библиотеки (API RetailCRM)
require_once ('../../vendor/autoload.php');
// Подключаем описание класса для экспорта заказов и клиентов
require_once ('../../integration/orders.php');
// Подключаем общие инструменты
require_once ('../../integration/Tools.php');
$config = Tools::config('../../integration/config.php');


// Требуется пройтись по всем заказам, собрать из них необходимые данные.
// После формирования исчерпывающего набора данных подготовить к отправке список задействованных покупателей (пакетно по 50 штук)
// API RetailCRM /api/customers/upload
// Затем выгрузить данные по самим заказам (пакетно по 50 штук)
// API /api/orders/upload

$export_orders = new ExportRetailCRM($config);
$clientRetailCRM = new \RetailCrm\ApiClient($config['urlRetail'], $config['keyRetail'], 'simpla-test-local');
// Если есть непустой файл history.log, то значит полная выгрузка уже производилась. Повторять полную выгрузку нельзя.
$checkFile = $config['logDirectory'] . 'history.log';
if (file_exists($checkFile)) {
    // Выгрузим все заказы, появившиеся после указанного в логе времени
    $lastDate = Tools::getDate($checkFile);
    Tools::logger('Готовимся выгружать заказы, созданные после ' . $lastDate . "\n", 'orders-info');
} else { // Файла с датой последней выгрузки нет, поэтому считаем, что надо выгружать всё
    $lastDate = null;
    Tools::logger('Готовимся к первоначальной выгрузке всех заказов' . "\n", 'orders-info');
}
$data = $export_orders->fetch($lastDate);
// Массив данных разбит на пакеты - не более 50 записей в каждом пакете
// Пройдём по всему массиву клиентов и отправим каждый пакет
if (!is_null($data) && is_array($data)) {
    foreach ($data as $pack) {
        try {
            $response1 = $clientRetailCRM->customersUpload($pack['customers'], 'simpla-test-local');
            Tools::logger('RetailCRM_Api::customersUpload: Выгрузили следующих клиентов' . "\n", 'orders-info');
        } catch (\RetailCrm\Exception\CurlException $e) {
            Tools::logger('RetailCRM_Api::customersUpload ' . $e->getMessage() . "\n", 'connect');
            echo "Сетевые проблемы. Ошибка подключения к retailCRM: " . $e->getMessage();
        }
        // Получаем подробности обработки клиентов
        if (isset($response1)) {
            if ($response1->isSuccessful() && 201 === $response1->getStatusCode()) {
                $status = 'Все клиенты успешно выгружены в RetaiCRM.' . '<br>';
                // Переходим к выгрузке заказов
                try {
                    $response2 = $clientRetailCRM->ordersUpload($pack['orders'], 'simpla-test-local');
                    Tools::logger(date('Y-m-d H:i:s'), 'history-log'); // Помечаем время последней выгрузки заказов
                    Tools::logger('RetailCRM_Api::ordersUpload: Выгрузили следующие заказы' . "\n", 'orders-info');
                } catch (\RetailCrm\Exception\CurlException $e) {
                    Tools::logger('RetailCRM_Api::ordersUpload ' . $e->getMessage() . "\n", 'connect');
                    echo "Сетевые проблемы. Ошибка подключения к retailCRM: " . $e->getMessage();
                }
                // Получаем подробности обработки заказов
                if (isset($response2)) {
                    if ($response2->isSuccessful() && 201 === $response2->getStatusCode()) {
                        echo $status . 'Все заказы успешно выгружены в RetaiCRM.' . '<br>';
                    } elseif ($response2->isSuccessful() && 460 === $response2->getStatusCode()) {
                        echo 'Не все заказы успешно выгружены в RetaiCRM.' . '<br>';
                        echo sprintf(
                            "Ошибка при выгрузке заказов: [Статус HTTP-ответа %s] %s", 
                            $response1->getStatusCode(),
                            $response1->getErrorMsg()
                        );
                    }
                    else {
                        echo sprintf(
                            "Ошибка при выгрузке заказов: [Статус HTTP-ответа %s] %s", 
                            $response2->getStatusCode(),
                            $response2->getErrorMsg()
                        );
                    }
                }
            } elseif ($response1->isSuccessful() && 460 === $response1->getStatusCode()) {
                echo 'Не все клиенты успешно выгружены в RetaiCRM.' . '<br>';
                echo sprintf(
                    "Ошибка при выгрузке клиентов: [Статус HTTP-ответа %s] %s", 
                    $response1->getStatusCode(),
                    $response1->getErrorMsg()
                );
            }
            else {
                echo sprintf(
                    "Ошибка при выгрузке клиентов: [Статус HTTP-ответа %s] %s", 
                    $response1->getStatusCode(),
                    $response1->getErrorMsg()
                );
            }
        }
    } // Конец цикла по пакетам
} else { // Для выгрузки данных нет
    Tools::logger('Выгрузка прерывается - нечего выгружать.' . "\n", 'orders-info');
    Tools::logger(date('Y-m-d H:i:s'), 'history-log'); // Помечаем время последней попытки выгрузки заказов
    echo 'Выгружать нечего';
}
