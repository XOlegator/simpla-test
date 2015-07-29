<?php /* Smarty version Smarty-3.1.18, created on 2015-07-29 15:35:30
         compiled from "/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/cart_informer.tpl" */ ?>
<?php /*%%SmartyHeaderCode:68614673555b8c892b85c49-13929806%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    'b20546675df60883443eb4610696a5da419314a4' => 
    array (
      0 => '/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/cart_informer.tpl',
      1 => 1328284806,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '68614673555b8c892b85c49-13929806',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'cart' => 0,
    'currency' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_55b8c892b9b064_70667798',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55b8c892b9b064_70667798')) {function content_55b8c892b9b064_70667798($_smarty_tpl) {?>

<?php if ($_smarty_tpl->tpl_vars['cart']->value->total_products>0) {?>
	В <a href="./cart/">корзине</a>
	<?php echo $_smarty_tpl->tpl_vars['cart']->value->total_products;?>
 <?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['plural'][0][0]->plural_modifier($_smarty_tpl->tpl_vars['cart']->value->total_products,'товар','товаров','товара');?>

	на <?php echo $_smarty_tpl->smarty->registered_plugins[Smarty::PLUGIN_MODIFIER]['convert'][0][0]->convert($_smarty_tpl->tpl_vars['cart']->value->total_price);?>
 <?php echo htmlspecialchars($_smarty_tpl->tpl_vars['currency']->value->sign, ENT_QUOTES, 'UTF-8', true);?>

<?php } else { ?>
	Корзина пуста
<?php }?>
<?php }} ?>
