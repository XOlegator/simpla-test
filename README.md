# Интеграция Simpla <-> RetailCRM #
* Пакетная выгрузка клиентов и заказов из интернет-магазина Simpla в RetailCRM (через админку SimplaCMS или консоль сервера - например по cron).
* Формирование ICML (https://www.retailcrm.ru/docs/Developers/ICML) документа для RetailCRM (все товары и их свойства в XML виде определённой структуры) - также через админку SimplaCMS либо через консоль сервера.
* Мгновенная отправка данных из SimplaCMS в RetailCRM о регистрации нового пользователя.
* Мгновенная отправка новых данных пользователя при изменении самим пользователем в личном кабинете, либо администратором SimplaCMS в админке.
* Мгновенная отправка данных из SimplaCMS в RetailCRM о новых заказах.
* Мгновенная отправка новых данных по заказу при изменении самим пользователем в личном кабинете, либо администратором SimplaCMS в админке.
* Получение в SimplaCMS изменённых в RetailCRM данных по заказу (через функционал триггеров RetailCRM), а также данных о добавленных в RetailCRM заказах.

Скрипты используют v4 версию API RetailCRM (http://www.retailcrm.ru/docs/Developers/ApiVersion4). Проверено на работе Simpla версии 2.3.7. Для полной настройки и задействования всех возможностей из описания выше, нужно:
* ssh доступ к серверу (для установки библиотеки RetailCRM через Composer) либо ftp доступ (для ручного копирования файлов)
* доступ к магазину SimplaCMS через HTTPS (требование API RetailCRM). Иначе не будет работать отправка данных в RetailCRM
* PHP версии 5.4 или новее
* параметр allow_url_fopen со значением on
* доступ к настройке заданий cron (иначе не будет доступна периодическая автоматическая отправка ICML файла и пакетная выгрузка заказов и клиентов)

## Установка общих зависимостей ##
1. Установить Simpla (проверено на версии 2.3.7)
2. Зарегистрировать собственный аккаунт RetailCRM (например, по кнопке "Получить демо-версию" по ссылке https://www.retailcrm.ru/).
3. Установить официальный клиент для работы с API RetailCRM (https://github.com/retailcrm/api-client-php). Скрипты будут расположены в каталоге */vendor/retailcrm/api-client-php/lib/RetailCrm/*
4. Создать в корне web-сервера иерархию каталогов:
/integration
|----/log
|----/icml
Каталоги log и icml должны быть доступны для записи криптам php. В каталоге /integration/log/ будут располагаться лог-файлы по работе скриптов экспорта и импорта. В каталоге /integration/icml/ будет записываться ICML файл.
5. Скопировать в каталог /integration файлы config.php, icml.php, orders.php, Tools.php
6. В файле config.php прописать собственные значения настроек в соответствии с комментариями. API ключ получить в RetailCRM - [Администрирование / Интеграция / Ключи доступа к API] и выбрать нужный магазин.

### Установка для пакетной выгрузки заказов и товаров ###
1. Скопировать два скрипта *export_orders_retailCRM.php* и *export_to_ICML_retailCRM.php* в каталог web-сервера /simpla/ajax/
2. ОПЦИОНАЛЬНО. Заменить родной файл Simpla /simpla/design/html/export.tpl на его модифицированный вариант - только если требуется запускать представленные скрипты экспорта вручную из административной панели сайта интернет-магазина. Новые кнопки появятся в админке: [Автоматизация / Экспорт].
3. В настройках RetaiCRM прописать путь к ICML-файлу - [Администрирование / Магазины / Магазины], выбрать нужный магазин и на вкладке Каталог указать URL.

### Установка для мгновенной выгрузки данных по заказам и клиентам в RetailCRM ###
Внимание! Этот пункт установки требует модификации системных файлов SimplaCMS. При последующем обновлении SimplaCMS эти действия, скорее всего, потребуется повторить! Нет возможности автоматизировать этот процесс. Модификацию скриптов придётся выполнить вручную. Изменённые скрипты закачать на сервер, например, через FTP.
1) Скопировать в каталог /api файл Retail.php - это класс со всеми методами, необходимыми для мгновеного обмена данными между SimplaCMS и RetailCRM.
2) В файле /api/Simpla.php дополнить массив $classes элементом: 'retail' => 'Retail'. Например, в базовой становке Simpla этот массив должен стать таким:
```php
    // Свойства - Классы API
    private $classes = array(
        'config'     => 'Config',
        'request'    => 'Request',
        'db'         => 'Database',
        'settings'   => 'Settings',
        'design'     => 'Design',
        'products'   => 'Products',
        'variants'   => 'Variants',
        'categories' => 'Categories',
        'brands'     => 'Brands',
        'features'   => 'Features',
        'money'      => 'Money',
        'pages'      => 'Pages',
        'blog'       => 'Blog',
        'cart'       => 'Cart',
        'image'      => 'Image',
        'delivery'   => 'Delivery',
        'payment'    => 'Payment',
        'orders'     => 'Orders',
        'users'      => 'Users',
        'coupons'    => 'Coupons',
        'comments'   => 'Comments',
        'feedbacks'  => 'Feedbacks',
        'notify'     => 'Notify',
        'managers'   => 'Managers',
        'retail'     => 'Retail'
    );
```
3) Для отправки в RetailCRM группы пользователей, в которую назначен пользователь в SimplaCMS, нужно создать пользовательское поле "Группа пользователей" (код group) для таблицы Клиенты в RetailCRM: Администрирование / Настройки / Пользовательские поля.
4) Для мгновенной отправки данных о регистрации клиента в SimplaCMS - в файл /view/RegisterView.php после строк
```php
            elseif($user_id = $this->users->add_user(array('name'=>$name, 'email'=>$email, 'password'=>$password, 'enabled'=>$default_status, 'last_ip'=>$_SERVER['REMOTE_ADDR'])))
            {
```
вставить строки:
```php
                if ($arUserData = $this->retail->getUserRetailData($user_id)) {
                    $this->retail->request('customersCreate', $arUserData);
                }
```
5) Для мгновенной отправки изменённых данных о пользователе сразу после изменения им же в собственном личном кабинете - в файл /view/UserView.php после строк
```php
            elseif($user_id = $this->users->update_user($this->user->id, array('name'=>$name, 'email'=>$email)))
            {
                $this->user = $this->users->get_user(intval($user_id));
                $this->design->assign('name', $this->user->name);
                $this->design->assign('user', $this->user);
                $this->design->assign('email', $this->user->email);
```
Вставить строки:
```php
                // Отсылаем данные о пользователе в RetailCRM
                if ($arUserData = $this->retail->getUserRetailData($user_id)) {
                    $this->retail->request('customersEdit', $arUserData);
                }
```
6) Для мгновенной отправки изменённых данных о пользователе сразу после изменения в админке - в файл /simpla/UserAdmin.php после строк
```php
                $user->id = $this->users->update_user($user->id, $user);
                $this->design->assign('message_success', 'updated');
                $user = $this->users->get_user(intval($user->id));
```
 вставить строки:
```php
                 // Отсылаем данные о пользователе в RetailCRM
                if ($arUserData = $this->retail->getUserRetailData($user->id)) {
                    $this->retail->request('customersEdit', $arUserData);
                }
```
7) Для мгновенной отправки данных о новом клиентском заказе в SimplaCMS - в файл /view/CartView.php после строк
```php
            // Отправляем письмо администратору
            $this->notify->email_order_admin($order->id);
```
вставить строки:
```php
            // Отсылаем данные о новом заказе в RetailCRM
            if ($arOrderData = $this->retail->getOrderRetailData($order_id)) {
                $this->retail->request('ordersCreate', $arOrderData);
            }
```
8) Для мгновенной отправки изменений по заказу со стороны клиентов - в файл /simpla/OrderView.php после строк
```php
            if($payment_method_id = $this->request->post('payment_method_id', 'integer'))
            {
                $this->orders->update_order($order->id, array('payment_method_id'=>$payment_method_id));
                $order = $this->orders->get_order((integer)$order->id);
```
вставляем строки:
```php
                // Отсылаем данные о заказе в RetailCRM
                if ($arOrderData = $this->retail->getOrderRetailData($order->id)) {
                    $this->retail->request('ordersEdit', $arOrderData);
                }
```
9) Для мгновенной отправки заказов, созданных в админке SimplaCMS - в файл /simpla/OrderAdmin.php после строк
```php
        if($this->request->method('post'))
        {
```
вствить строку:
```php
            $isNewOrder = false;
```
После строк
```php
            if(empty($order->id))
            {
                $order->id = $this->orders->add_order($order);
```
вставить строку
```php
                $isNewOrder = true;
```
После строк
```php
                // Отправляем письмо пользователю
                if($this->request->post('notify_user'))
                    $this->notify->email_order_user($order->id);
```
вставить строки:
```php
                if ($arOrderData = $this->retail->getOrderRetailData($order->id)) {
                    if ($isNewOrder) {
                        // Отсылаем данные о новом заказе в RetailCRM
                        $this->retail->request('ordersCreate', $arOrderData);
                    } else {
                        // Отсылаем данные о заказе в RetailCRM
                        $this->retail->request('ordersEdit', $arOrderData);
                    }
                }
```
10) Для мгновенной отправки изменений по заказу из админки - в файле /simpla/OrdersAdmin.php после строк (это после блока switch):
```php
                case(preg_match('/^unset_label_([0-9]+)/', $this->request->post('action'), $a) ? true : false):
                {
                    $l_id = intval($a[1]);
                    if($l_id>0)
                    foreach($ids as $id)
                    {
                        $this->orders->delete_order_labels($id, $l_id);
                    }
                    break;
                }
            }
```
вставляем строки
```php
            foreach ($ids as $id) {
                if ($arOrderData = $this->retail->getOrderRetailData($id)) {
                    // Отсылаем данные о заказе в RetailCRM
                    $this->retail->request('ordersEdit', $arOrderData);
                }
            }
```
11) Для мгновенной отсылки информации об оплате - в файл /api/Orders.php в функцию pay() после строк
```php
        $query = $this->db->placehold("UPDATE __orders SET payment_status=1, payment_date=NOW(), modified=NOW() WHERE id=? LIMIT 1", $order->id);
        $this->db->query($query);
```
 вставить строки
```php
         // Отсылаем данные об оплате заказа в RetailCRM
        if ($arOrderData = $this->retail->getOrderRetailData($order_id)) {
            $this->retail->request('ordersEdit', $arOrderData);
        }
```

### Установка для мгновенной выгрузки данных по заказам из RetailCRM в SimplaCMS ###
1. Скопировать в каталог /api файл Retail.php (см. п. 1 из предыдущего списка действий)
2. Добавить триггер в RetailCRM для отправки запроса при изменении заказа: Администрирование / Коммуникации / Добавить. Действие: Выполнить HTTP-запрос. Адрес: http://sitename/integration/import_order_retailCRM.php (подставить вместо sitename название собственного сайта c SimplaCMS). Метод: POST. Передавать параметры: В теле запроса (urlencode). Параметры: Параметр - id; Значение - {{ order.id }}

## Использование ##
При настройке мгновенного обена данными по заказам и клиентам между SimplaCMS и RetailCRM - обмен будет происходить в режиме реального времени при изменении соответсвующих данных.

Запуск пакетного экспорта товаров (через ICML-файл), заказов и клиентов можно производить различными способами: автоматически и вручную.

Запуск экспорта товаров можно производить вручную через административную панель сайта (для этого понадобится войти под учётной записью с правами экспорта - учётная запись администратора обладает необходимыми правами). Перейти в раздел Автоматизация/Экспорт. Первая кнопка - это стандартный экспорт из поставки Simpla. 
Вторая кнопка - добавленный экспорт клиентов и заказов напрямую на сайт RetailCRM. Если операцию выполнять впервые, то будет произведён экспорт всех существующих заказов и клиентов по этим заказам. После выгрузки данных будет сформирован файл в каталоге web-сервера /integration/log/history.log с отметкой времени последней выгрузки. Все последующие попытки экспорта будут формироваться по заказам, созданным в Simpla позже указанной метки (метка обновляется после каждой успешной выгрузки).
Третья кнопка запускает формирование ICML (https://www.retailcrm.ru/docs/%D0%A0%D0%B0%D0%B7%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D1%87%D0%B8%D0%BA%D0%B8/%D0%A4%D0%BE%D1%80%D0%BC%D0%B0%D1%82ICML) файла /vendor/integration/icml.xml

Запуск экспорта заказов и клиентов можно производить вручную через консоль операционной системы (если путь к php определён в окружении):
```bash
php /<путь к HTTP серверу>/simpla/ajax/export_orders_retailCRM.php 
```
Запуск формирования ICML файла
```bash
php /<путь к HTTP серверу>/simpla/ajax/export_to_ICML_retailCRM.php
```
Дополнительные параметры не требуются. Ответ будет показан в консоли. Если ответ не требуется, то вывод команд нужно перенаправить:
```bash
php /<путь к HTTP серверу>/simpla/ajax/export_orders_retailCRM.php >/dev/null 2>&1
php /<путь к HTTP серверу>/simpla/ajax/export_to_ICML_retailCRM.php >/dev/null 2>&1
```
В любом случае процесс работы скриптов будет отражён в логах в каталоге web-сервера /integration/log/

Запуск скриптов на Linux сервере можно настроить на регулярное выполнение с помощью cron. Например, для выполненния каждые 15 минут скрипта export_orders_retailCRM.php, нужно в файл /etc/crontab дописать строку:
```bash
*/15 * * * * www-data	/usr/bin/php5 /<путь к HTTP серверу>/simpla/ajax/export_orders_retailCRM.php >/dev/null 2>&1
```
После перезапуска cron скрипт будет выполняться каждые 15 минут. Результат работы задания можно увидеть в системном логе /var/log/syslog Результат работы скрипта в логах в каталоге web-сервера /integration/log/ В приведённом примере скрипт запускается от имени пользователя www-data (пользователь Apache по-умолчанию в Linux дистрибутивах семейства Debian), - при необходимости изменить на текущего пользователя, от имени которого работает web-сервер.

Возможно настроить автомарический запуск скриптов после оформления каждого нового заказа. В Simpla есть возможность автоматической отправки письма на почту после оформления заказа. На стороне почтового сервера возможно настроить обработку входящих писем. При получении письма, сигнализирующего о новом заказе, запускать скрипт (некоторые почтовые сервисы позволяют это делать).
