<?php
/**
 * Настройки для интеграции RetailCRM и Simpla CMS
 */
return [
    'RETAIL_CRM' => [
        'IS_ONLINE_INTEGRATION' => true
    ],
    'urlSimpla'     => 'https://sitename', // URL интернет-магазина - необходимо для запуска скриптов из консоли сервера
    'urlRetail'     => 'https://sitename.retailcrm.ru', // URL RetailCRM, в которую будут выгружаться данные
    'siteCode'      => 'SiteCode', // Код интернет-магазина, прописанный в настройках RetailCRM
    'keyRetail'     => 'some-key', // Персональный ключ RetailCRM для доступа к API
    'logDirectory'  => '../../integration/log/', // Каталог для логов работы скриптов выгрузок

    //Соответствие кодов типов доставок из базы Simpla и RetailCRM: array(<Код типа доставки Simpla> => <Код типа доставки RetailCRM>)
    'deliveryType'  => [
        1 => 'courier', // Доставка курьером
        2 => 'self-delivery', // Самовывоз
    ],

    //Соответствие кодов типов оплат из базы Simpla и RetailCRM: array(<Код типа оплаты Simpla> => <Код типа оплаты RetailCRM>)
    'paymentType'   => [
        1  => 'bank-transfer', // Банковский перевод (квитанция с реквизитами)
        2  => 'e-money', // WebMoney WMZ
        3  => 'e-money', // Робокасса
        4  => 'e-money', // PayPal
        5  => 'e-money', // Интеркасса
        6  => 'e-money', // Liqpay
        7  => 'e-money', // Pay2Pay
        8  => 'e-money', // QIWI
        9  => 'e-money', // Яндекс.Деньги
        10 => 'bank-card', // Банковская карта
        11 => 'e-money', // Терминал
        12 => 'e-money', // Мобильный телефон
    ],

    // Соответствие кодов статусов оплат из базы Simpla и RetailCRM: array(<Код статуса оплаты Simpla> => <Код статуса оплаты RetailCRM>)
    'paymentStatus' => [
        0 => 'not-paid', // Не оплачен
        1 => 'paid', // Оплачен
    ],

    // Соответствие кодов статусов заказов из базы RetailCRM и SimplaCMS: array(<Код статуса заказа RetailCRM> => <Код статуса заказа Simpla>)
    'orderStatus'   => [
        'new'                    => 0, // (Новый)        Новый                   / Новый
        'availability-confirmed' => 0, // (Согласование) Наличие подтверждено    / Новый
        'offer-analog'           => 0, // (Согласование) Предложить замену       / Новый
        'client-confirmed'       => 0, // (Согласование) Согласовано с клиентом  / Новый
        'prepayed'               => 0, // (Согласование) Предоплата поступила    / Новый
        'assembling'             => 1, // (Комплектация) Комплектуется           / Принят
        'send-to-assembling'     => 1, // (Комплектация) Передано в комплектацию / Принят
        'assembling-complete'    => 1, // (Комплектация) Укомплектован           / Принят
        'send-to-delivery'       => 1, // (Доставка)     Передан в доставку      / Принят
        'delivering'             => 1, // (Доставка)     Доставляется            / Принят
        'redirect'               => 1, // (Доставка)     Доставка перенесена     / Принят
        'complete'               => 2, // (Выполнен)     Выполнен                / Выполнен
        'cancel-other'           => 3, // (Отменён)      Отменён                 / Удалён
        'no-call'                => 3, // (Отменён)      Недозвон                / Удалён
        'no-product'             => 3, // (Отменён)      Нет в наличии           / Удалён
        'already-buyed'          => 3, // (Отменён)      Купил в другом месте    / Удалён
        'delyvery-did-not-suit'  => 3, // (Отменён)      Не устроила доставка    / Удалён
        'prices-did-not-suit'    => 3, // (Отменён)      Не устроила цена        / Удалён
    ],

    // Соответствие кодов свойств товаров из базы Simpla и RetailCRM: array(<Код свойтсва товара в Simpla> => <Код параметра в RetailCRM>)
    'propCodes'     => [
        7 => 'weight',
    ],
];
