<?php
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем директорию скрипта (полезно для запуска из cron'а)
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
// Подключаем API Simpla
require_once '../../api/Simpla.php';
// Подключим зависимые библиотеки (API RetailCRM)
require_once '../../vendor/autoload.php';
// Подключаем класс с методами интеграции RetailCRM
require_once '../../api/Retail.php';
$config = Retail::config('../../integration/config.php');

/**
 * @var integer Номер версии API RetailCRM
 */
$apiRetailCRM = 5;

// Требуется пройтись по всем заказам, собрать из них необходимые данные.
// После формирования исчерпывающего набора данных подготовить к отправке список задействованных покупателей (пакетно по 50 штук)
// API RetailCRM /api/customers/upload
// Затем выгрузить данные по самим заказам (пакетно по 50 штук)
// API /api/orders/upload

$retail          = new Retail($apiRetailCRM);
$clientRetailCRM = new \RetailCrm\ApiClient($config['urlRetail'], $config['keyRetail'], $config['siteCode']);
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
$data = $retail->fetch($lastDate);
//Retail::logger('Все данные для выгрузки: ' . print_r($data, true), 'orders-info');
// Массив данных разбит на пакеты - не более 50 записей в каждом пакете
// Пройдём по всему массиву клиентов и отправим каждый пакет
if (!is_null($data) && is_array($data)) {
    foreach ($data as $pack) {
        try {
            $response1 = $clientRetailCRM->customersUpload($pack['customers'], $config['siteCode']);
            Retail::logger('RetailCRM_Api::customersUpload: Выгрузили следующих клиентов: ' . print_r($pack['customers'], true), 'orders-info');
        } catch (\RetailCrm\Exception\CurlException $e) {
            Retail::logger('RetailCRM_Api::customersUpload ' . $e->getMessage(), 'connect');
            echo 'Сетевые проблемы. Ошибка подключения к retailCRM: ' . $e->getMessage();
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
                Retail::logger('Ошибка при выгрузке клиентов: ' . print_r($response1, true), 'customers');
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
            Retail::logger(date('Y-m-d H:i:s'), 'history-log'); // Помечаем время последней выгрузки заказов
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

        // Переходим к выгрузке оплат
        if ($apiRetailCRM > 4) {
            foreach ($pack['payments'] as $payment) {
                try {
                    $response3 = $clientRetailCRM->ordersPaymentCreate($payment, $config['siteCode']);
                } catch (\RetailCrm\Exception\CurlException $e) {
                    Retail::logger('RetailCRM_Api::ordersPaymentCreate ' . $e->getMessage(), 'connect');
                    echo 'Сетевые проблемы. Ошибка подключения к RetailCRM: ' . $e->getMessage();
                }
                // Получаем подробности обработки заказов
                if (isset($response3)) {
                    if ($response3->isSuccessful() && 201 === $response3->getStatusCode()) {
                        echo $status . 'Оплата успешно создана в RetaiCRM.' . '<br>';
                    } else {
                        Retail::logger('Ошибка при выгрузке оплаты: ' . print_r($response3, true), 'orders-error');
                        echo sprintf(
                            "Ошибка при выгрузке оплаты: [Статус HTTP-ответа %s] %s",
                            $response3->getStatusCode(),
                            $response3->getErrorMsg()
                        ) . '<br>';
                        $arErrorText = $response3->getErrors();
                        foreach ($arErrorText as $errorText) {
                            echo $errorText . '<br>';
                        }
                    }
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
