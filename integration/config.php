<?php

return array(
    'urlSimpla' => 'http://simpla-test.local',
    'urlRetail' => 'https://demo.retailcrm.ru',
    'keyRetail' => 'QItLioI0x8g7h2CWqkNNC6Ifg7jeD8dd',
    'logDirectory' => '../integration/log',
    /*
        Соответствие кодов типов доставок из базы Simpla и RetailCRM: array(<Код типа доставки Simpla> => <Код типа доставки RetailCRM>)
        В базе Simpla коды типов доставок числовые; в базе RetailCRM коды типов доставок строковые.
    */
    'deliveryType' => array (
        1 => 'courier',
        2 => 'self-delivery',
    ),
    /*
        Соответствие кодов типов оплат из базы Simpla и RetailCRM: array(<Код типа оплаты Simpla> => <Код типа оплаты RetailCRM>)
        В базе Simpla коды типов оплат числовые; в базе RetailCRM коды типов оплат строковые.
    */
    'paymentType' => array (
        1 => 'bank-transfer',
        2 => 'web',
        3 => 'robokassa',
        4 => 'paypal',
        5 => 'interkassa',
        6 => 'liqpay',
        7 => 'pay2pay',
        8 => 'qiwi',
        9 => 'yad',
        10 => 'bank-card',
        11 => 'terminal',
        12 => 'mobile'
    ),
    /*
        Соответствие кодов статусов оплат из базы Simpla и RetailCRM: array(<Код статуса оплаты Simpla> => <Код статуса оплаты RetailCRM>)
        В базе Simpla коды статусов оплат числовые; в базе RetailCRM коды статусов оплат строковые.
    */
    'paymentStatus' => array (
        0 => 'not-paid',
        1 => 'paid'
    ),
    /*
        Соответствие кодов статусов заказов из базы Simpla и RetailCRM: array(<Код статуса заказа Simpla> => <Код статуса заказа RetailCRM>)
        В базе Simpla коды статусов заказов числовые; в базе RetailCRM коды статусов заказов строковые.
    */
    'orderStatus' => array (
        0 => 'new',
        1 => '123456',
        2 => 'complete',
        3 => 'cancel-other'
    ),
);
