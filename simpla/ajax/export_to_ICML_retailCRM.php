<?php
$path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // Определяем директорию скрипта (полезно для запуска из cron'а)
chdir($path_parts['dirname']); // Задаём директорию выполнение скрипта

require_once ('../../api/Simpla.php');
// Подключаем описание класса для формирования ICML-файла
require_once ('../../integration/icml.php');
// Подключаем общие инструменты
require_once ('../../integration/Tools.php');
$config = Tools::config('../../integration/config.php');

$simpla = new Simpla();
print_r("Check access level");
if (!$simpla->managers->access('export')) return false; // Проверка прав доступа при запуске скрипта из админки Simpla

$export = new ExportICMLRetailCRM($config);
$domObject = $export->generate();
// Приводим объект к читабельному виду XML
$domObject->preserveWhiteSpace = false;
$domObject->formatOutput = true;
$domObject->saveXML();
if ($domObject->save("../../integration/icml/icml.xml")) {
    echo "Создан файл /integration/icml/icml.xml";
    Tools::logger('Сгенерирован новый ICML-файл: /integration/icml/icml.xml' . "\n", 'icml');
} else {
    echo 'Файл XML не создан';
    Tools::logger('Не удалось сохранить ICML-файл: /integration/icml/icml.xml' . "\n", 'icml');
}
