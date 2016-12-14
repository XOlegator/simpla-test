<?php

class ExportICMLRetailCRM extends Simpla
{
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    
    /**
      * Метод формирует XML вставку по каталогам товаров
      */
    private function makeCategories($domObject, $domElementCategories)
    {
        $categories = $this->categories->get_categories();
        if (!empty($categories)) {
            foreach ($categories as $index => $category) {
                $categoryXml = $domElementCategories->appendChild($domObject->createElement('category', $category->name));
    
                $categoryXml->setAttribute('id', $category->id);
    
                if ($category->parent_id > 0) {
                    $categoryXml->setAttribute('parentId', $category->parent_id);
                }
            }
            Tools::logger('Сформирован список категорий товаров', 'icml');
            return $categoryXml;
        } else { // Ниодной категории не зарегистрировано
            Tools::logger('Нет ни одной категории товаров', 'icml');
            return null;
        }
    }


    /**
      * Метод формирует XML вставку по товарам
      */
    private function makeOffers($domObject, $domElementOffers)
    {
        $simpla = new Simpla();
        $products = $this->products->get_products();
        if (!empty($products)) {
            //$items = '';
            foreach ($products as $product) {
                $variants = $this->variants->get_variants(array('product_id' => $product->id));
                if (!empty($variants)) {
                    foreach ($variants as $variant) {
                        $currentOffer = $domObject->createElement('offer');
                        $currentOffer->setAttribute('id', $variant->id); // Идентификатор торгового предложения
                        $currentOffer->setAttribute('productId', $product->id); // Идентификатор товара
                        $currentOffer->setAttribute('quantity', $variant->stock);
                        $offerXml = $domElementOffers->appendChild($currentOffer);

                        // Сформируем URL товара
                        $url = $this->config['urlSimpla'] . '/products/' . $product->url;
                        $currentOffer->appendChild($domObject->createElement('url', $url));

                        // Цена товара
                        $currentOffer->appendChild($domObject->createElement('price', $variant->price));

                        // Получим первый каталог товара
                        $currentCategory = $this->categories->get_product_categories(array('product_id' => $product->id));
                        if (!empty($currentCategory)) {
                            $categoryId = $currentCategory[0]->category_id;
                            $currentOffer->appendChild($domObject->createElement('categoryId', $categoryId));
                        }

                        // Получим путь к первой картинке товара
                        $images = $this->products->get_images(array('product_id' => $product->id));
                        if (!empty($images)) {
                            $image = $simpla->design->resize_modifier($images[0]->filename, 200, 200);
                            $is_console = PHP_SAPI == 'cli' || (!isset($_SERVER['DOCUMENT_ROOT']) && !isset($_SERVER['REQUEST_URI']));
                            if ($is_console) {
                                $image = str_replace('http://', $this->config['urlSimpla'], $image);
                            }
                            $currentOffer->appendChild($domObject->createElement('picture', $image));
                        }

                        // Название торгового предложения. В Simpla это название товара + название варианта
                        if ($variant->name != '') {
                            $name = $product->name . ' / ' . $variant->name;
                        } else {
                            $name = $product->name;
                        }
                        $currentOffer->appendChild($domObject->createElement('name', $name));

                        // Выводим название товара
                        $currentOffer->appendChild($domObject->createElement('productName', $product->name));

                        // Определим, активен ли данный товар
                        if (!$product->visible) {
                            $currentOffer->appendChild($domObject->createElement('productActivity', 0));; // Если товар активен, то ничего указывать не надо   
                        }

                        // Соберём все свойства товаров
                        $options = $this->features->get_options(array('product_id' => $product->id));
                        if (!empty($options)) {
                            foreach ($options as $option) {
                                $nameOption = ''; // В БД не задействован механизм контроля целостности данных по внешним ключам, поэтому название опции может быть удалено
                                $features = $this->features->get_features(array('catalog_id' => $categoryId, 'id' => $option->feature_id));
                                if (!empty($features)) {
                                    $nameOption = $features[0]->name;
                                }
                                $optionCode = (isset($this->config['propCodes'][$option->feature_id])) ? $this->config['propCodes'][$option->feature_id] : $option->feature_id;
                                $valueOption = $option->value;
                                if (!empty($optionCode) && !empty($valueOption)) {
                                    $currentParam = $domObject->createElement('param', $valueOption);
                                    $currentParam->setAttribute('name', $nameOption);
                                    $currentParam->setAttribute('code', $optionCode);
                                    $currentOffer->appendChild($currentParam);
                                }
                            }

                            // Отдельно добавим некоторые параметры со специальной обработкой в RetailCRM
                            // description
                            $description = html_entity_decode($this->getProductDescription($product));
                            if (!empty($description)) {
                                $currentParam = $domObject->createElement('param', $description);
                                $currentParam->setAttribute('name', 'Описание торгового предложения');
                                $currentParam->setAttribute('code', 'description');
                                $currentOffer->appendChild($currentParam);
                            }
                            // article
                            $article = $this->getVariantArticle($variant);
                            if (!empty($article)) {
                                $currentParam = $domObject->createElement('param', $article);
                                $currentParam->setAttribute('name', 'Артикул торгового предложения');
                                $currentParam->setAttribute('code', 'article');
                                $currentOffer->appendChild($currentParam);
                            }
                        }

                        // Добавим производителя
                        $currentOffer->appendChild($domObject->createElement('vendor', $product->brand));
                    }
                }
            }

            Tools::logger('Сформирован список товаров', 'icml');
            return $offerXml;
        } else { // Ниодного товара не зарегистрировано
            Tools::logger('Нет ни одного товара', 'icml');
            return null;
        }
    }


    private function getSitename()
    {
        return (!is_null($this->settings->site_name)) ? $this->settings->site_name : '';
    }


    private function getCompanyname()
    {
        return (!is_null($this->settings->company_name) ? $this->settings->company_name : $this->getSitename());
    }


    /**
     * Метод возвращает описание торгового предложения
     */
    private function getProductDescription($product)
    {
        return $product->meta_description;
    }


    /**
     * Метод возвращает артикул торгового предложения
     */
    private function getVariantArticle($variant)
    {
        return $variant->sku;
    }


    /**
     * Метод генерирует и возвращает DOM-объект
     */
    public function generate()
    {
        if (!$this->managers->access('export')) return false; // Проверка прав доступа при запуске скрипта из админки Simpla

        // Создаём шаблон XML-документа
        $domObject = new DOMDocument('1.0', 'utf-8');
        $domElementCatalog = $domObject->createElement('yml_catalog');
        $domAttribute = $domObject->createAttribute('date');
        $domAttribute->value = date('Y-m-d H:i:s');
        $domElementCatalog->appendChild($domAttribute);
        $domObject->appendChild($domElementCatalog);
        // Добавляем элемент shop
        $domElementShop = $domObject->createElement('shop');
        $domElementCatalog->appendChild($domElementShop);
        //$domElementName = $domObject->createElement('name', $this->getSitename());
        $domElementName = $domObject->createElement('name');
        $domElementShop->appendChild($domElementName);
        $domElementName->appendChild($domObject->createTextNode($this->getSitename()));
        $domElementCompany = $domObject->createElement('company');
        $domElementShop->appendChild($domElementCompany);
        $domElementCompany->appendChild($domObject->createTextNode($this->getCompanyname()));
        $domElementCategories = $domObject->createElement('categories');
        $domElementShop->appendChild($domElementCategories);
        $domElementOffers = $domObject->createElement('offers');
        $domElementShop->appendChild($domElementOffers);

        // Формируем блок категорий товаров
        $this->makeCategories($domObject, $domElementCategories);

        //Формируем блок товаров
        $this->makeOffers($domObject, $domElementOffers);

        return $domObject;
    }
}
