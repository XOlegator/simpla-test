<?php /* Smarty version Smarty-3.1.18, created on 2015-07-29 15:44:10
         compiled from "/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/order.tpl" */ ?>
<?php /*%%SmartyHeaderCode:153378460155b8ca9a284672-98410373%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'd4f26020f48ded4c024214e8c8e97afc6a858002' => 
    array (
      0 => '/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/order.tpl',
      1 => 1392558988,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '153378460155b8ca9a284672-98410373',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'order' => 0,
    'purchases' => 0,
    'purchase' => 0,
    'image' => 0,
    'product' => 0,
    'currency' => 0,
    'settings' => 0,
    'delivery' => 0,
    'payment_methods' => 0,
    'payment_method' => 0,
    'all_currencies' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_55b8ca9a3c0db0_45083427',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55b8ca9a3c0db0_45083427')) {function content_55b8ca9a3c0db0_45083427($_smarty_tpl) {?>

<?php $_smarty_tpl->tpl_vars['meta_title'] = new Smarty_variable("Ваш заказ №".((string)$_smarty_tpl->tpl_vars['order']->value->id), null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['meta_title'] = clone $_smarty_tpl->tpl_vars['meta_title'];?>

<h1>Ваш заказ №<?php echo $_smarty_tpl->tpl_vars['order']->value->id;?>
 
<?php if ($_smarty_tpl->tpl_vars['order']->value->status==0) {?>принят<?php }?>
<?php if ($_smarty_tpl->tpl_vars['order']->value->status==1) {?>в обработке<?php } elseif ($_smarty_tpl->tpl_vars['order']->value->status==2) {?>выполнен<?php }?>
<?php if ($_smarty_tpl->tpl_vars['order']->value->paid==1) {?>, оплачен<?php } else { ?><?php }?>
</h1>


<table id="purchases">

<?php  $_smarty_tpl->tpl_vars['purchase'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['purchase']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['purchases']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
foreach ($_from as $_smarty_tpl->tpl_vars['purchase']->key => $_smarty_tpl->tpl_vars['purchase']->value) {
$_smarty_tpl->tpl_vars['purchase']->_loop = true;
?>
<tr>
	
	<td class="image">
		<?php $_smarty_tpl->tpl_vars['image'] = new Smarty_variable($_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['first'][0][0]->first_modifier($_smarty_tpl->tpl_vars['purchase']->value->product->images), null, 0);?>
		<?php if ($_smarty_tpl->tpl_vars['image']->value) {?>
		<a href="products/<?php echo $_smarty_tpl->tpl_vars['purchase']->value->product->url;?>
"><img src="<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['resize'][0][0]->resize_modifier($_smarty_tpl->tpl_vars['image']->value->filename,50,50);?>
" alt="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['product']->value->name, ENT_QUOTES, 'UTF-8', true);?>
"></a>
		<?php }?>
	</td>
	
	
	<td class="name">
		<a href="/products/<?php echo $_smarty_tpl->tpl_vars['purchase']->value->product->url;?>
"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['purchase']->value->product_name, ENT_QUOTES, 'UTF-8', true);?>
</a>
		<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['purchase']->value->variant_name, ENT_QUOTES, 'UTF-8', true);?>

		<?php if ($_smarty_tpl->tpl_vars['order']->value->paid&&$_smarty_tpl->tpl_vars['purchase']->value->variant->attachment) {?>
			<a class="download_attachment" href="order/<?php echo $_smarty_tpl->tpl_vars['order']->value->url;?>
/<?php echo $_smarty_tpl->tpl_vars['purchase']->value->variant->attachment;?>
">скачать файл</a>
		<?php }?>
	</td>

	
	<td class="price">
		<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert(($_smarty_tpl->tpl_vars['purchase']->value->price));?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['currency']->value->sign;?>

	</td>

	
	<td class="amount">
		&times; <?php echo $_smarty_tpl->tpl_vars['purchase']->value->amount;?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['settings']->value->units;?>

	</td>

	
	<td class="price">
		<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert(($_smarty_tpl->tpl_vars['purchase']->value->price*$_smarty_tpl->tpl_vars['purchase']->value->amount));?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['currency']->value->sign;?>

	</td>
</tr>
<?php } ?>

<?php if ($_smarty_tpl->tpl_vars['order']->value->discount>0) {?>
<tr>
	<th class="image"></th>
	<th class="name">скидка</th>
	<th class="price"></th>
	<th class="amount"></th>
	<th class="price">
		<?php echo $_smarty_tpl->tpl_vars['order']->value->discount;?>
&nbsp;%
	</th>
</tr>
<?php }?>

<?php if ($_smarty_tpl->tpl_vars['order']->value->coupon_discount>0) {?>
<tr>
	<th class="image"></th>
	<th class="name">купон</th>
	<th class="price"></th>
	<th class="amount"></th>
	<th class="price">
		&minus;<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['order']->value->coupon_discount);?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['currency']->value->sign;?>

	</th>
</tr>
<?php }?>

<?php if (!$_smarty_tpl->tpl_vars['order']->value->separate_delivery&&$_smarty_tpl->tpl_vars['order']->value->delivery_price>0) {?>
<tr>
	<td class="image"></td>
	<td class="name"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['delivery']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</td>
	<td class="price"></td>
	<td class="amount"></td>
	<td class="price">
		<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['order']->value->delivery_price);?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['currency']->value->sign;?>

	</td>
</tr>
<?php }?>

<tr>
	<th class="image"></th>
	<th class="name">итого</th>
	<th class="price"></th>
	<th class="amount"></th>
	<th class="price">
		<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['order']->value->total_price);?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['currency']->value->sign;?>

	</th>
</tr>

<?php if ($_smarty_tpl->tpl_vars['order']->value->separate_delivery) {?>
<tr>
	<td class="image"></td>
	<td class="name"><?php echo htmlspecialchars($_smarty_tpl->tpl_vars['delivery']->value->name, ENT_QUOTES, 'UTF-8', true);?>
</td>
	<td class="price"></td>
	<td class="amount"></td>
	<td class="price">
		<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['order']->value->delivery_price);?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['currency']->value->sign;?>

	</td>
</tr>
<?php }?>

</table>


<h2>Детали заказа</h2>
<table class="order_info">
	<tr>
		<td>
			Дата заказа
		</td>
		<td>
			<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['date'][0][0]->date_modifier($_smarty_tpl->tpl_vars['order']->value->date);?>
 в
			<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['time'][0][0]->time_modifier($_smarty_tpl->tpl_vars['order']->value->date);?>

		</td>
	</tr>
	<?php if ($_smarty_tpl->tpl_vars['order']->value->name) {?>
	<tr>
		<td>
			Имя
		</td>
		<td>
			<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['order']->value->name, ENT_QUOTES, 'UTF-8', true);?>

		</td>
	</tr>
	<?php }?>
	<?php if ($_smarty_tpl->tpl_vars['order']->value->email) {?>
	<tr>
		<td>
			Email
		</td>
		<td>
			<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['order']->value->email, ENT_QUOTES, 'UTF-8', true);?>

		</td>
	</tr>
	<?php }?>
	<?php if ($_smarty_tpl->tpl_vars['order']->value->phone) {?>
	<tr>
		<td>
			Телефон
		</td>
		<td>
			<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['order']->value->phone, ENT_QUOTES, 'UTF-8', true);?>

		</td>
	</tr>
	<?php }?>
	<?php if ($_smarty_tpl->tpl_vars['order']->value->address) {?>
	<tr>
		<td>
			Адрес доставки
		</td>
		<td>
			<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['order']->value->address, ENT_QUOTES, 'UTF-8', true);?>

		</td>
	</tr>
	<?php }?>
	<?php if ($_smarty_tpl->tpl_vars['order']->value->comment) {?>
	<tr>
		<td>
			Комментарий
		</td>
		<td>
			<?php echo nl2br(htmlspecialchars($_smarty_tpl->tpl_vars['order']->value->comment, ENT_QUOTES, 'UTF-8', true));?>

		</td>
	</tr>
	<?php }?>
</table>


<?php if (!$_smarty_tpl->tpl_vars['order']->value->paid) {?>

<?php if ($_smarty_tpl->tpl_vars['payment_methods']->value&&!$_smarty_tpl->tpl_vars['payment_method']->value&&$_smarty_tpl->tpl_vars['order']->value->total_price>0) {?>
<form method="post">
<h2>Выберите способ оплаты</h2>
<ul id="deliveries">
    <?php  $_smarty_tpl->tpl_vars['payment_method'] = new Smarty_Variable; $_smarty_tpl->tpl_vars['payment_method']->_loop = false;
 $_from = $_smarty_tpl->tpl_vars['payment_methods']->value; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array');}
 $_smarty_tpl->tpl_vars['payment_method']->index=-1;
foreach ($_from as $_smarty_tpl->tpl_vars['payment_method']->key => $_smarty_tpl->tpl_vars['payment_method']->value) {
$_smarty_tpl->tpl_vars['payment_method']->_loop = true;
 $_smarty_tpl->tpl_vars['payment_method']->index++;
 $_smarty_tpl->tpl_vars['payment_method']->first = $_smarty_tpl->tpl_vars['payment_method']->index === 0;
?>
    	<li>
    		<div class="checkbox">
    			<input type=radio name=payment_method_id value='<?php echo $_smarty_tpl->tpl_vars['payment_method']->value->id;?>
' <?php if ($_smarty_tpl->tpl_vars['payment_method']->first) {?>checked<?php }?> id=payment_<?php echo $_smarty_tpl->tpl_vars['payment_method']->value->id;?>
>
    		</div>			
			<h3><label for=payment_<?php echo $_smarty_tpl->tpl_vars['payment_method']->value->id;?>
>	<?php echo $_smarty_tpl->tpl_vars['payment_method']->value->name;?>
, к оплате <?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['order']->value->total_price,$_smarty_tpl->tpl_vars['payment_method']->value->currency_id);?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['all_currencies']->value[$_smarty_tpl->tpl_vars['payment_method']->value->currency_id]->sign;?>
</label></h3>
			<div class="description">
			<?php echo $_smarty_tpl->tpl_vars['payment_method']->value->description;?>

			</div>
    	</li>
    <?php } ?>
</ul>
<input type='submit' class="button" value='Закончить заказ'>
</form>


<?php } elseif ($_smarty_tpl->tpl_vars['payment_method']->value) {?>
<h2>Способ оплаты &mdash; <?php echo $_smarty_tpl->tpl_vars['payment_method']->value->name;?>

<form method=post><input type=submit name='reset_payment_method' value='Выбрать другой способ оплаты'></form>	
</h2>
<p>
<?php echo $_smarty_tpl->tpl_vars['payment_method']->value->description;?>

</p>
<h2>
К оплате <?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['order']->value->total_price,$_smarty_tpl->tpl_vars['payment_method']->value->currency_id);?>
&nbsp;<?php echo $_smarty_tpl->tpl_vars['all_currencies']->value[$_smarty_tpl->tpl_vars['payment_method']->value->currency_id]->sign;?>

</h2>


<?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_FUNCTION]['checkout_form'][0][0]->checkout_form(array('order_id'=>$_smarty_tpl->tpl_vars['order']->value->id,'module'=>$_smarty_tpl->tpl_vars['payment_method']->value->module),$_smarty_tpl);?>

<?php }?>

<?php }?>


 <?php }} ?>
