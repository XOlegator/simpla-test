<?php
/**
 * В этом файле реализованы уникальные методы данного проекта: чтобы не затрагивать общий для всех интеграций
 * скрипт /api/Retail.php
 *
 * @copyright 2019 Oleg Ekhlakov
 * @author    Oleg Ekhlakov <subspam@mail.ru>
 */
trait RetailTrait {
    /**
     * @param integer $orderId Идентификатор заказа в Simpla CMS
     * @return string Значение URL заказа
     */
    public static function getOrderUrl($orderId)
    {
        $result = '';
        $obSimpla = new Simpla();
        if ($order = $obSimpla->orders->get_order($orderId)) {
            if (!empty($order->url) && !empty(strval($obSimpla->config->root_url))) {
                $result = $obSimpla->config->root_url . '/order/' . $order->url;
            }
        }

        return $result;
    }
}
