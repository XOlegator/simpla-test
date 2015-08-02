<?php
  define("KEYRETAIL", "QItLioI0x8g7h2CWqkNNC6Ifg7jeD8dd");
  require_once($_SERVER['DOCUMENT_ROOT'] . '/api/Simpla.php');
  // Подключим библиотеку API RetailCRM
  require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/retailcrm/api-client-php/lib/RetailCrm/Exception/CurlException.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/retailcrm/api-client-php/lib/RetailCrm/Exception/InvalidJsonException.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/retailcrm/api-client-php/lib/RetailCrm/Http/Client.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/retailcrm/api-client-php/lib/RetailCrm/Response/ApiResponse.php');
  require_once($_SERVER['DOCUMENT_ROOT'] . '/vendor/retailcrm/api-client-php/lib/RetailCrm/ApiClient.php');
  
  class ExportOrdersRetailCRM extends Simpla {
    public function fetch() {
      if(!$this->managers->access('export')) return false;
      $countOrders = $this->orders->count_orders();
      $orders = array();
      $customers = array();
      $items = array();
      for($i = 1; $i <= ceil($countOrders/50); $i++) { // Если заказов много, то разбиваем отправку по пакетам по 50 штук
        foreach($this->orders->get_orders(array('page' => $i, 'limit'=> 50)) as $p) {
          $items = []; // Очищаем массив товаров для новой итерации
          // Собираем массив товаров из заказа
          if(null !== $this->orders->get_purchases(array('order_id' => $p->id))) {
            foreach ($this->orders->get_purchases(array('order_id' => $p->id)) as $item) {
              $items[] = array(
                "initialPrice" => $item->price,
                "productId"    => $item->id,
                "productName"  => $item->product_name,
                "quantity"     => $item->amount
              );
            }
          }
          // Конвертируем виды доставок
          switch ($p->delivery_id) {
          case 1:
            $delivery = 'courier';
            break;
          case 2:
            $delivery = 'self-delivery';
            break;
          default:
            $delivery = 'self-delivery';
          }
          // Конвертируем виды оплат
          switch ($p->payment_method_id) {
          case 1:
            $payment = 'bank-transfer';
            break;
          case 2:
            $payment = 'web';
            break;
          case 3:
            $payment = 'robokassa';
            break;
          case 4:
            $payment = 'paypal';
            break;
          case 5:
            $payment = 'interkassa';
            break;
          case 6:
            $payment = 'liqpay';
            break;
          case 7:
            $payment = 'pay2pay';
            break;
          case 8:
            $payment = 'qiwi';
            break;
          case 9:
            $payment = 'yad';
            break;
          case 10:
            $payment = 'bank-card';
            break;
          case 11:
            $payment = 'terminal';
            break;
          case 12:
            $payment = 'mobile';
            break;
          default:
            $payment = 'bank-transfer';
          }
          // Конвертируем статусы оплат
          switch ($p->paid) {
          case 0:
            $paymentStatus = 'not-paid';
            break;
          case 1:
            $paymentStatus = 'paid';
            break;
          default:
            $paymentStatus = 'not-paid';
          }
          // Конвертируем статусы заказов
          switch ($p->status) {
          case 0:
            $orderStatus = 'new';
            break;
          case 1:
            $orderStatus = '123456';
            break;
          case 2:
            $orderStatus = 'complete';
            break;
          case 3:
            $orderStatus = 'cancel-other';
            break;
          default:
            $orderStatus = 'new';
          }
          $orders[] = array(
            'externalId'      => $p->id,
            'createdAt'       => date("Y-m-d H:i:s", strtotime($p->date)),
            'discount'        => $p->discount,
            'phone'           => $p->phone,
            'email'           => $p->email,
            'customerComment' => $p->comment,
            'managerComment'  => $p->note,
            'contragentType'  => 'individual', // Доступны только физ. лица
            'legalName'       => $p->name, // Имя в Simpla формируется в свободной форме
            'legalAddress'    => $p->address,
            'customerId'      => (intval($p->user_id) == 0) ? 'order' . $p->id : $p->user_id, // Код клиента (по данным Simpla)
            'paymentType'     => $payment,
            'paymentStatus'   => $paymentStatus,
            'status'          => $orderStatus,
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
      }      
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
  $data = $export_orders->fetch(); // Получили массив из всех заказов и их клиентов
  
  $clientRetailCRM = new \RetailCrm\ApiClient('https://demo.retailcrm.ru', KEYRETAIL, 'simpla-test-local');
  // Массив данных разбит на пакеты - не более 50 записей в каждом пакете
  // Пройдём по всему массиву клиентов и отправим каждый пакет
  foreach($data as $pack) {
    try {
      $response1 = $clientRetailCRM->customersUpload($pack['customers'], 'simpla-test-local');
    } catch (\RetailCrm\Exception\CurlException $e) {
      echo "Сетевые проблемы. Ошибка подключения к retailCRM: " . $e->getMessage();
    }
    // Получаем подробности обработки клиентов 
    if ($response1->isSuccessful() && 201 === $response1->getStatusCode()) {
      $status = 'Все клиенты успешно выгружены в RetaiCRM.' . '<br>';
      // Переходим к выгрузке заказов
      try {
        $response2 = $clientRetailCRM->ordersUpload($pack['orders'], 'simpla-test-local');
      } catch (\RetailCrm\Exception\CurlException $e) {
        echo "Сетевые проблемы. Ошибка подключения к retailCRM: " . $e->getMessage();
      }
      // Получаем подробности обработки заказов
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
    
        // получить детализацию ошибок
        if (isset($response2['errors'])) {
            print_r($response2['errors']);
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
  
      // получить детализацию ошибок
      /*if (isset($response1['errorsArray'])) {
          print_r($response1['errorsArray']);
      }*/
    }
  }
