<?php

return array(
    'urlSimpla'    => 'https://sitename',              // URL интернет-магазина - необходимо для запуска скриптов из консоли сервера
    'urlRetail'    => 'https://sitename.retailcrm.ru', // URL RetailCRM, в которую будут выгружаться данные
    'siteCode'     => 'SiteCode',                      // Код интернет-магазина, прописанный в настройках RetailCRM
    'keyRetail'    => 'some-key',                      // Персональный ключ RetailCRM для доступа к API 
    'logDirectory' => '../../integration/log/',        // Каталог для логов работы скриптов выгрузок

    //Соответствие кодов типов доставок из базы Simpla и RetailCRM: array(<Код типа доставки Simpla> => <Код типа доставки RetailCRM>)
    'deliveryType' => array (
        '1' => 'courier',       // Доставка курьером
        '2' => 'self-delivery', // Самовывоз
    ),

    //Соответствие кодов типов оплат из базы Simpla и RetailCRM: array(<Код типа оплаты Simpla> => <Код типа оплаты RetailCRM>)
    'paymentType' => array (
        '1'  => 'bank-transfer', // Банковский перевод (квитанция с реквизитами)
        '2'  => 'web',           // WebMoney WMZ
        '3'  => 'robokassa',     // Робокасса
        '4'  => 'paypal',        // PayPal
        '5'  => 'interkassa',    // Интеркасса
        '6'  => 'liqpay',        // Liqpay
        '7'  => 'pay2pay',       // Pay2Pay
        '8'  => 'qiwi',          // QIWI
        '9'  => 'yad',           // Яндекс.Деньги
        '10' => 'bank-card',     // Банковская карта
        '11' => 'terminal',      // Терминал
        '12' => 'mobile'         // Мобильный телефон
    ),

    // Соответствие кодов статусов оплат из базы Simpla и RetailCRM: array(<Код статуса оплаты Simpla> => <Код статуса оплаты RetailCRM>)
    'paymentStatus' => array (
        '0' => 'not-paid', // Не оплачен
        '1' => 'paid'      // Оплачен
    ),

    // Соответствие кодов статусов заказов из базы Simpla и RetailCRM: array(<Код статуса заказа Simpla> => <Код статуса заказа RetailCRM>)
    'orderStatus' => array (
        '0' => 'new',         // Новый
        '1' => 'assembling',  // Принят
        '2' => 'complete',    // Выполнен
        '3' => 'cancel-other' // Удалён
    ),
    
    // Соответствие кодов свойств товаров из базы Simpla и RetailCRM: array(<Код свойтсва товара в Simpla> => <Код параметра в RetailCRM>)
    'propCodes' => array(
        '7' => 'weight'
    )
);
