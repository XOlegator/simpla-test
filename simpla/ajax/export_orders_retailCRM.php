<?php
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // Определяем директорию скрипта (полезно для запуска из cron'а)
chdir($path_parts['dirname']); // Задаём директорию выполнения скрипта
// Подключаем API Simpla
require_once '../../api/Simpla.php';
// Подключим зависимые библиотеки (API RetailCRM)
require_once '../../vendor/autoload.php';
// Подключаем класс с методами интеграции RetailCRM
require_once '../../api/Retail.php';
$config = Retail::config('../../integration/config.php');

set_time_limit(7200);

// Требуется пройтись по всем заказам, собрать из них необходимые данные.
// После формирования исчерпывающего набора данных подготовить к отправке список задействованных покупателей (пакетно по 50 штук)
// API RetailCRM /api/customers/upload
// Затем выгрузить данные по самим заказам (пакетно по 50 штук)
// API /api/orders/upload

$retail          = new Retail(\RetailCrm\ApiClient::V5);
$clientRetailCRM = new \RetailCrm\ApiClient(
    $config['urlRetail'],
    $config['keyRetail'],
    \RetailCrm\ApiClient::V5,
    $config['siteCode']
);
// Если есть непустой файл history.log, то значит полная выгрузка уже производилась. Повторять полную выгрузку нельзя.
$checkFile = $config['logDirectory'] . 'history.log';
if (file_exists($checkFile)) {
    // Выгрузим все заказы, появившиеся после указанного в логе времени
    $lastDate = Retail::getDate($checkFile);
    Retail::logger('Готовимся выгружать заказы, созданные после ' . $lastDate, 'orders-info');
} else {
    // Файла с датой последней выгрузки нет, поэтому считаем, что надо выгружать всё
    $lastDate = null;
    Retail::logger('Готовимся к первоначальной выгрузке всех заказов', 'orders-info');
}
$data = $retail->fetch($lastDate, 12);
//Retail::logger('Все данные для выгрузки: ' . print_r($data, true), 'orders-info');
// Массив данных разбит на пакеты - не более 50 записей в каждом пакете
// Пройдём по всему массиву клиентов и отправим каждый пакет
if (!is_null($data) && is_array($data)) {
    foreach ($data as $pack) {
        if (!empty($pack['customers'])) {
            try {
                //Retail::logger('RetailCRM_Api::customersUpload: Сейчас будем выгружать клиентов: ' . print_r($pack['customers'], true), 'orders-info');
                $response1 = $clientRetailCRM->request->customersUpload($pack['customers'], $config['siteCode']);
                //Retail::logger('RetailCRM_Api::customersUpload: Выгрузили следующих клиентов: ' . print_r($pack['customers'], true), 'orders-info');
            } catch (\RetailCrm\Exception\CurlException $e) {
                Retail::logger('RetailCRM_Api::customersUpload ' . $e->getMessage(), 'connect');
                echo 'Сетевые проблемы. Ошибка подключения к retailCRM: ' . $e->getMessage();
            }
        } else {
            Retail::logger('Выгружать было нечего, так что считаем, что по клиентам всё нормально выгрузилось', 'customers');
        }
        // Получаем подробности обработки клиентов
        if (isset($response1)) {
            if ($response1->isSuccessful()) {
                if (460 === $response1->getStatusCode()) {
                    Retail::logger('Ошибка при выгрузке некоторых клиентов: ' . print_r($response1, true), 'customers');
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
                Retail::logger('Ошибка при выгрузке клиентов: ' . print_r($response1, true) . '; $pack[\'customers\'] = ' . print_r($pack['customers'], true), 'customers');
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
            $response2 = $clientRetailCRM->request->ordersUpload($pack['orders'], $config['siteCode']);

            // Помечаем время последней выгрузки заказов
            if (!empty($pack['lastDate'])) {
                Retail::logger($pack['lastDate'], 'history-log');
            } else {
                Retail::logger(date('Y-m-d H:i:s'), 'history-log');
            }

            Retail::logger('RetailCRM_Api::ordersUpload: Выгрузили следующие заказы', 'orders-info');
        } catch (\RetailCrm\Exception\CurlException $e) {
            Retail::logger('RetailCRM_Api::ordersUpload ' . $e->getMessage(), 'connect');
            echo 'Сетевые проблемы. Ошибка подключения к retailCRM: ' . $e->getMessage();
        }
        // Получаем подробности обработки заказов
        if (isset($response2)) {
            if ($response2->isSuccessful() && 201 === $response2->getStatusCode()) {
                echo $status . 'Все заказы успешно выгружены в RetaiCRM.' . '<br>';
            } elseif ($response2->isSuccessful() && 460 === $response2->getStatusCode()) {
                Retail::logger('Ошибка при выгрузке некоторых заказов: ' . print_r($response2, true), 'customers');
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
            } else {
                Retail::logger('Ошибка при выгрузке заказов: ' . print_r($response2, true), 'orders-error');
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
} else {
    // Для выгрузки данных нет
    Retail::logger('Выгрузка прерывается - нечего выгружать.', 'orders-info');
    Retail::logger(date('Y-m-d H:i:s'), 'history-log'); // Помечаем время последней попытки выгрузки заказов
    echo 'Выгружать нечего';
}

if (file_exists($checkFile)) {
    // Выгрузим все заказы, появившиеся после указанного в логе времени
    $lastDate = Retail::getDate($checkFile);
    echo 'Выгрузили заказы по ' . $lastDate;
}
