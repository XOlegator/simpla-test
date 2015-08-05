<?php  
  $path_parts = pathinfo($_SERVER['SCRIPT_FILENAME']); // определяем директорию скрипта (полезно для запуска из cron'а)
  chdir($path_parts['dirname']); // задаем директорию выполнение скрипта
  
  require_once('../../api/Simpla.php');
  
  class ExportICMLRetailCRM extends Simpla {
    /*
      Функция формирует XML вставку по каталогам товаров     
    */
    public function getCategories() {
      if(!$this->managers->access('export')) return false;
      
      if(!empty($this->categories->get_categories())) {
        $items = '';  
        foreach($this->categories->get_categories() as $index => $category) {
          if($category->parent_id == 0) { // Родительской директории нет
            $parentId = '';
          } else {
            $parentId = ' parentId="' . $category->parent_id . '"';
          }
          $items .= '<category id="' . $category->id . '"' . $parentId . '>' . $category->name . '</category>' . "\n";
        }
        return $items;
      } else { // Ниодной категории не зарегистрировано
        return '';
      }
  
    }
    /*
      Функция формирует XML вставку по товарам     
    */
    public function getOffers() {
      if(!$this->managers->access('export')) return false;
      
      if(!empty($this->products->get_products())) {
        $items = '';
        foreach($this->products->get_products() as $product) {
          // Определим, активент ли данный товар
          if($product->visible) {
            $productActivity = ''; // Если товар активен, то ничего указывать не надо   
          } else {
            $productActivity = '<productActivity>0</productActivity>';          
          }
          if(!empty($this->variants->get_variants(array('product_id' => $product->id)))) {
            foreach($this->variants->get_variants(array('product_id' => $product->id)) as $variant) {
              // Получим путь к первой картинке товара
              if(!empty($this->products->get_images(array('product_id' => $product->id)))) {
                $image = $_SERVER['SERVER_NAME'] . '/files/originals/' . $this->products->get_images(array('product_id' => $product->id))[0]->filename;
                $image = '<picture>' . $image . '</picture>';
              } else {
                $image = '';
              }
              // Получим первый каталог товара
              if(!empty($this->categories->get_product_categories(array('product_id' => $product->id)))) {
                $categoryId = $this->categories->get_product_categories(array('product_id' => $product->id))[0]->category_id;
                $category = '<categoryId>' . $categoryId . '</categoryId>';
              } else {
                $category = '';              
              }
              // Соберём все свойства товаров
              $param = '';
              if(!empty($this->features->get_options(array('product_id' => $product->id)))) {
                $options = $this->features->get_options(array('product_id' => $product->id));
                foreach($options as $option) {
                  $nameOption = ''; // В БД не задействован механизм контроля целостности данных по внешним ключам, поэтому название опции может быть удалено
                  if(!empty($this->features->get_features(array('catalog_id' => $categoryId, 'id' => $option->feature_id)))) {
                    $nameOption = $this->features->get_features(array('catalog_id' => $categoryId, 'id' => $option->feature_id))[0]->name;
                  }
                  $valueOption = $option->value;
                  $param .= '<param name="' . $nameOption . '" code="' . $option->feature_id . '">' . $valueOption . '</param>' . "\n";
                }
              }
              // Сформируем весь блок offer
              $items .= '<offer id="var' . $variant->id . '" productId="' . $product->id . '" quantity="' . $variant->stock . '"> 
                        <url>' . $_SERVER['SERVER_NAME'] . '/products/' . $product->url . '</url> 
                        <price>' . $variant->price . '</price>
                        ' . $category . ' 
                        ' . $image . ' 
                        <name>' . $product->name . '</name>
                        <productName>' . $product->name . '</productName>
                        ' . $productActivity . '
                        ' . $param . '
                        <param name="Цвет" code="color">' . $variant->name . '</param> 
                        <vendor>' . $product->brand . '</vendor>
                    </offer>' . "\n";
            }
          }
        }
        return $items;
      } else { // Ниодной категории не зарегистрировано
        return '';
      }
  
    }
    
    public function getSitename() {
      if(!$this->managers->access('export')) return false;
      return (!empty($this->settings->getSite_name) ? $this->settings->getSite_name : '');
    }
    
    public function getCompanyname() {
      if(!$this->managers->access('export')) return false;
      return (!empty($this->settings->getCompany_name) ? $this->settings->getCompany_name : $this->getSitename);
    }
    
  }
  
  $export = new ExportICMLRetailCRM();
  $body = '<?xml version="1.0" encoding="UTF-8"?>
            <yml_catalog date="'.date('Y-m-d H:i:s').'">
                <shop>
                    <name>' . $export->getSitename() . '</name>
                    <company>' . $export->getCompanyname() . '</company>
                    <categories>' . $export->getCategories() . '</categories>
                    <offers>' . $export->getOffers() . '</offers>
                </shop>
            </yml_catalog>
        ';
  $xml = new SimpleXMLElement($body, LIBXML_NOENT |LIBXML_NOCDATA | LIBXML_COMPACT | LIBXML_PARSEHUGE);
  $file = new DOMDocument();
  $file->preserveWhiteSpace = false;
  $file->formatOutput = true;
  $file->loadXML($xml->asXML());

  $file->saveXML();
  if($file->save("../../bitrix/catalog_export/intarocrm.xml")) {
    echo 'Создан файл /bitrix/catalog_export/intarocrm.xml";
  } else {
    echo 'Файл XML не создан';
  }
  