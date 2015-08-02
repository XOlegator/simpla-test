{capture name=tabs}
	{if in_array('import', $manager->permissions)}<li><a href="index.php?module=ImportAdmin">Импорт</a></li>{/if}
	<li class="active"><a href="index.php?module=ExportAdmin">Экспорт</a></li>
	{if in_array('backup', $manager->permissions)}<li><a href="index.php?module=BackupAdmin">Бекап</a></li>{/if}
{/capture}
{$meta_title='Экспорт товаров' scope=parent}

<script src="{$config->root_url}/simpla/design/js/piecon/piecon.js"></script>
<script>
{literal}
	
var in_process=false;

$(function() {

	// On document load
	$('input#start').click(function() {
 
 		Piecon.setOptions({fallback: 'force'});
 		Piecon.setProgress(0);
    	$("#progressbar").progressbar({ value: 0 });

    	$("#start").hide('fast');
		do_export();
    
	});
  
	function do_export(page)
	{
		page = typeof(page) != 'undefined' ? page : 1;

		$.ajax({
 			 url: "ajax/export.php",
 			 	data: {page:page},
 			 	dataType: 'json',
  				success: function(data){
  				
    				if(data && !data.end)
    				{
    					Piecon.setProgress(Math.round(100*data.page/data.totalpages));
    					$("#progressbar").progressbar({ value: 100*data.page/data.totalpages });
    					do_export(data.page*1+1);
    				}
    				else
    				{	
	    				if(data && data.end)
	    				{
	    					Piecon.setProgress(100);
	    					$("#progressbar").hide('fast');
	    					window.location.href = 'files/export/export.csv';
    					}
    				}
  				},
				error:function(xhr, status, errorThrown) {
					alert(errorThrown+'\n'+xhr.responseText);
        		}  				
  				
		});
	
	}
	
	<!-- Начало вставки от Олега от 30.07.2015 -->
	
	$('input#startExportOrders').on('click', function() {
		do_export_orders_retailCRM();    
	});
  
	function do_export_orders_retailCRM()	{
		// Инициируем запрос на сервер для формирования выгрузки заказов в RetailCRM
		$.ajax({
 		  url: "ajax/export_orders_retailCRM.php",
  			success: function(data) {
  			  $("#resultExportOrdersRetailCRM").append(data);
  			},
				error: function(xhr, status, errorThrown) {
					alert(errorThrown + '\n' + xhr.responseText);
        }
		});	
	}
	
	<!-- Конец вставки Олега от 30.07.2015 -->
	
});
{/literal}
</script>

<style>
	.ui-progressbar-value { background-image: url(design/images/progress.gif); background-position:left; border-color: #009ae2;}
	#progressbar{ clear: both; height:29px; }
	#result{ clear: both; width:100%;}
	#download{ display:none;  clear: both; }
</style>


{if $message_error}
<!-- Системное сообщение -->
<div class="message message_error">
	<span class="text">
	{if $message_error == 'no_permission'}Установите права на запись в папку {$export_files_dir}
	{else}{$message_error}{/if}
	</span>
</div>
<!-- Системное сообщение (The End)-->
{/if}


<div>
	<h1>Экспорт товаров</h1>
	{if $message_error != 'no_permission'}
	<div id='progressbar'></div>
	<input class="button_green" id="start" type="button" name="" value="Экспортировать" />	
	{/if}
</div>
<hr>
<div>
	<h1>Экспорт заказов в RetailCRM</h1>
	{if $message_error != 'no_permission'}
	<div style="clear: both; height: 28px;"></div>
	<input class="button_green" id="startExportOrders" type="button" name="" value="Экспортировать" />
  <div id="resultExportOrdersRetailCRM"></div>	
	{/if}
</div>
<hr>
<div>
	<h1>Экспорт товаров в ICML для RetailCRM</h1>
	{if $message_error != 'no_permission'}
	<div style="clear: both; height: 28px;"></div>
	<input class="button_green" id="startExportGoods" type="button" name="" value="Экспортировать" />
  <div id="resultExportGoodsRetailCRM"></div>	
	{/if}
</div>
