<?php

// Подключим зависимые библиотеки (API RetailCRM)
require_once (__DIR__ . '/../vendor/autoload.php');

/**
 * Класс для работы с RetailCRM
 *
 * @copyright	2016 Oleg Ekhlakov
 * @author		Oleg Ekhlakov
 *
 */
 
class Retail extends Simpla
{
    const INTEGRATION_DIR = '/../integration';
    
    /**
     * Метод отправляет запрос в RetailCRM
     */
    public function request($method, $arData)
    {
        $config = self::config($this->getIntegrationDir() . '/config.php');
        //self::logger('$arData: ' . print_r($arData, true) . "\n", 'orders-error');
    	$clientRetailCRM = new \RetailCrm\ApiClient($config['urlRetail'], $config['keyRetail'], $config['siteCode']);
        try {
            $response = $clientRetailCRM->$method($arData, $config['siteCode']);
        } catch (\RetailCrm\Exception\CurlException $e) {
            self::logger('RetailCRM_Api::' . $method . ' ' . $e->getMessage() . PHP_EOL, 'connect');
        }

        if ($response->isSuccessful() && 201 === $response->getStatusCode()) {
            self::logger('RetailCRM_Api::' . $method . ' - Success. Response Id = ' . $response->id . PHP_EOL, 'connect');
        } else {
            self::logger('RetailCRM_Api::' . $method . ' - Error. Status code: ' . $response->getStatusCode() . '; message: ' . $response->getErrorMsg() . '; Details: ' . print_r($response['errors'], true) . PHP_EOL, 'connect');
        }
    }


    /**
     * Метод формирует данные по новому заказу для отправки в RetailCRM
     * @param integer $order_id Идентификатор заказа
     * @return array Массив данных по заказу в формате API v4 RetailCRM (http://www.retailcrm.ru/docs/Developers/ApiVersion4#post--api-v4-orders-upload)
     */
	public function getNewOrderRetailData($order_id)
	{
        $arOrderData = [];
        $order_id = (int) $order_id;
        if (!($order = $this->orders->get_order($order_id))) {
            return $arOrderData;
        }

        $config = self::config($this->getIntegrationDir() . '/config.php');

        $items = []; // Очищаем массив товаров для новой итерации
        // Собираем массив товаров из заказа
        if ($purchases = $this->orders->get_purchases(array('order_id' => $order->id))) {
            foreach ($purchases as $item) {
                $arItemData = array(
                    "initialPrice" => (float) $item->price,
                    "offer"        => array(
                        "externalId" => $item->variant_id,
                    ),
                    "productName"  => $item->product_name,
                    "quantity"     => (float) $item->amount,
                    //"properties"   => array (
                    //  "code"  => $item->variant_id,
                    //  "name"  => $item->variant_name,
                    //  "value" => $item->variant_id
                    //)
                );
                if ($product = $this->products->get_product($item->product_id)) {
                    if ($createdAt = $product->created) {
                        $arItemData["createdAt"] = $createdAt;
                    }
                }
                $items[] = $arItemData;
            }
        }
        // Конвертируем виды доставок
        if (isset($config['deliveryType'][$order->delivery_id])) {
            $delivery = $config['deliveryType'][$order->delivery_id];
        } else {
            self::logger('Нет соответствующего кода типа доставки для RetailCRM. Код типа доставки Simpla: ' . $order->delivery_id . "\n", 'orders-error');
            $delivery = '';
        }
        // Конвертируем виды оплат
        if (isset($config['paymentType'][$order->payment_method_id])) {
            $payment = $config['paymentType'][$order->payment_method_id];
        } else {
            self::logger('Нет соответствующего кода типа оплаты для RetailCRM. Код типа оплаты Simpla: ' . $order->payment_method_id . "\n", 'orders-error');
            $payment = '';
        }
        // Конвертируем статусы оплат
        if (isset($config['paymentStatus'][$order->paid])) {
            $paymentStatus = $config['paymentStatus'][$order->paid];
        } else {
            self::logger('Нет соответствующего статуса оплаты для RetailCRM. Код статуса оплаты Simpla: ' . $order->paid . "\n", 'orders-error');
            $paymentStatus = '';
        }
        // Конвертируем статусы заказов
        if (isset($config['orderStatus'][$order->status])) {
            $orderStatus = $config['orderStatus'][$order->status];
        } else {
            self::logger('Нет соответствующего статуса заказа для RetailCRM. Код статуса заказа Simpla: ' . $order->status . "\n", 'orders-error');
            $orderStatus = '';
        }
        $arOrderData = array(
            'externalId'      => $order->id,
            'createdAt'       => date("Y-m-d H:i:s", strtotime($order->date)),
            'discount'        => $order->coupon_discount, // Скидка в рублях
            'discountPercent' => $order->discount, // Скидка в процентах
            'phone'           => $order->phone,
            'email'           => $order->email,
            'customerComment' => $order->comment,
            'managerComment'  => $order->note,
            'contragent'      => array(
                'contragentType' => 'individual', // Доступны только физ. лица
                'legalName'      => $order->name, // Имя в Simpla формируется в свободной форме
                'legalAddress'   => $order->address,
            ),
            'customer'        => array(
                'externalId' => (intval($order->user_id) == 0) ? 'order' . $order->id : $order->user_id, // Код клиента (по данным Simpla)
            ),
            'paymentType'     => $payment,
            'paymentStatus'   => $paymentStatus,
            'status'          => $orderStatus,
            'orderType'       => 'eshop-individual', // Тип заказа - обязательное поле. В нашем случае тип всегдя один - заказ от физ. лица через ИМ
            'orderMethod'     => 'shopping-cart', // Только один способ заказа - через корзину
            'items'           => $items, // Массив товаров из заказа
            'delivery'        => array(
                'code'    => $delivery,
                'cost'    => $order->delivery_price,
                'address' => $order->address          
            )
        );
        
        return $arOrderData;
	}


    /**
     * Метод формирует данные по зарегистрированному пользователю для отправки в RetailCRM
     * @param integer $user_id Идентификатор пользователя
     * @return array Массив данных по пользователю в формате API v4 RetailCRM (http://www.retailcrm.ru/docs/Developers/ApiVersion4#post--api-v4-customers-upload)
     */
	public function getNewUserRetailData($user_id)
	{
        $arCustomerData = [];
        $user_id = (int) $user_id;
        if (!($user = $this->users->get_user($user_id))) {
            return $arCustomerData;
        }

        $arCustomerData = array(
            'externalId'       => $user_id,
            'email'            => $user->email,
            'createdAt'        => $user->created,
            'contragent'       => array(
                'contragentType' => 'individual', // Доступны только физ. лица
                'legalName'      => $user->name
            )
        );
        $arCustomerName = explode(' ', trim($user->name));
        if (!empty($arCustomerName[0])) {
            $arCustomerData['firstName'] = $arCustomerName[0];
        }
        if (!empty($arCustomerName[1])) {
            $arCustomerData['lastName'] = $arCustomerName[1];
        }

        return $arCustomerData;
	}


    public static function getDate($log)
    {
        if (file_exists($log)) {
            return file_get_contents($log);
        } else {
            return date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s'))));
        }
    }


    public static function logger($message, $type, $errors = null)
    {
        $format = "[" . date('Y-m-d H:i:s') . "]";
        if (!is_null($errors) && is_array($errors)) {
            $message .= ":\n";
            foreach ($errors as $error) {
                $message .= "\t" . $error . "\n";
            }
        } else {
            $message .= "\n";
        }
        $logDir = self::getIntegrationDir() . '/log/'; 
        switch ($type) {
            case 'connect':
                $path = $logDir. "connect-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'customers':
                $path = $logDir . "customers-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'orders-info':
                $path = $logDir . "orders-info.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'orders-error':
                $path = $logDir . "orders-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'icml':
                $path = $logDir . "icml.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'history':
                $path = $logDir . "history-error.log";
                error_log($format . " " . $message, 3, $path);
                break;
            case 'history-log':
                $path = $logDir . "history.log";
                file_put_contents($path, $message);
                break;
        }

    }
    
    
    public function getIntegrationDir()
    {
        return __DIR__ . self::INTEGRATION_DIR;
    }


    public static function config($configFile)
    {
        if (file_exists($configFile)) {
            return include($configFile);
        } else {
            return null;
        }
    }
}
