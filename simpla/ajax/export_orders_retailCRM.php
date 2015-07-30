<?php

require_once($_SERVER['DOCUMENT_ROOT'] . '/api/Simpla.php');

class ExportCustomersRetailCRM extends Simpla {	
  private $site = 'simpla-test-local'; // Из настроек Demo RetailCRM https://demo.retailcrm.ru/admin/sites
	private $customers = array(
	  'externalID' =>         'simpla-test-local'	  
	);
			
	public function fetch()	{

		if(!$this->managers->access('export')) return false;
			
		
    $columns_names["category"]="Ответ сервера";
		return $columns_names;

	}
	
}

class ExportOrdersRetailCRM extends Simpla {	
	private $columns_names = array(
			'category' => 'test',
			);
	public function fetch()	{

		if(!$this->managers->access('export'))
			return false;
    $columns_names["category"]="Ответ сервера";
		$countOrders = $this->orders->count_orders();
		$orders = array();
		$items = array();
    for($i = 1; $i <= ceil($countOrders/50); $i++) { // Если заказов много, то разбиваем отправку по пакетам по 50 штук
      foreach($this->orders->get_orders(array('page' => $i, 'limit'=> 50)) as $p) {
        //$orders[$p->id] = (array)$p;
        // Нужна обработка имени клиента - иногда он регистрируется, иногда нет
        $items = []; // Очищаем массив для новой итерации
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
        //print_r($item);
        $orders[] = array(
          'externalId' => $p->id,
          'createdAt' => date("Y-m-d H:i:s", strtotime($p->date)),
          'discount' => $p->discount,
          'phone' => $p->phone,
          'email' => $p->email,
          'customerComment' => $p->comment,
          'managerComment' => $p->note,
          'contragentType' => 'contragentType', // Доступны только физ. лица
          'legalName' => $p->name, // Имя формируется в свободной форме
          'legalAddress' => $p->address,
          'customerId' => $p->user_id,
          'paymentType' => $p->payment_method_id,
          'paymentStatus' => $p->paid,
          'status' => $p->status,
          'items' => $items, // Массив товаров из заказа
          'delivery' => array(
            'code' => $p->delivery_id,
            'cost' => $p->delivery_price,
            'address' => $p->address          
          )
        );
      }
    }
    print_r($orders);
		
		return $columns_names;

	}
	
	private function getPageOrders($page) {
	  
	}
	
}

// Требуется пройтись по всем заказам, собрать из них необходимые данные.
// После формирования исчерпывающего набора данных подготовить к отправке список задействованных покупателей (пакетно по 50 штук)
// API RetailCRM /api/customers/upload
// Затем тоже самое по задействованным в заказах товарам
// В последнюю очередь выгрузить данные по самим заказам (пакетно по 50 штук)
// API /api/orders/upload

$customers = array();

$export_orders = new ExportOrdersRetailCRM();
$data = $export_orders->fetch();
//$data = array('countOrders' => $export_orders.countOrders);
echo json_encode($data);
/*if($data) {
	header("Content-type: application/json; charset=utf-8");
	header("Cache-Control: must-revalidate");
	header("Pragma: no-cache");
	header("Expires: -1");
	$json = json_encode($data);
	//return $json;
	echo $json;
}*/
