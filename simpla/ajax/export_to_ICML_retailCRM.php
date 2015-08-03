<?php

  require_once($_SERVER['DOCUMENT_ROOT'] . '/api/Simpla.php');
  
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
        //print_r($this->products->get_products());
        $items = '';
        foreach($this->products->get_products() as $product) {
          if(!empty($this->variants->get_variants(array('product_id' => $product->id)))) {
            //print_r($this->variants->get_variants(array('product_id' => $product->id)));
            foreach($this->variants->get_variants(array('product_id' => $product->id)) as $variant) {
              if($this->products->get_images(array('product_id' => $product->id))) {
                //print_r($this->products->get_images(array('product_id' => $product->id)));
                $image = $_SERVER['SERVER_NAME'] . '/files/originals/' . $this->products->get_images(array('product_id' => $product->id))[0]->filename;
                $image = '<picture>' . $image . '</picture>';
              } else {
                $image = '';
              }
              if($this->categories->get_product_categories(array('product_id' => $product->id))) {
                //print_r($this->categories->get_product_categories(array('product_id' => $product->id)));
                $category = $this->categories->get_product_categories(array('product_id' => $product->id))[0]->category_id;
                $category = '<categoryId>' . $category . '</categoryId>';
              } else {
                $category = '';              
              }
              $items .= '<offer id="var' . $variant->id . '" productId="' . $product->id . '" quantity="' . $variant->stock . '"> 
                        <url>' . $_SERVER['SERVER_NAME'] . '/products/' . $product->url . '</url> 
                        <price>' . $variant->price . '</price>
                        ' . $category . ' 
                        ' . $image . ' 
                        <name>' . $product->name . '</name>
                        <productName>' . $product->name . '</productName> 
                        <param name="Артикул" code="article">789789</param> 
                        <param name="Размер" code="size">двухъярусная</param> 
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

  echo $body;