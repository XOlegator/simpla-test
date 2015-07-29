<?php /* Smarty version Smarty-3.1.18, created on 2015-07-29 15:47:44
         compiled from "/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/products.tpl" */ ?>
<?php /*%%SmartyHeaderCode:52817176655b8cb70e54692-67135113%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '5ecad7347a039ada2fbc1821bbc9efb11c58d0fd' => 
    array (
      0 => '/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/products.tpl',
      1 => 1409252924,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '52817176655b8cb70e54692-67135113',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'category' => 0,
    'brand' => 0,
    'keyword' => 0,
    'cat' => 0,
    'page' => 0,
    'current_page_num' => 0,
    'b' => 0,
    'config' => 0,
    'features' => 0,
    'f' => 0,
    'key' => 0,
    'o' => 0,
    'products' => 0,
    'sort' => 0,
    'product' => 0,
    'v' => 0,
    'currency' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_55b8cb7106bfc2_49561305',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55b8cb7106bfc2_49561305')) {function content_55b8cb7106bfc2_49561305($_smarty_tpl) {?>


<?php if ($_smarty_tpl->tpl_vars['category']->value&&$_smarty_tpl->tpl_vars['brand']->value) {?>
<?php $_smarty_tpl->tpl_vars['canonical'] = new Smarty_variable("/catalog/".((string)$_smarty_tpl->tpl_vars['category']->value->url)."/".((string)$_smarty_tpl->tpl_vars['brand']->value->url), null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['canonical'] = clone $_smarty_tpl->tpl_vars['canonical'];?>
<?php } elseif ($_smarty_tpl->tpl_vars['category']->value) {?>
<?php $_smarty_tpl->tpl_vars['canonical'] = new Smarty_variable("/catalog/".((string)$_smarty_tpl->tpl_vars['category']->value->url), null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['canonical'] = clone $_smarty_tpl->tpl_vars['canonical'];?>
<?php } elseif ($_smarty_tpl->tpl_vars['brand']->value) {?>
<?php $_smarty_tpl->tpl_vars['canonical'] = new Smarty_variable("/brands/".((string)$_smarty_tpl->tpl_vars['brand']->value->url), null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['canonical'] = clone $_smarty_tpl->tpl_vars['canonical'];?>
<?php } elseif ($_smarty_tpl->tpl_vars['keyword']->value) {?>
<?php ob_start();?><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['keyword']->value, ENT_QUOTES, 'UTF-8', true);?>
<?php $_tmp1=ob_get_clean();?><?php $_smarty_tpl->tpl_vars['canonical'] = new Smarty_variable("/products?keyword=".$_tmp1, null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['canonical'] = clone $_smarty_tpl->tpl_vars['canonical'];?>
<?php } else { ?>
<?php $_smarty_tpl->tpl_vars['canonical'] = new Smarty_variable("/products", null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['canonical'] = clone $_smarty_tpl->tpl_vars['canonical'];?>
<?php }?>

<!-- Хлебные крошки /-->
<div id="path">
	<a href="/">Главная</a>
	<?php if ($_smarty_tpl->tpl_vars['category']->value) {?>
	<?php  $_smarty_tpl->tpl_vars['cat'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['cat']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['category']->value->path; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['cat']->key => $_smarty_tpl->tpl_vars['cat']->value) {
$_smarty_tpl->tpl_vars['cat']->_loop = true;
?>
	→ <a href="catalog/<?php echo $_smarty_tpl->tpl_vars['cat']->value->url;?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['cat']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</a>
	<?php } ?>  
	<?php if ($_smarty_tpl->tpl_vars['brand']->value) {?>
	→ <a href="catalog/<?php echo $_smarty_tpl->tpl_vars['cat']->value->url;?>
/<?php echo $_smarty_tpl->tpl_vars['brand']->value->url;?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['brand']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</a>
	<?php }?>
	<?php } elseif ($_smarty_tpl->tpl_vars['brand']->value) {?>
	→ <a href="brands/<?php echo $_smarty_tpl->tpl_vars['brand']->value->url;?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['brand']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</a>
	<?php } elseif ($_smarty_tpl->tpl_vars['keyword']->value) {?>
	→ Поиск
	<?php }?>
</div>
<!-- Хлебные крошки #End /-->


<?php if ($_smarty_tpl->tpl_vars['keyword']->value) {?>
<h1>Поиск <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['keyword']->value, ENT_QUOTES, 'UTF-8', true);?>
</h1>
<?php } elseif ($_smarty_tpl->tpl_vars['page']->value) {?>
<h1><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['page']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</h1>
<?php } else { ?>
<h1><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['category']->value->name, ENT_QUOTES, 'UTF-8', true);?>
 <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['brand']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</h1>
<?php }?>



<?php echo $_smarty_tpl->tpl_vars['page']->value->body;?>


<?php if ($_smarty_tpl->tpl_vars['current_page_num']->value==1) {?>

<?php echo $_smarty_tpl->tpl_vars['category']->value->description;?>

<?php }?>


<?php if ($_smarty_tpl->tpl_vars['category']->value->brands) {?>
<div id="brands">
	<a href="catalog/<?php echo $_smarty_tpl->tpl_vars['category']->value->url;?>
" <?php if (!$_smarty_tpl->tpl_vars['brand']->value->id) {?>class="selected"<?php }?>>Все бренды</a>
	<?php  $_smarty_tpl->tpl_vars['b'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['b']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['category']->value->brands; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['b']->key => $_smarty_tpl->tpl_vars['b']->value) {
$_smarty_tpl->tpl_vars['b']->_loop = true;
?>
		<?php if ($_smarty_tpl->tpl_vars['b']->value->image) {?>
		<a data-brand="<?php echo $_smarty_tpl->tpl_vars['b']->value->id;?>
" href="catalog/<?php echo $_smarty_tpl->tpl_vars['category']->value->url;?>
/<?php echo $_smarty_tpl->tpl_vars['b']->value->url;?>
"><img src="<?php echo $_smarty_tpl->tpl_vars['config']->value->brands_images_dir;?>
<?php echo $_smarty_tpl->tpl_vars['b']->value->image;?>
" alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['b']->value->name, ENT_QUOTES, 'UTF-8', true);?>
"></a>
		<?php } else { ?>
		<a data-brand="<?php echo $_smarty_tpl->tpl_vars['b']->value->id;?>
" href="catalog/<?php echo $_smarty_tpl->tpl_vars['category']->value->url;?>
/<?php echo $_smarty_tpl->tpl_vars['b']->value->url;?>
" <?php if ($_smarty_tpl->tpl_vars['b']->value->id==$_smarty_tpl->tpl_vars['brand']->value->id) {?>class="selected"<?php }?>><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['b']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</a>
		<?php }?>
	<?php } ?>
</div>
<?php }?>

<?php if ($_smarty_tpl->tpl_vars['current_page_num']->value==1) {?>

<?php echo $_smarty_tpl->tpl_vars['brand']->value->description;?>

<?php }?>


<?php if ($_smarty_tpl->tpl_vars['features']->value) {?>
<table id="features">
	<?php  $_smarty_tpl->tpl_vars['f'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['f']->_loop = false;
 $_smarty_tpl->tpl_vars['key'] = new Smarty_Variable;
 $_from = $_smarty_tpl->tpl_vars['features']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['f']->key => $_smarty_tpl->tpl_vars['f']->value) {
$_smarty_tpl->tpl_vars['f']->_loop = true;
 $_smarty_tpl->tpl_vars['key']->value = $_smarty_tpl->tpl_vars['f']->key;
?>
	<tr>
	<td class="feature_name" data-feature="<?php echo $_smarty_tpl->tpl_vars['f']->value->id;?>
">
		<?php echo $_smarty_tpl->tpl_vars['f']->value->name;?>
:
	</td>
	<td class="feature_values">
		<a href="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['url'][0][0]->url_modifier(array('params'=>array($_smarty_tpl->tpl_vars['f']->value->id=>null,'page'=>null)),$_smarty_tpl);?>
" <?php if (!$_GET[$_smarty_tpl->tpl_vars['key']->value]) {?>class="selected"<?php }?>>Все</a>
		<?php  $_smarty_tpl->tpl_vars['o'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['o']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['f']->value->options; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['o']->key => $_smarty_tpl->tpl_vars['o']->value) {
$_smarty_tpl->tpl_vars['o']->_loop = true;
?>
		<a href="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['url'][0][0]->url_modifier(array('params'=>array($_smarty_tpl->tpl_vars['f']->value->id=>$_smarty_tpl->tpl_vars['o']->value->value,'page'=>null)),$_smarty_tpl);?>
" <?php if ($_GET[$_smarty_tpl->tpl_vars['key']->value]==$_smarty_tpl->tpl_vars['o']->value->value) {?>class="selected"<?php }?>><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['o']->value->value, ENT_QUOTES, 'UTF-8', true);?>
</a>
		<?php } ?>
	</td>
	</tr>
	<?php } ?>
</table>
<?php }?>


<!--Каталог товаров-->
<?php if ($_smarty_tpl->tpl_vars['products']->value) {?>


<?php if (count($_smarty_tpl->tpl_vars['products']->value)>0) {?>
<div class="sort">
	Сортировать по 
	<a <?php if ($_smarty_tpl->tpl_vars['sort']->value=='position') {?> class="selected"<?php }?> href="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['url'][0][0]->url_modifier(array('sort'=>'position','page'=>null),$_smarty_tpl);?>
">умолчанию</a>
	<a <?php if ($_smarty_tpl->tpl_vars['sort']->value=='price') {?>    class="selected"<?php }?> href="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['url'][0][0]->url_modifier(array('sort'=>'price','page'=>null),$_smarty_tpl);?>
">цене</a>
	<a <?php if ($_smarty_tpl->tpl_vars['sort']->value=='name') {?>     class="selected"<?php }?> href="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['url'][0][0]->url_modifier(array('sort'=>'name','page'=>null),$_smarty_tpl);?>
">названию</a>
</div>
<?php }?>


<?php echo $_smarty_tpl->getSubTemplate ('pagination.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>



<!-- Список товаров-->
<ul class="products">

	<?php  $_smarty_tpl->tpl_vars['product'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['product']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['products']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['product']->key => $_smarty_tpl->tpl_vars['product']->value) {
$_smarty_tpl->tpl_vars['product']->_loop = true;
?>
	<!-- Товар-->
	<li class="product">
		
		<!-- Фото товара -->
		<?php if ($_smarty_tpl->tpl_vars['product']->value->image) {?>
		<div class="image">
			<a href="products/<?php echo $_smarty_tpl->tpl_vars['product']->value->url;?>
"><img src="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['resize'][0][0]->resize_modifier($_smarty_tpl->tpl_vars['product']->value->image->filename,200,200);?>
" alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['product']->value->name, ENT_QUOTES, 'UTF-8', true);?>
"/></a>
		</div>
		<?php }?>
		<!-- Фото товара (The End) -->

		<div class="product_info">
		<!-- Название товара -->
		<h3 class="<?php if ($_smarty_tpl->tpl_vars['product']->value->featured) {?>featured<?php }?>"><a data-product="<?php echo $_smarty_tpl->tpl_vars['product']->value->id;?>
" href="products/<?php echo $_smarty_tpl->tpl_vars['product']->value->url;?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['product']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</a></h3>
		<!-- Название товара (The End) -->

		<!-- Описание товара -->
		<div class="annotation"><?php echo $_smarty_tpl->tpl_vars['product']->value->annotation;?>
</div>
		<!-- Описание товара (The End) -->
		
		<?php if (count($_smarty_tpl->tpl_vars['product']->value->variants)>0) {?>
		<!-- Выбор варианта товара -->
		<form class="variants" action="/cart">
			<table>
			<?php  $_smarty_tpl->tpl_vars['v'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['v']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['product']->value->variants; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['v']->index=-1;
foreach ($_from as $_smarty_tpl->tpl_vars['v']->key => $_smarty_tpl->tpl_vars['v']->value) {
$_smarty_tpl->tpl_vars['v']->_loop = true;
 $_smarty_tpl->tpl_vars['v']->index++;
 $_smarty_tpl->tpl_vars['v']->first = $_smarty_tpl->tpl_vars['v']->index === 0;
?>
			<tr class="variant">
				<td>
					<input id="variants_<?php echo $_smarty_tpl->tpl_vars['v']->value->id;?>
" name="variant" value="<?php echo $_smarty_tpl->tpl_vars['v']->value->id;?>
" type="radio" class="variant_radiobutton" <?php if ($_smarty_tpl->tpl_vars['v']->first) {?>checked<?php }?> <?php if (count($_smarty_tpl->tpl_vars['product']->value->variants)<2) {?>style="display:none;"<?php }?>/>
				</td>
				<td>
					<?php if ($_smarty_tpl->tpl_vars['v']->value->name) {?><label class="variant_name" for="variants_<?php echo $_smarty_tpl->tpl_vars['v']->value->id;?>
"><?php echo $_smarty_tpl->tpl_vars['v']->value->name;?>
</label><?php }?>
				</td>
				<td>
					<?php if ($_smarty_tpl->tpl_vars['v']->value->compare_price>0) {?><span class="compare_price"><?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['v']->value->compare_price);?>
</span><?php }?>
					<span class="price"><?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['v']->value->price);?>
 <span class="currency"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['currency']->value->sign, ENT_QUOTES, 'UTF-8', true);?>
</span></span>
				</td>
			</tr>
			<?php } ?>
			</table>
			<input type="submit" class="button" value="в корзину" data-result-text="добавлено"/>
		</form>
		<!-- Выбор варианта товара (The End) -->
		<?php } else { ?>
			Нет в наличии
		<?php }?>

		</div>
		
	</li>
	<!-- Товар (The End)-->
	<?php } ?>
			
</ul>

<?php echo $_smarty_tpl->getSubTemplate ('pagination.tpl', $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, null, array(), 0);?>
	
<!-- Список товаров (The End)-->

<?php } else { ?>
Товары не найдены
<?php }?>
<!--Каталог товаров (The End)--><?php }} ?>
