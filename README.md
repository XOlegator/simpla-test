# Simpla CMS <-> RetailCRM Integration

- **Batch export** of customers and orders from Simpla CMS to RetailCRM (via Simpla CMS admin panel or server console, e.g., via cron).
- **ICML generation** ([RetailCRM ICML documentation](https://www.retailcrm.ru/docs/Developers/ICML)) — an XML file containing all products and their properties in a specific structure. Generated via Simpla CMS admin panel or server console.
- **Real-time syncing** of new user registrations from Simpla CMS to RetailCRM.
- **Instant updates** to user data when modified by the customer (via account dashboard) or by an admin in Simpla CMS.
- **Automatic sending** of new orders from Simpla CMS to RetailCRM.
- **Instant updates** to order data when modified by the customer (via account dashboard) or by an admin in Simpla CMS.
- **Retrieving updated order data** from RetailCRM to Simpla CMS (via RetailCRM triggers), including orders added directly in RetailCRM.

---  

# Интеграция Simpla CMS <-> RetailCRM

* Пакетная выгрузка клиентов и заказов из интернет-магазина Simpla CMS в RetailCRM (через админку Simpla CMS или консоль сервера - например по cron).
* Формирование ICML (https://www.retailcrm.ru/docs/Developers/ICML) документа для RetailCRM (все товары и их свойства в XML виде определённой структуры) - также через админку Simpla CMS либо через консоль сервера.
* Мгновенная отправка данных из Simpla CMS в RetailCRM о регистрации нового пользователя.
* Мгновенная отправка новых данных пользователя при изменении самим пользователем в личном кабинете, либо администратором SimplaCMS в админке.
* Мгновенная отправка данных из Simpla CMS в RetailCRM о новых заказах.
* Мгновенная отправка новых данных по заказу при изменении самим пользователем в личном кабинете, либо администратором SimplaCMS в админке.
* Получение в Simpla CMS изменённых в RetailCRM данных по заказу (через функционал триггеров RetailCRM), а также данных о добавленных в RetailCRM заказах.

Скрипты используют v5 версию API RetailCRM (https://www.retailcrm.ru/docs/Developers/ApiVersion5). Проверено на работе Simpla CMS версии 2.3.7. Для полной настройки и задействования всех возможностей из описания выше, нужно:
* ssh доступ к серверу (для установки библиотеки RetailCRM через Composer) либо ftp доступ (для ручного копирования файлов)
* доступ к магазину Simpla CMS через HTTPS (требование API RetailCRM). Иначе не будет работать отправка данных в RetailCRM
* PHP версии 5.4 или новее
* параметр allow_url_fopen со значением on
* доступ к настройке заданий cron (иначе не будет доступна периодическая автоматическая отправка ICML файла и пакетная выгрузка заказов и клиентов)

## Установка общих зависимостей ##
1. Установить Simpla CMS (проверено на версии 2.3.7)
2. Зарегистрировать собственный аккаунт RetailCRM (например, по кнопке "Получить демо-версию" по ссылке https://www.retailcrm.ru/).
3. Установить официальный клиент для работы с API RetailCRM (https://github.com/retailcrm/api-client-php) по рекомендуемой процедуре через composer. Скрипты в результате будут расположены в каталоге */vendor/retailcrm/api-client-php/lib/RetailCrm/*. Или взять из релиза этого проекта готовый архив с каталогом vendor.
Пример, как можно установить composer и библиотеку API RetailCRM в консоли сервера Ubuntu:

a) Установка минимального набора необходимых пакетов PHP:
```shell
sudo apt-get install php5.6-cli php5.6-curl
```

b) Установка утилиты composer:
```shell
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'dac665fdc30fdd8ec78b38b9800061b4150413ff2e3b6f88543c636f7cd84f6db9189d43a81e5503cda447da73c7e5b6') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }"
php composer-setup.php --2.2
mv ./composer.phar ./composer
```

c) Установка библиотеки API RetailCRM:
```shell
/usr/bin/php5.6 ./composer update
```

4. Создать в корне web-сервера иерархию каталогов:  
/integration  
|----/log  
|----/icml  
Каталоги log и icml должны быть доступны для записи криптам php. В каталоге /integration/log/ будут располагаться лог-файлы по работе скриптов экспорта и импорта. В каталоге /integration/icml/ будет записываться ICML файл.
5. Скопировать в каталог /integration файлы config.php, icml.php.
6. В файле config.php прописать собственные значения настроек в соответствии с комментариями. API ключ получить в RetailCRM - [Администрирование / Интеграция / Ключи доступа к API] и выбрать нужный магазин.
7. Дать разрешения необходимым методам API - [Администрирование / Интеграция / Ключи доступа к API] - в поле "Разрешенные методы API" выставить нужные галочки (если не уверены, выставляйте все).

### Установка для пакетной выгрузки заказов и товаров ###
1. Скопировать два скрипта *export_orders_retailCRM.php* и *export_to_ICML_retailCRM.php* в каталог web-сервера /simpla/ajax/
2. ОПЦИОНАЛЬНО. Заменить родной файл Simpla CMS /simpla/design/html/export.tpl на его модифицированный вариант - только если требуется запускать представленные скрипты экспорта вручную из административной панели сайта интернет-магазина. Новые кнопки появятся в админке: [Автоматизация / Экспорт].
![Новые кнопки экспорта](https://raw.githubusercontent.com/wiki/XOlegator/simpla-test/export_buttons.png)  
3. В настройках RetaiCRM прописать путь к ICML-файлу - [Администрирование / Магазины / Магазины], выбрать нужный магазин и на вкладке Каталог указать URL (https://sitename/integration/icml/icml.xml).

### Установка для мгновенной выгрузки данных по заказам и клиентам в RetailCRM ###
Внимание! Этот пункт установки требует модификации системных файлов Simpla CMS. При последующем обновлении Simpla CMS эти действия, скорее всего, потребуется повторить! Нет возможности автоматизировать этот процесс. Модификацию скриптов придётся выполнить вручную. Изменённые скрипты закачать на сервер, например, через FTP.  
1) Скопировать в каталог /api файл Retail.php - это класс со всеми методами, необходимыми для мгновеного обмена данными между Simpla CMS и RetailCRM.  
2) В файле /api/Simpla.php дополнить массив $classes элементом: 'retail' => 'Retail'. Например, в базовой установке Simpla CMS этот массив должен стать таким:
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
3) Для отправки в RetailCRM группы пользователей, в которую назначен пользователь в Simpla CMS, нужно создать пользовательское поле "Группа пользователей" (код group) для таблицы Клиенты в RetailCRM: Администрирование / Настройки / Пользовательские поля.
![Группа пользователей](https://raw.githubusercontent.com/wiki/XOlegator/simpla-test/user_group.png)  
4) Для мгновенной отправки данных о регистрации клиента в Simpla CMS - в файл /view/RegisterView.php после строк
```php
            elseif($user_id = $this->users->add_user(array('name'=>$name, 'email'=>$email, 'password'=>$password, 'enabled'=>$default_status, 'last_ip'=>$_SERVER['REMOTE_ADDR'])))
            {
```
вставить строки:
```php
                // Отсылаем данные о зарегистрировавшемся пользователе в RetailCRM
                if ($this->retail->isOnlineIntegration() && $arUserData = $this->retail->getUserRetailData($user_id)) {
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
                if ($this->retail->isOnlineIntegration() && $arUserData = $this->retail->getUserRetailData($user_id)) {
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
                if ($this->retail->isOnlineIntegration() && $arUserData = $this->retail->getUserRetailData($user->id)) {
                    $this->retail->request('customersEdit', $arUserData);
                }
```
7) Для мгновенной отправки данных о новом клиентском заказе в Simpla CMS - в файл /view/CartView.php после строк
```php
            // Отправляем письмо администратору
            $this->notify->email_order_admin($order->id);
```
вставить строки:
```php
            // Отсылаем данные о новом заказе в RetailCRM
            if ($this->retail->isOnlineIntegration() && $arOrderData = $this->retail->getOrderRetailData($order_id)) {
                $this->retail->request('ordersCreate', $arOrderData);
            }
```
8) Для мгновенной отправки изменений по заказу со стороны клиентов - в файл /view/OrderView.php после строк
```php
            if($payment_method_id = $this->request->post('payment_method_id', 'integer'))
            {
                $this->orders->update_order($order->id, array('payment_method_id'=>$payment_method_id));
                $order = $this->orders->get_order((integer)$order->id);
```
вставляем строки:
```php
                // Отсылаем данные о заказе в RetailCRM
                if ($this->retail->isOnlineIntegration() && $arOrderData = $this->retail->getOrderRetailData($order->id)) {
                    $this->retail->request('ordersEdit', $arOrderData);
                }
```
9) Для мгновенной отправки заказов, созданных в админке Simpla CMS - в файл /simpla/OrderAdmin.php после строк
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
                if ($this->retail->isOnlineIntegration() && $arOrderData = $this->retail->getOrderRetailData($order->id)) {
                    if ($isNewOrder) {
                        // Отсылаем данные о новом заказе в RetailCRM
                        $this->retail->request('ordersCreate', $arOrderData);
                    } else {
                        // Отсылаем данные об изменённом заказе в RetailCRM
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
вставить строки:
```php
            if ($this->retail->isOnlineIntegration()) {
                foreach ($ids as $id) {
                    if ($arOrderData = $this->retail->getOrderRetailData($id)) {
                        // Отсылаем данные о заказе в RetailCRM
                        $this->retail->request('ordersEdit', $arOrderData);
                    }
                }
            }
```
11) Для мгновенной отсылки информации об оплате - в файл /api/Orders.php в методе update_order после строк
```php
        $this->db->query($query);
        $this->update_total_price(intval($id));
```
вставить строки:
```php
        // Проверка, изменился ли статус оплаты. По какой-то причине оплата иногда проставляется не отдельным методом pay(), а этим методом update_order()
        if ($this->retail->isOnlineIntegration() && is_array($order) && isset($order['paid']) && !isset($order['status'])) {
            // Вероятно изменился статус оплаты по заказау, отразим это в RetailCRM
            // Отсылаем данные об оплате заказа в RetailCRM
            if ($arOrderData = $this->retail->getOrderRetailData($order_id)) {
                $this->retail->request('ordersEdit', $arOrderData);
            }
        }
```
И в методе pay() после строк
```php
        $query = $this->db->placehold("UPDATE __orders SET payment_status=1, payment_date=NOW(), modified=NOW() WHERE id=? LIMIT 1", $order->id);
        $this->db->query($query);
```
вставить строки:
```php
        // Отсылаем данные об оплате заказа в RetailCRM
        if ($this->retail->isOnlineIntegration() && $arOrderData = $this->retail->getOrderRetailData($order_id)) {
            $this->retail->request('ordersEdit', $arOrderData);
        }
```

### Установка для мгновенной выгрузки данных по заказам из RetailCRM в Simpla CMS ###
1. Скопировать в каталог /api файл Retail.php (см. п. 1 из предыдущего списка действий)
2. Скопировать в каталог /integration файл import_order_retailCRM.php.
3. Добавить триггер в RetailCRM для отправки запроса при изменении заказа: Администрирование / Коммуникации / Добавить. Условие применения триггера: not changeSet.hasChangesWithSource("api"). Действие: Выполнить HTTP-запрос. Адрес: https://sitename/integration/import_order_retailCRM.php (подставить вместо sitename название собственного сайта c Simpla CMS; тут протокол может быть HTTP, хотя рекомендуется HTTPS). Метод: POST. Передавать параметры: В теле запроса (urlencode). Параметры: Параметр - id; Значение - {{ order.id }}
![Триггер на изменение заказа](https://raw.githubusercontent.com/wiki/XOlegator/simpla-test/trigger_order_change.png)

## Использование ##
При настройке мгновенного обена данными по заказам и клиентам между Simpla CMS и RetailCRM - обмен будет происходить в режиме реального времени при изменении соответствующих данных.

Запуск пакетного экспорта товаров (через ICML-файл), заказов и клиентов можно производить различными способами: автоматически и вручную.

Запуск экспорта товаров можно производить вручную через административную панель сайта (для этого понадобится войти под учётной записью с правами экспорта - учётная запись администратора обладает необходимыми правами). Перейти в раздел Автоматизация/Экспорт. Первая кнопка - это стандартный экспорт из поставки Simpla CMS. 
Вторая кнопка - добавленный экспорт клиентов и заказов напрямую на сайт RetailCRM. Если операцию выполнять впервые, то будет произведён экспорт всех существующих заказов и клиентов по этим заказам. После выгрузки данных будет сформирован файл в каталоге web-сервера /integration/log/history.log с отметкой времени последней выгрузки. Все последующие попытки экспорта будут формироваться по заказам, созданным в Simpla позже указанной метки (метка обновляется после каждой успешной выгрузки).
Третья кнопка запускает формирование ICML (https://www.retailcrm.ru/docs/Developers/ICML) файла /integration/icml/icml.xml

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
*/15 * * * * www-data   /usr/bin/php5 /<путь к HTTP серверу>/simpla/ajax/export_orders_retailCRM.php >/dev/null 2>&1
```
После перезапуска cron скрипт будет выполняться каждые 15 минут. Результат работы задания можно увидеть в системном логе /var/log/syslog Результат работы скрипта в логах в каталоге web-сервера /integration/log/ В приведённом примере скрипт запускается от имени пользователя www-data (пользователь Apache по-умолчанию в Linux дистрибутивах семейства Debian), - при необходимости изменить на текущего пользователя, от имени которого работает web-сервер.

Возможно настроить автомарический запуск скриптов после оформления каждого нового заказа. В Simpla есть возможность автоматической отправки письма на почту после оформления заказа. На стороне почтового сервера возможно настроить обработку входящих писем. При получении письма, сигнализирующего о новом заказе, запускать скрипт (некоторые почтовые сервисы позволяют это делать).

### Передача данных пользовательских полей заказа ###

Есть возможность завести в retailCRM пользовательские поля заказа и передавать туда значения из Simpla CMS. Т.к. пользовательские поля могут быть произвольными и настраиваются индивидуально на каждом проекте, то нет возможности в данной базовой интеграции разработать универсальные методы, формирующие данные. Но есть возможность реализовать эти методы самостоятельно на конкретном проекте. Для этого нужно:
1. Создать пользовательское поле для заказа в RetailCRM. Задать символьный код свойства в стиле "змеиный_регистр" ("snake case"). Для примера в этой интеграции рассматривается пользовательское свойство "order_url".
2. В файле конфигурации интеграции определить все пользовательские поля заказа в ключе "orderCustomFields" (просто массив символьных кодов пользовательских полей заказа из retailCRM).
3. В скрипте /api/RetailTrait.php создайте статический метод с соответсвующим названием. Название должно начинаться с "get", затем название пользовательского поля в "pascal case". Например, для пользовательского поля order_url создан метод "getOrderUrl". На вход метод должен принимать один параметр - идентификатор заказа в Simpla CMS. На выходе должно быть строковое или числовое значение для пользовательского поля. Пользовательские поля типа Справочник не поддерживаются.
![Пример пользовательского поля заказа](https://raw.githubusercontent.com/wiki/XOlegator/simpla-test/order_custom_fields.png)

## Обновление интеграции с v1.0 (API v4) на v2.0 (API v5) ##

1. Обновить библиотеку API RetailCRM через composer, как это рекомендуется на сайте разработчика - https://github.com/retailcrm/api-client-php Можно файл composer.json привести к виду:
```json
{
    "require": {
        "retailcrm/api-client-php": "^5.0"
    }
}
```
И выполнить в консоли сервера команду (при глобально установленном Composer):
```bash
composer update
```
2. Обновить скрипт /api/Retail.php
3. Проверить и при необходимости обновить согласно пункту "Установка для мгновенной выгрузки данных по заказам и клиентам в RetailCRM" этой инструкции.
4. Обновить скрипты /simpla/ajax/export_orders_retailCRM.php, /simpla/ajax/export_to_ICML_retailCRM.php и /integration/icml.php
5. Удалить файлы orders.php и Tools.php из каталога /integration (теперь код из них содержится в /api/Retail.php)

## Благодарности и ссылки ##
* RetailCRM - https://www.retailcrm.ru/
* Simpla CMS - http://simplacms.ru/
* Разработчик интеграции - Олег Ехлаков <subspam@mail.ru>
* Проект интеграции реализован при поддержке студии Ультравзор — http://ultravzor.com
* Комментарии к проекту можно оставять [тут](https://github.com/XOlegator/simpla-test/issues/2)
