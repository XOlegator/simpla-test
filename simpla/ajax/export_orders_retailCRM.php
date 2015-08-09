<?php
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем директорию скрипта (полезно для запуска из cron'а)
chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
// Подключаем API Simpla
require_once('../../api/Simpla.php');
// Подключим зависимые библиотеки, в т.ч. API RetailCRM
require '../../vendor/autoload.php';
// Подключаем общие инструменты
require_once('../../integration/Tools.php');
$config = Tools::config('../../integration/config.php');

class ExportOrdersRetailCRM extends Simpla
{
    /*
      Переопределяем функцию из класса Orders. В исходном варианте нет фильтра по дате создания заказа    
    */
    public function count_orders($filter = array())
    {
        $created_since = '';
        if(isset($filter['created_since']) && !is_null($filter['created_since']))
            $created_since = $this->db->placehold('AND o.date > ?', $filter['created_since']);

        // Выбираем заказы
        $query = $this->db->placehold("SELECT COUNT(DISTINCT id) as count
          FROM __orders AS o 
          LEFT JOIN __orders_labels AS ol ON o.id=ol.order_id 
          WHERE 1
          $created_since");
        $this->db->query($query);
        return $this->db->result('count');
    }
    /*
      Переопределяем функцию из класса Orders. В исходном варианте нет фильтра по дате создания заказа    
    */
    function get_orders($filter = array())
    {
        // По умолчанию
        $limit = 50;
        $page = 1;
        $created_since = '';
        if(isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));
        if(isset($filter['page']))
            $page = max(1, intval($filter['page']));
        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
        if(isset($filter['created_since']) && !is_null($filter['created_since']))
            $created_since = $this->db->placehold('AND o.date > ?', $filter['created_since']);

        // Выбираем заказы
        $query = $this->db->placehold("SELECT o.id, o.delivery_id, o.delivery_price, o.separate_delivery,
          o.payment_method_id, o.paid, o.payment_date, o.closed, o.discount, o.coupon_code, o.coupon_discount,
          o.date, o.user_id, o.name, o.address, o.phone, o.email, o.comment, o.status,
          o.url, o.total_price, o.note
          FROM __orders AS o 
          LEFT JOIN __orders_labels AS ol ON o.id=ol.order_id 
          WHERE 1
          $created_since GROUP BY o.id ORDER BY status, id DESC $sql_limit", "%Y-%m-%d");
        $this->db->query($query);
        $orders = array();
        foreach($this->db->results() as $order)
            $orders[$order->id] = $order;
        return $orders;
    }
    /*
      Функция fetch() формирует массив из всех клиентов и заказов ИМ Simpla. Если передана дата, то отбираются все созданные заказы после этого времени.   
    */
    public function fetch($date)
    {
        if(!$this->managers->access('export')) return false; // Проверка прав доступа при запуске скрипта из админки Simpla

        $countOrders = $this->count_orders(array('created_since' => $date));
        Tools::logger('Количество заказов для выгрузки: ' . $countOrders . "\n", 'orders-info');
        if($countOrders == 0) { // Заказов вообще не нашлось
            Tools::logger('Заказов для выгрузки нет' . "\n", 'orders-info');
            return null;
        }
        for($i = 1; $i <= ceil($countOrders/50); $i++) { // Если заказов много, то разбиваем отправку по пакетам по 50 штук
            $orders = []; // Чистый массив заказов для нового пакета данных
            $customers = []; // Чистый массив клиентов для нового пакета данных
            // Получаем очередные заказы
            $ordersPack = $this->get_orders(array('page' => $i, 'limit' => 50, 'created_since' => $date));
            if(!empty($ordersPack)) {
                foreach($ordersPack as $p) {
                    $items = []; // Очищаем массив товаров для новой итерации
                    // Собираем массив товаров из заказа
                    if(null !== $this->orders->get_purchases(array('order_id' => $p->id))) {
                        foreach ($this->orders->get_purchases(array('order_id' => $p->id)) as $item) {
                            $items[] = array(
                                "initialPrice" => $item->price,
                                "productId"    => $item->id,
                                "productName"  => $item->product_name,
                                "quantity"     => $item->amount,
                                //"properties"   => array (
                                //  "code"  => $item->variant_id,
                                //  "name"  => $item->variant_name,
                                //  "value" => $item->variant_id
                                //)
                            );
                        }
                    }
                    // Конвертируем виды доставок
                    if (isset($config['deliveryType'][$p->delivery_id])) {
                        $delivery = $config['deliveryType'][$p->delivery_id];
                    } else {
                        Tools::logger('Нет соответствующего кода типа доставки для RetailCRM. Код типа доставки Simpla: ' . $p->delivery_id . "\n", 'orders-error');
                        $delivery = '';
                    }
                    // Конвертируем виды оплат
                    if (isset($config['paymentType'][$p->payment_method_id])) {
                        $payment = $config['paymentType'][$p->payment_method_id];
                    } else {
                        Tools::logger('Нет соответствующего кода типа оплаты для RetailCRM. Код типа оплаты Simpla: ' . $p->payment_method_id . "\n", 'orders-error');
                        $payment = '';
                    }
                    // Конвертируем статусы оплат
                    if (isset($config['paymentStatus'][$p->paid])) {
                        $paymentStatus = $config['paymentStatus'][$p->paid];
                    } else {
                        Tools::logger('Нет соответствующего статуса оплаты для RetailCRM. Код статуса оплаты Simpla: ' . $p->paid . "\n", 'orders-error');
                        $paymentStatus = '';
                    }
                    // Конвертируем статусы заказов
                    if (isset($config['orderStatus'][$p->status])) {
                        $orderStatus = $config['orderStatus'][$p->status];
                    } else {
                        Tools::logger('Нет соответствующего статуса заказа для RetailCRM. Код статуса заказа Simpla: ' . $p->status . "\n", 'orders-error');
                        $orderStatus = '';
                    }
                    $orders[] = array(
                        'externalId'      => $p->id,
                        'createdAt'       => date("Y-m-d H:i:s", strtotime($p->date)),
                        'discount'        => $p->discount,
                        'phone'           => $p->phone,
                        'email'           => $p->email,
                        'firstName'       => $p->name,
                        'customerComment' => $p->comment,
                        'managerComment'  => $p->note,
                        'contragentType'  => 'individual', // Доступны только физ. лица
                        'legalName'       => $p->name, // Имя в Simpla формируется в свободной форме
                        'legalAddress'    => $p->address,
                        'customerId'      => (intval($p->user_id) == 0) ? 'order' . $p->id : $p->user_id, // Код клиента (по данным Simpla)
                        'paymentType'     => $payment,
                        'paymentStatus'   => $paymentStatus,
                        'status'          => $orderStatus,
                        'orderType'       => 'eshop-individual', // Тип заказа - обязательное поле. В нашем случае тип всегдя один - заказ от физ. лица через ИМ
                        'orderMethod'     => 'shopping-cart', // Только один способ заказа - через корзину
                        'items'           => $items, // Массив товаров из заказа
                        'delivery'        => array(
                            'code'    => $delivery,
                            'cost'    => $p->delivery_price,
                            'address' => $p->address          
                        )
                    );
                    $currentOrder = end($orders);
                    if(intval($p->user_id) == 0) { // Код клиента в Simpla <0> зарезервирован для экспресс клиентов (без регистрации)
                        // Раз регистрации не было, то собираем крупицы информации по клиенту из самого заказа
                        $customers[] = array(
                            'externalId'     => 'order' . $currentOrder["externalId"], // Ссылаться некуда, - пользователя нет в базе. Сгенерируем виртуальный код клиента по маске ["order"<номер заказа>]
                            'firstName'      => $currentOrder["legalName"], // Тут может быть не только имя
                            'email'          => $currentOrder["email"],
                            'phones'         => array (
                                'number' => $currentOrder["phone"]
                            ),
                            'address'        => array (
                                'text'   => $currentOrder["delivery"]["address"]
                            ),
                            'createdAt'      => $currentOrder["createdAt"], // Пользователь создан в момент заказа
                            'contragentType' => 'individual', // Доступны только физ. лица
                            'legalName'      => $currentOrder["legalName"]
                        );
                    } else { // Клиент зарегистрировался на сайте ИМ, значит берём данные из таблицы клиентов
                        $objCurrentCustomer = $this->users->get_user(intval($p->user_id));
                        $customers[] = array(
                            'externalId'     => $p->user_id,
                            'firstName'      => $objCurrentCustomer->name,
                            'email'          => $objCurrentCustomer->email,
                            'phones'         => array ( // Попробуем телефон извлечь из заказа, т.к. в таблице клиентов телефон не хранится
                                'number' => $currentOrder["phone"]
                            ),
                            'address'        => array ( // Адрес также не хранится в таблице клиентов, возьмём из заказа
                                'text'   => $currentOrder["delivery"]["address"]
                            ),
                            'createdAt'      => $objCurrentCustomer->created,
                            'contragentType' => 'individual', // Доступны только физ. лица
                            'legalName'      => $objCurrentCustomer->name
                        );
                    }
                } // Прошлись по очередным заказам (не более 50)
                // Записываем результат в массив
                $result[] = array('customers' => $customers, 'orders' => $orders);
                Tools::logger('Сформирован ' . $i . '-й пакет данных (клиенты и заказы) для первоначальной загрузки' . "\n", 'orders-info');
            } else {
                Tools::logger('Simpla::Orders::get_orders: ' . 'Заказы не найдены' . "\n", 'orders-info');
                return false;
            }
        }
        Tools::logger('Сформирован весь набор данных (клиенты и заказы) для загрузки' . "\n", 'orders-info');
        return $result;
    }
}

// Требуется пройтись по всем заказам, собрать из них необходимые данные.
// После формирования исчерпывающего набора данных подготовить к отправке список задействованных покупателей (пакетно по 50 штук)
// API RetailCRM /api/customers/upload
// Затем тоже самое по задействованным в заказах товарам
// В последнюю очередь выгрузить данные по самим заказам (пакетно по 50 штук)
// API /api/orders/upload
$export_orders = new ExportOrdersRetailCRM();
$clientRetailCRM = new \RetailCrm\ApiClient($config['urlRetail'], $config['keyRetail'], 'simpla-test-local');
// Если есть непустой файл history.log, то значит полная выгрузка уже производилась. Повторять полную выгрузку нельзя.
$checkFile = '../../integration/log/history.log';
if(file_exists($checkFile)) {
    // Выгрузим все заказы, появившиеся после указанного в логе времени
    $lastDate = Tools::getDate($checkFile);
    Tools::logger('Выгружаем заказы, созданные после ' . $lastDate . "\n", 'orders-info');
} else { // Файла с датой последней выгрузки нет, поэтому считаем, что надо выгружать всё
    $lastDate = null;
    Tools::logger('Первоначально выгружаем все заказы' . "\n", 'orders-info');
}
$data = $export_orders->fetch($lastDate);
// Массив данных разбит на пакеты - не более 50 записей в каждом пакете
// Пройдём по всему массиву клиентов и отправим каждый пакет
if(!is_null($data) && is_array($data)) {
    foreach($data as $pack) {
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
                    } elseif($response2->isSuccessful() && 460 === $response2->getStatusCode()) {
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
            } elseif($response1->isSuccessful() && 460 === $response1->getStatusCode()) {
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
    echo 'Выгружать нечего';
}
