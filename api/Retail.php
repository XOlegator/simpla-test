<?php

// Подключим зависимые библиотеки (API RetailCRM)
require_once __DIR__ . '/../vendor/autoload.php';

// Подключим скрипт, где определяются уникальные для проекта методы
require_once __DIR__ . '/RetailTrait.php';

/**
 * Класс для работы с RetailCRM
 *
 * @copyright 2016 Oleg Ekhlakov
 * @author    Oleg Ekhlakov <subspam@mail.ru>
 *
 */

class Retail extends Simpla
{
    use RetailTrait;

    /**
     * @var integer Номер версии API RetailCRM
     */
    public static $apiVersion = \RetailCrm\ApiClient::V5;

    /**
     * @param string $apiVersion Номер версии API RetailCRM
     */
    public function __construct($apiVersion = \RetailCrm\ApiClient::V5)
    {
        if (!empty($apiVersion)) {
            self::$apiVersion = $apiVersion;
        }
    }

    const INTEGRATION_DIR = '/../integration';

    /**
     * Метод отправляет запрос в RetailCRM
     * @param string $method Метод из библиотеки API retailCRM
     * @param mixed[] $arData Массив данных
     * @param string $by Код номера, по которому нужно сзязать данные в retailCRM. Допустимые значения:
     *    "externalId" - связь по внешнему номеру (по номеру из Simpla CMS)
     *    "id"         - связь по номеру из retailCRM
     * @return boolean Флаг результата операции
     */
    public function request($method, $arData, $by = 'externalId')
    {
        $config = self::config($this->getIntegrationDir() . '/config.php');
        self::logger('$method = ' . $method . '; $arData: ' . print_r($arData, true) . '; $by = ' . $by, 'debug');
        $clientRetailCRM = new \RetailCrm\ApiClient(
            $config['urlRetail'],
            $config['keyRetail'],
            self::$apiVersion,
            $config['siteCode']
        );

        try {
            if ($method == 'ordersCreate' || $method == 'customersCreate') {
                $response = $clientRetailCRM->request->$method($arData, $config['siteCode']);
            } elseif ($method == 'ordersPaymentCreate') {
                $response = $clientRetailCRM->request->$method($arData);
                //self::logger('ordersPaymentCreate method $response = ' . print_r($response, true), 'debug');
            } elseif ($method == 'ordersEdit' || $method == 'customersEdit' || $method == 'ordersPaymentEdit') {
                $response = $clientRetailCRM->request->$method($arData, $by, $config['siteCode']);
                //self::logger('Edit method $response = ' . print_r($response, true), 'debug');
            }
            //self::logger('$response = ' . print_r($response, true), 'debug');

            if ($response->isSuccessful() && (200 === $response->getStatusCode() || 201 === $response->getStatusCode())) {
                //self::logger('RetailCRM_Api::' . $method . ' - Success. Response Id = ' . $response->id, 'debug');
                if ($method == 'ordersEdit') {
                    //self::logger('ordersEdit $arData = ' . print_r($arData, true), 'debug');

                    // В заказе могли измениться данные оплаты - это нужно отправлять отдельным запросом
                    // Данные оплаты из Simpla CMS
                    $arPaymentData = [
                        'amount' => $arData['amount'], // Сумма оплаты у нас совпадает с суммой заказа
                        'status' => $arData['payments'][0]['status']
                    ];
                    if (!empty($arData['payments'][0]['paidAt']) && $arData['payments'][0]['paidAt'] != '0000-00-00 00:00:00') {
                        $arPaymentData['paidAt'] = date('Y-m-d H:i:s', strtotime($arData['payments'][0]['paidAt']));
                    }
                    // Сначала определим, была ли назначена оплата по данным RetailCRM
                    if ($arPayments = self::getRetailPayments($arData['externalId'])) {
                        // Оплата уже есть в RetailCRM - обновим её данные
                        $firstRetailPayment = current($arPayments);
                        //self::logger('По данным RetailCRM оплаты есть. $firstRetailPayment = ' . print_r($firstRetailPayment, true), 'debug');
                        // Проверим, изменились ли данные оплаты
                        if ($arData['payments'][0]['type'] != $firstRetailPayment['type']) {
                            // Изменился тип оплаты, но тип оплаты нельзя менять в RetailCRM.
                            // Удалим старый платёж и создадим новый
                            //self::logger('В Simpla CMS изменился тип оплаты', 'debug');
                            $delPaymentResult = $clientRetailCRM->request->ordersPaymentDelete($firstRetailPayment['id']);
                            if ($delPaymentResult->isSuccessful()) {
                                // Удалили платёж в RetailCRM
                                //self::logger('Удалили платёж в RetailCRM: ' . print_r($arPaymentData, true), 'debug');
                                // Добавим оплату, если есть данные в Simpla CMS
                                if (isset($arData['payments'][0]['type'])) {
                                    $arPaymentData['externalId'] = 'p' . $arData['externalId']; // Идентификатор платежа у нас совпадает с идентификатором заказа
                                    $arPaymentData['type'] = $arData['payments'][0]['type'];
                                    $arPaymentData['order']['externalId'] = $arData['externalId'];
                                    $this->request('ordersPaymentCreate', $arPaymentData);
                                }
                            } else {
                                self::logger('Не удалось удалить платёж в RetailCRM: ' . print_r($arPaymentData, true), 'orders-error');
                            }
                        }
                        if ($arData['payments'][0]['status'] != $firstRetailPayment['status']) {
                            // Данные оплаты изменились, - изменим данные в RetailCRM
                            $arPaymentData['id'] = $firstRetailPayment['id'];
                            //self::logger('Данные оплаты изменились, - изменим данные в RetailCRM. $arPaymentData = ' . print_r($arPaymentData, true), 'debug');
                            $this->request('ordersPaymentEdit', $arPaymentData, 'id');
                        }
                    } else {
                        // Оплаты по данному заказу в RetailCRM нет - добавим оплату, если есть данные в Simpla CMS
                        self::logger('Оплаты по данному заказу в RetailCRM нет', 'debug');
                        if (isset($arData['payments'][0]['type'])) {
                            $arPaymentData['externalId'] = 'p' . $arData['externalId']; // Идентификатор платежа у нас совпадает с идентификатором заказа
                            $arPaymentData['type'] = $arData['payments'][0]['type'];
                            $arPaymentData['order']['externalId'] = $arData['externalId'];
                            $this->request('ordersPaymentCreate', $arPaymentData);
                        }
                    }
                }

                return true;
            } else {
                self::logger('RetailCRM_Api::' . $method . ' - Error. Status code: ' . $response->getStatusCode() . '. ' . print_r($response, true), 'connect');
            }
        } catch (\RetailCrm\Exception\CurlException $e) {
            self::logger('RetailCRM_Api::' . $method . ' ' . $e->getMessage(), 'connect');
        }

        return false;
    }

    /**
     * Метод формирует данные по заказу из SimplaCMS для отправки в RetailCRM.
     * @param integer $simplaOrderId Идентификатор заказа в Simpla CMS
     * @param integer $retailOrderId Идентификатор заказа в retailCRM. Если передано число больше нуля,
     *    то будет добавлен к массиву данных по заказу
     * @return array Массив данных по заказу. Может формировать массив в нескольких форматах:
     *    1) в формате API v4 RetailCRM (https://www.retailcrm.ru/docs/Developers/ApiVersion4#post--api-v4-orders-upload)
     *    2) в формате API v5 RetailCRM (https://www.retailcrm.ru/docs/Developers/ApiVersion5#post--api-v5-orders-upload)
     */
    public function getOrderRetailData($simplaOrderId, $retailOrderId = 0)
    {
        $arOrderData = [];
        $simplaOrderId = (int) $simplaOrderId;
        if (!($order = $this->orders->get_order($simplaOrderId))) {
            return $arOrderData;
        }

        $config = self::config($this->getIntegrationDir() . '/config.php');

        $items = []; // Очищаем массив товаров для новой итерации
        // Собираем массив товаров из заказа
        if ($purchases = $this->orders->get_purchases(['order_id' => $order->id])) {
            foreach ($purchases as $item) {
                $arItemData = [
                    'initialPrice' => (float) $item->price,
                    'productName'  => $item->product_name,
                    'quantity'     => (float) $item->amount,
                    //'properties'   => array (
                    //  'code'  => $item->variant_id,
                    //  'name'  => $item->variant_name,
                    //  'value' => $item->variant_id
                    //)
                ];

                // Если есть скидка на заказ, то нужно обнулить скидку по товарам в retailCRM
                if (self::$apiVersion == \RetailCrm\ApiClient::V5) {
                    $arItemData['discountManualAmount'] = 0.0;
                    $arItemData['discountManualPercent'] = 0.0;
                }

                /**
                 * Иногда так бывает, что из Simpla CMS удаляют товары, при этом остаются старые заказы,
                 * где эти товары были использованы. В этой ситуации в заказе вместо идентификатороа товаров
                 * стоит NULL. Но назаказы всё равно выгружаем в RetailCRM (без идентификаторов товаров)
                 */
                if (!empty($item->variant_id) && !empty($item->product_id)) {
                    $arItemData['offer']['externalId'] = $item->variant_id;
                    if ($product = $this->products->get_product($item->product_id)) {
                        if ($createdAt = $product->created) {
                            $arItemData['createdAt'] = $createdAt;
                        }
                    }
                }
                $items[] = $arItemData;
            }
        }

        $arOrderData = [
            'externalId'      => $order->id,
            'phone'           => $order->phone,
            'email'           => $order->email,
            'customerComment' => $order->comment,
            'managerComment'  => $order->note,
            'contragent'      => [
                'contragentType' => 'individual' // Доступны только физ. лица
            ],
            'orderType'       => 'eshop-individual', // Тип заказа - обязательное поле. В нашем случае тип всегдя один - заказ от физ. лица через ИМ
            'items'           => $items, // Массив товаров из заказа
            'delivery'        => [
                'code'    => $order->delivery_id,
                'cost'    => $order->delivery_price,
                'address' => [
                    'text' => $order->address
                ]
            ],
            'amount'          => $order->total_price
        ];

        if ($retailOrderId > 0) {
            // Если передан номер заказа из retailCRM, - считаем, что заказ был создан там
            // Добавляем в массив данных заказа - идентификатор заказа в retailCRM
            $arOrderData['id'] = (int)$retailOrderId;
        } else {
            // Номер заказа из retailCRM не был передан, - считаем, что заказ был создан в Simpla CMS
            $arOrderData['createdAt'] = date('Y-m-d H:i:s', strtotime($order->date));
            $arOrderData['orderMethod'] = 'shopping-cart'; // Только один способ заказа - через корзину
        }

        // Если есть код клиента, то создадим привязку, иначе в RetailCRM будет создан клиент по данным из Заказа
        if (intval($order->user_id) != 0) {
            $arOrderData['customer']['externalId'] = $order->user_id;
        }

        // Конвертируем статусы заказов
        $retailOrderStatus = $this->convertOrderStatus($order->status, 'simpla');
        if (false !== $retailOrderStatus) {
            $arOrderData['status'] = $retailOrderStatus;
        } else {
            self::logger('Нет соответствующего статуса заказа для RetailCRM. Код статуса заказа Simpla: ' . $order->status, 'orders-error');
        }

        // Конвертируем виды доставок
        if (!empty($order->delivery_id)) {
            // Код 0 в Simpla зарезервирован для невыбранного значения
            if (isset($config['deliveryType'][$order->delivery_id])) {
                $arOrderData['delivery']['code'] = $config['deliveryType'][$order->delivery_id];
            } else {
                self::logger('Нет соответствующего кода типа доставки для RetailCRM. Код типа доставки Simpla: ' . print_r($order->delivery_id, true), 'orders-error');
            }
        }

        if (self::$apiVersion == \RetailCrm\ApiClient::V4) {
            // Скидка на весь заказ
            $arOrderData['discount'] = (string) $order->coupon_discount; // Скидка в рублях
            $arOrderData['discountPercent'] = (string) $order->discount; // Скидка в процентах

            // Конвертируем виды оплат
            if (isset($order->payment_method_id) && !empty($order->payment_method_id)) {
                // Код 0 в Simpla зарезервирован для невыбранного значения
                if (isset($config['paymentType'][$order->payment_method_id])) {
                    $arOrderData['paymentType'] = $config['paymentType'][$order->payment_method_id];
                } else {
                    self::logger('Нет соответствующего кода типа оплаты для RetailCRM. Код типа оплаты Simpla: ' . print_r($order->payment_method_id, true), 'orders-error');
                }
            }

            // Конвертируем статусы оплат
            if (isset($order->paid) && ($order->paid != '' || !is_null($order->paid))) {
                if (isset($config['paymentStatus'][$order->paid])) {
                    $arOrderData['paymentStatus'] = $config['paymentStatus'][$order->paid];
                } else {
                    self::logger('Нет соответствующего статуса оплаты для RetailCRM. Код статуса оплаты Simpla: ' . print_r($order->paid, true), 'orders-error');
                }
            }
        } elseif (self::$apiVersion == \RetailCrm\ApiClient::V5) {
            // Скидка на весь заказ
            $arOrderData['discountManualAmount'] = (float) $order->coupon_discount; // Скидка в рублях
            $arOrderData['discountManualPercent'] = (float) $order->discount; // Скидка в процентах

            // Оплаты заказа. В симпле предусмотрена только одна оплата на заказ
            $arPayment = [];
            // Конвертируем виды оплат
            if (!empty($order->payment_method_id)) {
                // Код 0 в Simpla зарезервирован для невыбранного значения
                if (isset($config['paymentType'][$order->payment_method_id])) {
                    $arPayment['type'] = $config['paymentType'][$order->payment_method_id];
                } else {
                    self::logger('Нет соответствующего кода типа оплаты для RetailCRM. Код типа оплаты Simpla: ' . print_r($order->payment_method_id, true), 'orders-error');
                }
            }

            // Конвертируем статусы оплат
            if (isset($order->paid) && ($order->paid != '' || !is_null($order->paid))) {
                if (isset($config['paymentStatus'][$order->paid])) {
                    $arPayment['status'] = $config['paymentStatus'][$order->paid];
                    // Определяем дату оплаты
                    if ($order->paid && !empty($order->payment_date) && $order->payment_date != '0000-00-00 00:00:00') {
                        $arPayment['paidAt'] = date('Y-m-d H:i:s', strtotime($order->payment_date));
                    }
                } else {
                    self::logger('Нет соответствующего статуса оплаты для RetailCRM. Код статуса оплаты Simpla: ' . print_r($order->paid, true), 'orders-error');
                }
            }

            // Сохраняем данные по единственной оплате
            if (isset($arPayment['type'])) {
                // Способ оплаты в заказе Simpla CMS был указан
                $arOrderData['payments'][0] = $arPayment;
                // Ставим внешним идентификатором платежа номер заказа из Simpla CMS
                $arOrderData['payments'][0]['externalId'] = 'p' . $order->id;
            }

            // Определяем значения пользовательских полей заказа
            /**
             * Вычисление данных пользовательских полей лежит за рамками типовой интеграции:
             * требуется определить соответсувующий метод для пользовательского поля. Например,
             * для поля "order_url" - должен быть метод с названием "getOrderUrl". Метод принимает один параметр -
             * идентификатор заказа в Simpla CMS
             */
            if (!empty($config['orderCustomFields'])) {
                foreach ($config['orderCustomFields'] as $orderCustomField) {
                    $method = 'get' . str_replace('_', '', ucwords($orderCustomField, '_'));
                    if (method_exists(__CLASS__, $method)) {
                        $value = null;
                        // Определён метод для получения данных пользовательского поля
                        try {
                            $value = self::$method($simplaOrderId);
                        } catch (\Exception $exception) {
                            self::logger(
                                'Не удалось выполнить метод Retail::' . $method . ': ' . $exception->getMessage(),
                                'orders-error'
                            );
                        }
                        if (!is_null($value)) {
                            $arOrderData['customFields'][$orderCustomField] = $value;
                        }
                    }
                }
            }
        }

        // Добавляем данные по имени и фамилии клиента заказа
        if (!empty($order->name)) {
            $arCustomerName = explode(' ', $order->name);
            if (!empty($arCustomerName[0])) {
                $arOrderData['firstName'] = $arCustomerName[0];
            }
            if (!empty($arCustomerName[1])) {
                $arOrderData['lastName'] = $arCustomerName[1];
            }
        }

        return $arOrderData;
    }

    /**
     * Метод формирует данные по зарегистрированному пользователю для отправки в RetailCRM
     * @param integer $user_id Идентификатор пользователя
     * @return array Массив данных по пользователю в формате API v4 RetailCRM
     *    (http://www.retailcrm.ru/docs/Developers/ApiVersion4#post--api-v4-customers-upload)
     */
    public function getUserRetailData($user_id)
    {
        $arCustomerData = [];
        $user_id        = (int) $user_id;
        if (!($user = $this->users->get_user($user_id))) {
            return $arCustomerData;
        }

        $arCustomerData = [
            'externalId' => $user_id,
            'email'      => $user->email,
            'createdAt'  => $user->created,
            'contragent' => [
                'contragentType' => 'individual', // Доступны только физ. лица
            ]
        ];
        $arCustomerName = explode(' ', $user->name);
        if (!empty($arCustomerName[0])) {
            $arCustomerData['firstName'] = $arCustomerName[0];
        }
        if (!empty($arCustomerName[1])) {
            $arCustomerData['lastName'] = $arCustomerName[1];
        }
        if ($group_id = $user->group_id) {
            if ($discount = $this->users->get_group($group_id)->discount) {
                $arCustomerData['personalDiscount'] = (float) $discount;
            }
            if ($groupName = $this->users->get_group($group_id)->name) {
                $arCustomerData['customFields']['group'] = $groupName;
            }
        }

        return $arCustomerData;
    }

    /**
     * Метод формирует данные по оплате заказа для отправки в RetailCRM
     * @param integer $orderId Идентификатор заказа
     * @return array Массив данных по оплате в формате API v5 RetailCRM
     *    (https://www.retailcrm.ru/docs/Developers/ApiVersion5#post--api-v5-orders-payments-create)
     */
    public static function getPaymentRetailData($orderId)
    {
        $arPayment = [];

        $obSimpla = new Simpla();
        if ($order = $obSimpla->orders->get_order($orderId)) {
            $arPayment['order']['externalId'] = $order->id;
            // Конвертируем виды оплат
            if (isset($order->payment_method_id) && !empty($order->payment_method_id)) {
                $config = self::config(__DIR__ . self::INTEGRATION_DIR . '/config.php');
                // Код 0 в Simpla зарезервирован для невыбранного значения
                if (isset($config['paymentType'][$order->payment_method_id])) {
                    $arPayment['type'] = $config['paymentType'][$order->payment_method_id];

                    // Конвертируем статусы оплат
                    if (isset($order->paid) && ($order->paid != '' || !is_null($order->paid))) {
                        if (isset($config['paymentStatus'][$order->paid])) {
                            $arPayment['status'] = $config['paymentStatus'][$order->paid];

                            if ($order->paid) {
                                $arPayment['amount']  = $order->total_price;
                                $arPayment['paidAt']  = date('Y-m-d H:i:s', strtotime($order->payment_date));
                                $arPayment['comment'] = '';
                            }
                        } else {
                            self::logger('Нет соответствующего статуса оплаты для RetailCRM. Код статуса оплаты Simpla: ' . print_r($order->paid, true), 'orders-error');
                        }
                    }
                } else {
                    self::logger('Нет соответствующего кода типа оплаты для RetailCRM. Код типа оплаты Simpla: ' . print_r($order->payment_method_id, true), 'orders-error');
                }
            }
        }

        return $arPayment;
    }

    /**
     * Метод принимает данные по заказу из RetailCRM для обновления или изменения в Simpla CMS.
     * Если пришли данные по заказу, который был создан в retailCRM, то обратно отправим внешний код заказа -
     * номер заказа после создания в Simpla CMS
     * @param string $orderId Идентификатор заказа в RetailCRM
     * @param string $whichId Строка, определяющая идентификатор заказа из какой системы передан. Допустимые значения:
     *    "retail" - RetailCRM
     *    "simpla" - Simpla CMS
     * @return void
     */
    public function setOrderRetailData($orderId, $whichId = 'retail')
    {
        $config = self::config($this->getIntegrationDir() . '/config.php');
        //self::logger('setOrderRetailData. Данные, принятые из RetailCRM: ' . $orderId, 'orders-info');

        /**
         * Параметр, управляющий в API RetailCRM по какому идентификатору производить отбор (внешнему или внутреннему)
         * @var string
         */
        $paramSystem = 'id'; // По-умолчанию, ищем заказ по внутреннему идентификатору retailCRM
        if ($whichId == 'simpla') {
            $paramSystem = 'externalId'; // Ищем заказ по внешнему идентификатору для retailCRM
        }

        $clientRetailCRM = new \RetailCrm\ApiClient(
            $config['urlRetail'],
            $config['keyRetail'],
            self::$apiVersion,
            $config['siteCode']
        );
        try {
            $response = $clientRetailCRM->request->ordersGet($orderId, $paramSystem, $config['siteCode']);
        } catch (\RetailCrm\Exception\CurlException $e) {
            self::logger('RetailCRM_Api::ordersGet ' . $e->getMessage(), 'connect');
        }

        if (isset($response) && $response->isSuccessful() && 200 === $response->getStatusCode()) {
            // Получен ответ от retailCRM с данными заказа
            //self::logger('setOrderRetailData. RetailCRM_Api::ordersGet - Success. Receive data: ' . print_r($response->order, true), 'debug');
            $order = [];

            $order = [
                /*'separate_delivery' => '',*/
                /*'payment_date' => '',*/
                /*'coupon_code' => '',*/
                /*'date' => '',*/
                'user_id'         => (int) $response->order['customer']['externalId'],
                'name'            => implode(' ', [$response->order['firstName'], $response->order['lastName']]),
                'phone'           => empty($response->order['phone']) ? '' : $response->order['phone'],
                'email'           => empty($response->order['email']) ? '' : $response->order['email'],
                'comment'         => empty($response->order['customerComment']) ? '' : $response->order['customerComment'],
                /*'url' => '',*/
                'total_price'     => (float)$response->order['totalSumm'],
                'note'            => empty($response->order['managerComment']) ? '' : $response->order['managerComment']
            ];

            if (self::$apiVersion == \RetailCrm\ApiClient::V4) {
                if (isset($response->order['discountPercent'])) {
                    $order['discount'] = (float)$response->order['discountPercent'];
                }
                if (isset($response->order['discount'])) {
                    $order['coupon_discount'] = (float)$response->order['discount'];
                }
            } elseif (self::$apiVersion == \RetailCrm\ApiClient::V5) {
                if (isset($response->order['discountManualAmount'])) {
                    $order['discount'] = (float)$response->order['discountManualAmount'];
                }
                if (isset($response->order['discountManualPercent'])) {
                    $order['coupon_discount'] = (float)$response->order['discountManualPercent'];
                }
            }

            // Определяем код доставки
            if (isset($response->order['delivery']['code'])) {
                $deliveryId = array_search($response->order['delivery']['code'], $config['deliveryType']);
                if (false !== $deliveryId) {
                    $order['delivery_id'] = $deliveryId;
                }
            }
            // Определяем стоимость доставки
            if (isset($response->order['delivery']['cost'])) {
                $order['delivery_price'] = (float) $response->order['delivery']['cost'];
            }

            if (self::$apiVersion == \RetailCrm\ApiClient::V4) {
                // Определяем код способа оплаты
                if (isset($response->order['paymentType']) && $response->order['paymentType'] != '') {
                    $paymentId = array_search($response->order['paymentType'], $config['paymentType']);
                    if (false !== $paymentId) {
                        $order['payment_method_id'] = $paymentId;
                    }
                }
                // Определяем статус оплаты
                if (isset($response->order['paymentStatus'])) {
                    $paymentStatus = array_search($response->order['paymentStatus'], $config['paymentStatus']);
                    if (false !== $paymentStatus) {
                        $order['paid'] = $paymentStatus;
                    }
                }
            } elseif (self::$apiVersion == \RetailCrm\ApiClient::V5) {
                // Определяем код способа оплаты.
                // В RetailCRM может быть несколько оплат, а в SimplaCMS - только одна
                // Учитываем только тип первой оплаты
                if (!empty($response->order['payments'])) {
                    foreach ($response->order['payments'] as $payment) {
                        // Определяем код способа оплаты
                        if (!empty($payment['type'])) {
                            $paymentId = array_search($payment['type'], $config['paymentType']);
                            if (false !== $paymentId) {
                                $order['payment_method_id'] = $paymentId;
                            }
                        }
                        // Определяем статус оплаты
                        if (isset($payment['status'])) {
                            $paymentStatus = array_search($payment['status'], $config['paymentStatus']);
                            if (false !== $paymentStatus) {
                                $order['paid'] = $paymentStatus;
                            }
                        }
                    }
                }
            }

            if (isset($response->order['status'])) {
                $simplaOrderStatus = $this->convertOrderStatus($response->order['status'], 'retail');
                // Определяем отменён ли заказ
                if ($simplaOrderStatus == '3') {
                    $order['closed'] = 1;
                } else {
                    $order['closed'] = 0;
                }
                // Определяем статус заказа
                if (false !== $simplaOrderStatus) {
                    $order['status'] = $simplaOrderStatus;
                }
            }
            // Определяем адрес доставки
            if (!empty($response->order['customer']['address']['text'])) {
                $order['address'] = $response->order['customer']['address']['text'];
            }

            if (!empty($response->order['externalId'])) {
                // Вместе с данными заказа пришёл внешний код заказа (т.е. в retailCRM изменили какой-то заказ, который уже есть в SimplaCMS)
                $simplaOrderId = (int) $response->order['externalId'];
                //self::logger('Данные, принятые из RetailCRM и подготовленные к вставке: ' . print_r($order, true), 'orders-info');
                // Обновляем товары заказа
                // Сначала получим все текущие товары в заказе по данным SimplaCMS
                $purchases    = $this->orders->get_purchases(['order_id' => $simplaOrderId]);
                $products_ids = [];
                $variants_ids = [];
                foreach ($purchases as $purchase) {
                    $products_ids[]                         = $purchase->product_id;
                    $variants_ids[]                         = $purchase->variant_id;
                    $purchaseVariant[$purchase->variant_id] = $purchase->id;
                    $purchaseAmount[$purchase->variant_id]  = $purchase->amount;
                    $purchasePrice[$purchase->variant_id]   = $purchase->price;
                }

                // Суммарная скидка всех товаров (если назначалась скидка на товары, а не на заказ)
                $retailProductTotalDiscount = 0;

                // Получим все товары заказа по новым данным
                foreach ($response->order['items'] as $itemData) {
                    // Определим цену товара по данным retailCRM
                    $retailProductPrice = (float)$itemData['initialPrice'];
                    if (!empty($itemData['discountTotal']) && $itemData['initialPrice'] >= $itemData['discountTotal']) {
                        // Установлена скидка на товар, значит её нужно вычесть из начальной цены товара
                        $retailProductPrice -= $itemData['discountTotal'];
                        $retailProductTotalDiscount += $itemData['discountTotal'];

                        // Если пришла скидка по товару, то обнуляем скидку по заказу (она сюда уже включена)
                        if ($order['discount'] > 0) {
                            $order['discount'] = 0;
                        }
                        if ($order['coupon_discount'] > 0) {
                            $order['coupon_discount'] = 0;
                        }
                    }

                    if (in_array($itemData['offer']['externalId'], $variants_ids)) {
                        if ($purchaseAmount[$itemData['offer']['externalId']] != $itemData['quantity']
                            || $purchasePrice[$itemData['offer']['externalId']] != $retailProductPrice
                        ) {
                            // Товар повторяется, а количество и/или цена изменились
                            $this->orders->update_purchase(
                                $purchaseVariant[$itemData['offer']['externalId']],
                                [
                                    'amount' => (int) $itemData['quantity'],
                                    'price'  => $retailProductPrice
                                ]
                            );
                        } else {
                            // Вариант, когда изменилась не цена, а сам товар
                            $this->orders->update_purchase(
                                $purchaseVariant[$itemData['offer']['externalId']],
                                [
                                    'order_id'   => $response->order['externalId'],
                                    'variant_id' => intval($itemData['offer']['externalId'])
                                ]
                            );
                        }
                        unset($purchaseVariant[$itemData['offer']['externalId']]);
                    } else {
                        // Это новый товар - добавляем
                        $this->orders->add_purchase([
                            'order_id'   => $response->order['externalId'],
                            'variant_id' => intval($itemData['offer']['externalId']),
                            'amount'     => intval($itemData['quantity']),
                            'price'      => $retailProductPrice
                        ]);
                    }
                }

                if (!empty($purchaseVariant)) {
                    // Какие-то товары остались, значит, нужно удалить эти товары, - теперь их в заказе нет
                    foreach ($purchaseVariant as $purchase_id) {
                        $this->orders->delete_purchase($purchase_id);
                    }
                }
                //self::logger('Перед обновлением заказа в Simpla CMS по данным из RetailCRM: ' . print_r($order, true), 'debug');
                $this->orders->update_order($simplaOrderId, $order);
            } else {
                // Внешний номер заказа не пришёл, значит заказ был создан в retailCRM
                // Добавляем заказ в SimplaCMS
                $simplaOrderId = $this->orders->add_order($order);
                // Добавляем товары к заказу
                foreach ($response->order['items'] as $itemData) {
                    // Определим цену товара по данным retailCRM
                    $retailProductPrice = (float)$itemData['initialPrice'];
                    if (!empty($itemData['discountTotal']) && $itemData['initialPrice'] >= $itemData['discountTotal']) {
                        // Установлена скидка на товар, значит её нужно вычесть из начальной цены товара
                        $retailProductPrice -= $itemData['discountTotal'];

                        // Если пришла скидка по товару, то обнуляем скидку по заказу (она сюда уже включена)
                        if ($order['discount'] > 0) {
                            $order['discount'] = 0;
                        }
                        if ($order['coupon_discount'] > 0) {
                            $order['coupon_discount'] = 0;
                        }
                    }
                    $this->orders->add_purchase([
                        'order_id'   => $simplaOrderId,
                        'variant_id' => intval($itemData['offer']['externalId']),
                        'amount'     => intval($itemData['quantity']),
                        'price'      => $retailProductPrice
                    ]);
                }

                // Обновляем внешний код заказа в retailCRM (отсылаем данные о заказе в retailCRM)
                if ($this->isOnlineIntegration() && ($arOrderData = $this->getOrderRetailData($simplaOrderId, $orderId))) {
                    $this->request('ordersEdit', $arOrderData, $paramSystem);
                }
            }
        } else {
            self::logger('RetailCRM_Api::ordersGet - Error. Status code: ' . $response->getStatusCode(), 'connect');
        }
    }

    /**
     * Метод конвертирует код статуса заказа из одной системы в соответствующий код из другой системы (SimplaCMS и RetailCRM)
     * @param string $code Код статуса заказа
     * @param string $fromSystem Название системы, код которой передан в предыдущем параметре.
     *     Допустимы два значения: "simpla" и "retail". По-умолчанию, "simpla"
     * @return mixed Код статуса заказа в другой системе. Либо ЛОЖЬ в случае невозможности определить статус
     */
    public function convertOrderStatus($code, $fromSystem = 'simpla')
    {
        $fromSystem = trim(strval($fromSystem));
        if (empty($fromSystem)) {
            return false;
        }
        $config = self::config($this->getIntegrationDir() . '/config.php');

        if ($fromSystem == 'simpla') {
            return array_search($code, $config['orderStatus']);
        } elseif ($fromSystem == 'retail') {
            if (isset($config['orderStatus'][$code])) {
                return $config['orderStatus'][$code];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param string $log Путь к файлу-логу
     * @return false|string
     */
    public static function getDate($log)
    {
        if (file_exists($log)) {
            return file_get_contents($log);
        } else {
            return date('Y-m-d H:i:s', strtotime('-1 days', strtotime(date('Y-m-d H:i:s'))));
        }
    }

    /**
     * Переопределяем метод из класса Orders.
     * В исходном варианте нет фильтра по дате создания заказа
     * @param mixed[] $filter Массив для фильтра заказов
     * @return integer Количество заказов
     */
    public function count_orders($filter = [])
    {
        $created_since = '';
        if (isset($filter['created_since']) && !is_null($filter['created_since'])) {
            self::logger('Не null', 'debug');
            $created_since = $this->db->placehold("AND o.date > ?", $filter['created_since']);
        }

        // Выбираем заказы
        $query = $this->db->placehold("SELECT COUNT(DISTINCT id) as count
          FROM __orders AS o
          LEFT JOIN __orders_labels AS ol ON o.id=ol.order_id
          WHERE 1
          " . $created_since);
        $this->db->query($query);

        return $this->db->result('count');
    }

    /**
     * Переопределяем функцию из класса Orders. В исходном варианте нет фильтра по дате создания заказа.
     * Заказы сначала отбираются самые старые - сортировка по дате создания от старых к новым
     * @param mixed[] $filter Массив параметров для вильтрации
     * @return mixed[] Массив данных по заказам
     */
    public function get_orders($filter = [])
    {
        // По умолчанию
        $limit         = 50;
        $page          = 1;
        $created_since = '';
        if (isset($filter['limit'])) {
            $limit = max(1, intval($filter['limit']));
        }

        if (isset($filter['page'])) {
            $page = max(1, intval($filter['page']));
        }

        $sql_limit = $this->db->placehold(" LIMIT ?, ? ", ($page - 1) * $limit, $limit);
        if (isset($filter['created_since']) && !is_null($filter['created_since'])) {
            $created_since = $this->db->placehold("AND o.date > ?", $filter['created_since']);
        }

        // Выбираем заказы
        $query = $this->db->placehold("SELECT o.id, o.delivery_id, o.delivery_price, o.separate_delivery,
          o.payment_method_id, o.paid, o.payment_date, o.closed, o.discount, o.coupon_code, o.coupon_discount,
          o.date, o.user_id, o.name, o.address, o.phone, o.email, o.comment, o.status,
          o.url, o.total_price, o.note
          FROM __orders AS o
          LEFT JOIN __orders_labels AS ol ON o.id=ol.order_id
          WHERE 1
          " . $created_since . ' GROUP BY o.id ORDER BY id ASC ' . $sql_limit, '%Y-%m-%d');
        $this->db->query($query);
        $orders = [];
        foreach ($this->db->results() as $order) {
            $orders[$order->id] = $order;
        }

        return $orders;
    }

    /**
     * Метод формирует массив из всех клиентов и заказов ИМ Simpla.
     * Если передана дата, то отбираются все созданные заказы после этого времени.
     * @param string $date Строка с датой. Начиная с этой даты будут отбираться заказы по дате создания
     * @param integer $maxCountPack Максимальное количество отправляемых пакетов за один заход.
     *    Требуется ограничение, когда количество заказов большое, а память ограничена
     * @return mixed[] Массив данных по клиентам и заказам, разбитый на пакеты по 50 заказов
     */
    public function fetch($date, $maxCountPack = 2)
    {
        $result = [];
        if (!$this->managers->access('export')) {
            return $result;
        }
        // Проверка прав доступа при запуске скрипта из админки Simpla

        $countOrders = $this->count_orders(['created_since' => $date]);
        self::logger('Количество заказов для выгрузки: ' . $countOrders, 'orders-info');
        if ($countOrders == 0) {
            // Заказов вообще не нашлось
            self::logger('Заказов для выгрузки нет', 'orders-info');
            return null;
        }

        /**
         * Массив идентификаторов выгружаемых клиентов из Simpla CMS
         */
        $arUploadedCustiomerExternalIds = [];

        for ($i = 1; $i <= ceil($countOrders / 50); $i++) {
            // Если заказов много, то разбиваем отправку по пакетам по 50 штук
            $orders    = []; // Чистый массив заказов для нового пакета данных
            $customers = []; // Чистый массив клиентов для нового пакета данных
            // Получаем очередные заказы
            $ordersPack = $this->get_orders(['page' => $i, 'limit' => 50, 'created_since' => $date]);

            $maxCountPack--;
            if (!empty($ordersPack)) {
                $lastDate = '';
                foreach ($ordersPack as $order) {
                    if ($currentOrder = $this->getOrderRetailData($order->id)) {
                        $payments = []; // Массив оплат по заказам
                        $orders[] = $currentOrder;
                        // Получим данные по клиенту заказа
                        $user_id = (int) $order->user_id;
                        if ($user_id != 0) {
                            // Код клиента в Simpla <0> зарезервирован для экспресс клиентов (без регистрации)
                            if (!isset($arUploadedCustiomerExternalIds[$user_id])) {
                                $objCurrentCustomer = $this->users->get_user($user_id);
                                $customerData       = [
                                    'externalId' => $user_id,
                                    'email'      => empty($objCurrentCustomer->email) ? 'virtual@example.ru' : $objCurrentCustomer->email,
                                    'phones'     => [ // Попробуем телефон извлечь из заказа, т.к. в таблице клиентов телефон не хранится
                                        [
                                            'number' => $currentOrder['phone']
                                        ]
                                    ],
                                    'address'    => [
                                        'countryIso' => 'RU'
                                    ],
                                    'createdAt'  => empty($objCurrentCustomer->created) ? $currentOrder['createdAt'] : $objCurrentCustomer->created,
                                    'contragent' => [
                                        'contragentType' => 'individual' // Доступны только физ. лица
                                    ]
                                ];

                                // Адрес также не хранится в таблице клиентов, возьмём из заказа
                                if (!empty($currentOrder['delivery']['address']['text'])) {
                                    $customerData['address']['text'] = $currentOrder['delivery']['address']['text'];
                                }

                                // Добавляем данные по имени и фамилии клинта заказа
                                if (!empty($objCurrentCustomer->name)) {
                                    $arCustomerName = explode(' ', trim($objCurrentCustomer->name));
                                    if (!empty($arCustomerName[0])) {
                                        $customerData['firstName'] = $arCustomerName[0];
                                    }
                                    if (!empty($arCustomerName[1])) {
                                        $customerData['lastName'] = $arCustomerName[1];
                                    }
                                }
                                if (empty($customerData['firstName'])) {
                                    $customerData['firstName'] = 'UNKNOWN';
                                }
                                $customers[] = $customerData;
                                $arUploadedCustiomerExternalIds[$user_id] = $user_id;
                            }
                        }
                        $lastDate = $currentOrder['createdAt'];
                    }
                } // Прошлись по очередным заказам (не более 50)
                // Записываем результат в массив
                $result[] = [
                    'customers' => $customers,
                    'orders'    => $orders,
                    'lastDate'  => $lastDate
                ];
                self::logger('Сформирован ' . $i . '-й пакет данных (клиенты и заказы) для первоначальной загрузки. По дату ' . $lastDate, 'orders-info');
                if ($maxCountPack <= 0) {
                    self::logger('Это был последний пакет данных в текущем блоке выгрузки', 'orders-info');
                    break;
                }
            } else {
                self::logger('Simpla::Orders::get_orders: ' . 'Заказы не найдены', 'orders-info');
                return false;
            }
        }
        self::logger('Сформирован весь набор данных (клиенты и заказы с оплатами) для загрузки', 'orders-info');

        return $result;
    }


    /**
     * Метод проверяет наличие оплат по заказу по данным RetailCRM
     * @param integer $orderId Идентификатор заказа в одной из двух систем: Simpla CMS или RetailCRM
     * @param string $whichId Строка, определяющая идентификатор заказа из какой системы передан. Допустимые значения:
     *    "retail" - RetailCRM
     *    "simpla" - Simpla CMS
     * @return integer[] | false Массив данных по оплатам из RetailCRM, либо ЛОЖЬ в случае ошибки
     */
    public static function getRetailPayments($orderId, $whichId = 'simpla')
    {
        $arResult = [];

        $config = self::config(__DIR__ . self::INTEGRATION_DIR . '/config.php');

        /**
         * Параметр, управляющий в API RetailCRM по кокому идентификатору производить отбор (внешнему или внутреннему)
         * @var string
         */
        $paramSystem = 'id'; // По-умолчанию, ищем заказ по внутреннему идентификатору retailCRM

        if ($whichId == 'simpla') {
            $paramSystem = 'externalId'; // Ищем заказ по внешнему идентификатору для retailCRM
        }

        $clientRetailCRM = new \RetailCrm\ApiClient(
            $config['urlRetail'],
            $config['keyRetail'],
            self::$apiVersion,
            $config['siteCode']
        );
        try {
            $response = $clientRetailCRM->request->ordersGet($orderId, $paramSystem, $config['siteCode']);
        } catch (\RetailCrm\Exception\CurlException $e) {
            self::logger('RetailCRM_Api::ordersGet ' . $e->getMessage(), 'connect');
        }

        if (isset($response) && $response->isSuccessful() && 200 === $response->getStatusCode()) {
            //self::logger('getRetailPayments() RetailCRM_Api::ordersGet - Success. Receive data: ' . print_r($response->order, true), 'debug');
            if (isset($response->order['payments']) && !empty($response->order['payments'])) {
                foreach ($response->order['payments'] as $arPayment) {
                    $arResult[$arPayment['id']] = $arPayment;
                }
            }
        } else {
            self::logger('RetailCRM_Api::ordersGet - Error. Status code: ' . $response->getStatusCode(), 'connect');
        }
        //self::logger('getRetailPayments() $arResult = ' . print_r($arResult, true), 'debug');

        if (!empty($arResult)) {
            return $arResult;
        } else {
            return false;
        }
    }


    /**
     * Метод для логгирования действий интеграции
     * @param string $message Сообщение
     * @param string $type Тип сообщения
     * @param string | null $errors Массив ошибок для сохранения в лог
     * @return void Метод ничего не возвращает: только записывает в файлы логов
     */
    public static function logger($message, $type, $errors = null)
    {
        $format = '[' . date('Y-m-d H:i:s') . ']';
        if (!is_null($errors) && is_array($errors)) {
            $message .= ":\n";
            foreach ($errors as $error) {
                $message .= "\t" . $error . "\n";
            }
        } else {
            $message .= "\n";
        }

        $logDir = __DIR__ . self::INTEGRATION_DIR . '/log/';
        switch ($type) {
            case 'connect':
                $path = $logDir . 'connect-error.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'customers':
                $path = $logDir . 'customers-error.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'orders-info':
                $path = $logDir . 'orders-info.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'orders-error':
                $path = $logDir . 'orders-error.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'icml':
                $path = $logDir . 'icml.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'history':
                $path = $logDir . 'history-error.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'debug':
                $path = $logDir . 'debug.log';
                error_log($format . ' ' . $message, 3, $path);
                break;
            case 'history-log':
                $path = $logDir . 'history.log';
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
            return include $configFile;
        } else {
            return null;
        }
    }


    /**
     * Метод определяет, включена ли онлайн-интеграция с RetailCRM.
     * По-умолчанию, включена. Выключить можно, установив параметр конфигурации
     * $config['RETAIL_CRM']['IS_ONLINE_INTEGRATION'] = false
     * @return boolean Флаг, включена ли онлайн интеграция
     */
    public static function isOnlineIntegration()
    {
        $config = self::config(__DIR__ . self::INTEGRATION_DIR . '/config.php');
        if (isset($config['RETAIL_CRM']['IS_ONLINE_INTEGRATION'])) {
            return (bool) $config['RETAIL_CRM']['IS_ONLINE_INTEGRATION'];
        }

        return true;
    }
}
