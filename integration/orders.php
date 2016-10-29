<?php

class ExportRetailCrm extends Simpla
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }


    /**
     * Переопределяем функцию из класса Orders. В исходном варианте нет фильтра по дате создания заказа
     */
    public function count_orders($filter = array())
    {
        $created_since = '';
        if (isset($filter['created_since']) && !is_null($filter['created_since']))
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


    /**
     * Переопределяем функцию из класса Orders. В исходном варианте нет фильтра по дате создания заказа
     */
    public function get_orders($filter = array())
    {
        // По умолчанию
        $limit = 50;
        $page = 1;
        $created_since = '';
        if (isset($filter['limit']))
            $limit = max(1, intval($filter['limit']));
        if (isset($filter['page']))
            $page = max(1, intval($filter['page']));
        $sql_limit = $this->db->placehold(' LIMIT ?, ? ', ($page-1)*$limit, $limit);
        if (isset($filter['created_since']) && !is_null($filter['created_since']))
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
        foreach ($this->db->results() as $order)
            $orders[$order->id] = $order;
        return $orders;
    }


    /**
     * Функция fetch() формирует массив из всех клиентов и заказов ИМ Simpla. Если передана дата, то отбираются все созданные заказы после этого времени.
     */
    public function fetch($date)
    {
        if (!$this->managers->access('export')) return false; // Проверка прав доступа при запуске скрипта из админки Simpla

        $countOrders = $this->count_orders(array('created_since' => $date));
        Tools::logger('Количество заказов для выгрузки: ' . $countOrders, 'orders-info');
        if ($countOrders == 0) { // Заказов вообще не нашлось
            Tools::logger('Заказов для выгрузки нет', 'orders-info');
            return null;
        }
        for ($i = 1; $i <= ceil($countOrders/50); $i++) { // Если заказов много, то разбиваем отправку по пакетам по 50 штук
            $orders = []; // Чистый массив заказов для нового пакета данных
            $customers = []; // Чистый массив клиентов для нового пакета данных
            // Получаем очередные заказы
            $ordersPack = $this->get_orders(array('page' => $i, 'limit' => 50, 'created_since' => $date));
            if (!empty($ordersPack)) {
                foreach ($ordersPack as $order) {
                    if ($currentOrder = $this->retail->getOrderRetailData($order->id)) {
                        $orders[] = $currentOrder;
                        // Получим данные по клиенту заказа
                        $user_id = (int) $order->user_id;
                        if ($user_id != 0) { // Код клиента в Simpla <0> зарезервирован для экспресс клиентов (без регистрации)
                            $objCurrentCustomer = $this->users->get_user($user_id);
                            $customerData = array(
                                'externalId'     => $user_id,
                                'email'          => $objCurrentCustomer->email,
                                'phones'         => [ // Попробуем телефон извлечь из заказа, т.к. в таблице клиентов телефон не хранится
                                    'number' => $currentOrder["phone"]
                                ],
                                'address'        => [ // Адрес также не хранится в таблице клиентов, возьмём из заказа
                                    'text'   => $currentOrder["delivery"]["address"]['text']
                                ],
                                'createdAt'      => $objCurrentCustomer->created,
                                'contragent'     => [
                                    'contragentType' => 'individual' // Доступны только физ. лица
                                ]
                            );
                            // Добавляем данные по имени и фамилии клинта заказа
                            if (isset($objCurrentCustomer->name) && !empty($objCurrentCustomer->name)) {
                                $arCustomerName = explode(' ', $objCurrentCustomer->name);
                                if (!empty($arCustomerName[0])) {
                                    $customerData['firstName'] = $arCustomerName[0];
                                }
                                if (!empty($arCustomerName[1])) {
                                    $customerData['lastName'] = $arCustomerName[1];
                                }
                            }
                            $customers[] = $customerData;
                        }
                    }
                } // Прошлись по очередным заказам (не более 50)
                // Записываем результат в массив
                $result[] = array('customers' => $customers, 'orders' => $orders);
                Tools::logger('Сформирован ' . $i . '-й пакет данных (клиенты и заказы) для первоначальной загрузки', 'orders-info');
            } else {
                Tools::logger('Simpla::Orders::get_orders: ' . 'Заказы не найдены', 'orders-info');
                return false;
            }
        }
        Tools::logger('Сформирован весь набор данных (клиенты и заказы) для загрузки', 'orders-info');
        return $result;
    }
}
