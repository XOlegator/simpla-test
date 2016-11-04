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
$clientRetailCRM = new \RetailCrm\ApiClient($config['urlRetail'], $config['keyRetail'], $config['siteCode']);
// Если есть непустой файл history.log, то значит полная выгрузка уже производилась. Повторять полную выгрузку нельзя.
$checkFile = $config['logDirectory'] . 'history.log';
if (file_exists($checkFile)) {
    // Выгрузим все заказы, появившиеся после указанного в логе времени
    $lastDate = Tools::getDate($checkFile);
    Tools::logger('Готовимся выгружать заказы, созданные после ' . $lastDate, 'orders-info');
} else { // Файла с датой последней выгрузки нет, поэтому считаем, что надо выгружать всё
    $lastDate = null;
    Tools::logger('Готовимся к первоначальной выгрузке всех заказов', 'orders-info');
}
$data = $export_orders->fetch($lastDate);
//Tools::logger('Все данные для выгрузки: ' . print_r($data, true), 'orders-info');
// Массив данных разбит на пакеты - не более 50 записей в каждом пакете
// Пройдём по всему массиву клиентов и отправим каждый пакет
if (!is_null($data) && is_array($data)) {
    foreach ($data as $pack) {
        try {
            $response1 = $clientRetailCRM->customersUpload($pack['customers'], $config['siteCode']);
            Tools::logger('RetailCRM_Api::customersUpload: Выгрузили следующих клиентов: ' . print_r($pack['customers'], true), 'orders-info');
        } catch (\RetailCrm\Exception\CurlException $e) {
            Tools::logger('RetailCRM_Api::customersUpload ' . $e->getMessage(), 'connect');
            echo "Сетевые проблемы. Ошибка подключения к retailCRM: " . $e->getMessage();
        }
        // Получаем подробности обработки клиентов
        if (isset($response1)) {
            if ($response1->isSuccessful()) {
                if (460 === $response1->getStatusCode()) {
                    Tools::logger('Ошибка при выгрузке некоторых клиентов: ' . print_r($response1, true), 'customers');
                    echo 'Не все клиенты успешно выгружены в RetaiCRM.' . '<br>';
                    echo sprintf(
                        "Ошибка при выгрузке некоторых клиентов: [Статус HTTP-ответа %s] %s",
                        $response1->getStatusCode(),
                        $response1->getErrorMsg()
                    );
                    $arErrorText = $response1->getErrors();
                    foreach ($arErrorText as $errorText) {
                        echo $errorText . '<br>';
                    }
                }
                $status = 'Все клиенты успешно выгружены в RetaiCRM.' . '<br>';
            } else {
                Tools::logger('Ошибка при выгрузке клиентов: ' . print_r($response1, true), 'customers');
                echo sprintf(
                    "Ошибка при выгрузке клиентов: [Статус HTTP-ответа %s] %s",
                    $response1->getStatusCode(),
                    $response1->getErrorMsg()
                ) . '<br>';
                $arErrorText = $response1->getErrors();
                foreach ($arErrorText as $errorText) {
                    echo $errorText . '<br>';
                }
            }
        }

        // Переходим к выгрузке заказов
        try {
            $response2 = $clientRetailCRM->ordersUpload($pack['orders'], $config['siteCode']);
            Tools::logger(date('Y-m-d H:i:s'), 'history-log'); // Помечаем время последней выгрузки заказов
            Tools::logger('RetailCRM_Api::ordersUpload: Выгрузили следующие заказы', 'orders-info');
        } catch (\RetailCrm\Exception\CurlException $e) {
            Tools::logger('RetailCRM_Api::ordersUpload ' . $e->getMessage(), 'connect');
            echo "Сетевые проблемы. Ошибка подключения к retailCRM: " . $e->getMessage();
        }
        // Получаем подробности обработки заказов
        if (isset($response2)) {
            if ($response2->isSuccessful() && 201 === $response2->getStatusCode()) {
                echo $status . 'Все заказы успешно выгружены в RetaiCRM.' . '<br>';
            } elseif ($response2->isSuccessful() && 460 === $response2->getStatusCode()) {
                Tools::logger('Ошибка при выгрузке некоторых заказов: ' . print_r($response2, true), 'customers');
                echo 'Не все заказы успешно выгружены в RetaiCRM.' . '<br>';
                echo sprintf(
                    "Ошибка при выгрузке заказов: [Статус HTTP-ответа %s] %s",
                    $response1->getStatusCode(),
                    $response1->getErrorMsg()
                ) . '<br>';
                $arErrorText = $response2->getErrors();
                foreach ($arErrorText as $errorText) {
                    echo $errorText . '<br>';
                }
            }
            else {
                Tools::logger('Ошибка при выгрузке заказов: ' . print_r($response2, true), 'orders-error');
                echo sprintf(
                    "Ошибка при выгрузке заказов: [Статус HTTP-ответа %s] %s",
                    $response2->getStatusCode(),
                    $response2->getErrorMsg()
                ) . '<br>';
                $arErrorText = $response2->getErrors();
                foreach ($arErrorText as $errorText) {
                    echo $errorText . '<br>';
                }
            }
        }
    } // Конец цикла по пакетам
} else { // Для выгрузки данных нет
    Tools::logger('Выгрузка прерывается - нечего выгружать.', 'orders-info');
    Tools::logger(date('Y-m-d H:i:s'), 'history-log'); // Помечаем время последней попытки выгрузки заказов
    echo 'Выгружать нечего';
}
