<?php /* Smarty version Smarty-3.1.18, created on 2015-07-29 15:42:23
         compiled from "/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/register.tpl" */ ?>
<?php /*%%SmartyHeaderCode:65035166755b8ca2f1921a1-93331545%%*/if(!defined('SMARTY_DIR')) exit('no direct access allowed');
$_valid = $_smarty_tpl->decodeProperties(array (
  'file_dependency' => 
  array (
    '519225a53f88d2283139c204d1609baeb10d4ab3' => 
    array (
      0 => '/mnt/oldhome/oleg/vhosts/simpla-test.local/www/design/default/html/register.tpl',
      1 => 1394918432,
      2 => 'file',
    ),
  ),
  'nocache_hash' => '65035166755b8ca2f1921a1-93331545',
  'function' => 
  array (
  ),
  'variables' => 
  array (
    'error' => 0,
    'name' => 0,
    'email' => 0,
  ),
  'has_nocache_code' => false,
  'version' => 'Smarty-3.1.18',
  'unifunc' => 'content_55b8ca2f1fe511_52810998',
),false); /*/%%SmartyHeaderCode%%*/?>
<?php if ($_valid && !is_callable('content_55b8ca2f1fe511_52810998')) {function content_55b8ca2f1fe511_52810998($_smarty_tpl) {?><?php if (!is_callable('smarty_function_math')) include '/mnt/oldhome/oleg/vhosts/simpla-test.local/www/Smarty/libs/plugins/function.math.php';
?>


<?php $_smarty_tpl->tpl_vars['canonical'] = new Smarty_variable("/user/register", null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['canonical'] = clone $_smarty_tpl->tpl_vars['canonical'];?>

<?php $_smarty_tpl->tpl_vars['meta_title'] = new Smarty_variable("Регистрация", null, 1);
if ($_smarty_tpl->parent != null) $_smarty_tpl->parent->tpl_vars['meta_title'] = clone $_smarty_tpl->tpl_vars['meta_title'];?>

<h1>Регистрация</h1>

<?php if ($_smarty_tpl->tpl_vars['error']->value) {?>
<div class="message_error">
	<?php if ($_smarty_tpl->tpl_vars['error']->value=='empty_name') {?>Введите имя
	<?php } elseif ($_smarty_tpl->tpl_vars['error']->value=='empty_email') {?>Введите email
	<?php } elseif ($_smarty_tpl->tpl_vars['error']->value=='empty_password') {?>Введите пароль
	<?php } elseif ($_smarty_tpl->tpl_vars['error']->value=='user_exists') {?>Пользователь с таким email уже зарегистрирован
	<?php } elseif ($_smarty_tpl->tpl_vars['error']->value=='captcha') {?>Неверно введена капча
	<?php } else { ?><?php echo $_smarty_tpl->tpl_vars['error']->value;?>
<?php }?>
</div>
<?php }?>

<form class="form register_form" method="post">
	<label>Имя</label>
	<input type="text" name="name" data-format=".+" data-notice="Введите имя" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['name']->value, ENT_QUOTES, 'UTF-8', true);?>
" maxlength="255" />
	
	<label>Email</label>
	<input type="text" name="email" data-format="email" data-notice="Введите email" value="<?php echo htmlspecialchars($_smarty_tpl->tpl_vars['email']->value, ENT_QUOTES, 'UTF-8', true);?>
" maxlength="255" />

    <label>Пароль</label>
    <input type="password" name="password" data-format=".+" data-notice="Введите пароль" value="" />

	<div class="captcha"><img src="captcha/image.php?<?php echo smarty_function_math(array('equation'=>'rand(10,10000)'),$_smarty_tpl);?>
"/></div> 
	<input class="input_captcha" id="comment_captcha" type="text" name="captcha_code" value="" data-format="\d\d\d\d" data-notice="Введите капчу"/>

	<input type="submit" class="button" name="register" value="Зарегистрироваться">

</form>
<?php }} ?>
