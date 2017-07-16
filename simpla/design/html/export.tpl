{capture name=tabs}
    {if in_array('import', $manager->permissions)}<li><a href="index.php?module=ImportAdmin">Импорт</a></li>{/if}
    <li class="active"><a href="index.php?module=ExportAdmin">Экспорт</a></li>
    {if in_array('backup', $manager->permissions)}<li><a href="index.php?module=BackupAdmin">Бекап</a></li>{/if}
{/capture}
{$meta_title='Экспорт товаров' scope=parent}

<!-- Начало вставки для RetailCrm <-> Simpla CMS -->
<style>
    .button_green.disabled {
      color: #bdbbb9;
      border: 1px solid #d3d3d3;
      cursor: auto;
      text-shadow: white 0 1px 0;
      -webkit-box-shadow: white 0 1px 0 0 inset;
      -moz-box-shadow: white 0 1px 0 0 inset;
      -ms-box-shadow: white 0 1px 0 0 inset;
      -o-box-shadow: white 0 1px 0 0 inset;
      box-shadow: white 0 1px 0 0 inset;
    }
    .in-progress {
      text-shadow: none;
      background: -webkit-linear-gradient(-45deg, rgba(255, 255, 255, 0.6) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.6) 75%, transparent 75%, transparent), -webkit-linear-gradient(top, #f6f5f0, #e1e0dc);
      background: -moz-linear-gradient(-45deg, rgba(255, 255, 255, 0.6) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.6) 75%, transparent 75%, transparent), -moz-linear-gradient(top, #f6f5f0, #e1e0dc);
      background: -ms-linear-gradient(-45deg, rgba(255, 255, 255, 0.6) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.6) 75%, transparent 75%, transparent), -ms-linear-gradient(top, #f6f5f0, #e1e0dc);
      background: -o-linear-gradient(-45deg, rgba(255, 255, 255, 0.6) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.6) 75%, transparent 75%, transparent), -o-linear-gradient(top, #f6f5f0, #e1e0dc);
      background: linear-gradient(-45deg, rgba(255, 255, 255, 0.6) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.6) 75%, transparent 75%, transparent), linear-gradient(top, #f6f5f0, #e1e0dc);
      -pie-background: linear-gradient(-45deg, rgba(255, 255, 255, 0.6) 25%, transparent 25%, transparent 50%, rgba(255, 255, 255, 0.6) 50%, rgba(255, 255, 255, 0.6) 75%, transparent 75%, transparent), linear-gradient(top, #f6f5f0, #e1e0dc);
      background-repeat: repeat;
      -webkit-background-size: 40px 40px, 100% 100%;
      -moz-background-size: 40px 40px, 100% 100%;
      -ms-background-size: 40px 40px, 100% 100%;
      -o-background-size: 40px 40px, 100% 100%;
      background-size: 40px 40px, 100% 100%;
      -webkit-animation: progress-bar-stripes 2s linear infinite;
      -moz-animation: progress-bar-stripes 2s linear infinite;
      -ms-animation: progress-bar-stripes 2s linear infinite;
      -o-animation: progress-bar-stripes 2s linear infinite;
      animation: progress-bar-stripes 2s linear infinite;
    }

    @-webkit-keyframes progress-bar-stripes {
      from { background-position: 0 0; }
      to { background-position: 40px 0; }
    }

    @-moz-keyframes progress-bar-stripes {
      from { background-position: 0 0; }
      to { background-position: 40px 0; }
    }

    @keyframes progress-bar-stripes {
      from { background-position: 0 0; }
      to { background-position: 40px 0; }
    }
</style>
<!-- Конец вставки для RetailCrm <-> Simpla CMS -->

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

    <!-- Начало вставки для RetailCrm <-> Simpla CMS -->

    $('#startExportOrdersToRetail').on('click', function() {
        // Отключим кнопку, чтобы её нельзя было нажать повторно,
        // пока не закончится обраблтка
        $(this).prop('disabled', true);
        // Добавим анамацию загрузки
        $(this).addClass('in-progress');

        do_export_orders_retailCRM();
    });

    function do_export_orders_retailCRM() {
      $("#resultExportOrdersRetailCRM").html('');
        // Инициируем запрос на сервер для формирования выгрузки заказов в RetailCRM
        $.post('ajax/export_orders_retailCRM.php')
            .done(function(data) {
                $("#resultExportOrdersRetailCRM").append(data);
            })
            .fail(function(xhr, status, errorThrown) {
                alert(errorThrown + "\n" + xhr.responseText);
            })
            .always(function() {
                // Включим кнопку
                $('#startExportOrdersToRetail').prop('disabled', false);
                // Удалим анамацию загрузки
                $('#startExportOrdersToRetail').removeClass('in-progress');
            });
    }

    $('#startExportProdsToAmo').on('click', function() {
        // Отключим кнопку, чтобы её нельзя было нажать повторно,
        // пока не закончится обраблтка
        $(this).prop('disabled', true);
        // Добавим анамацию загрузки
        $(this).addClass('in-progress');

        do_export_goods_retailCRM();
    });

    function do_export_goods_retailCRM() {
      $("#resultExportGoodsRetailCRM").html('');
        // Инициируем запрос на сервер для формирования выгрузки товаров в файл ICML для RetailCRM
        $.post('ajax/export_to_ICML_retailCRM.php')
            .done(function(data) {
                $("#resultExportGoodsRetailCRM").append(data);
            })
            .fail(function(xhr, status, errorThrown) {
                alert(errorThrown + "\n" + xhr.responseText);
            })
            .always(function() {
                // Включим кнопку
                $('#startExportProdsToAmo').prop('disabled', false);
                // Удалим анамацию загрузки
                $('#startExportProdsToAmo').removeClass('in-progress');
            });
    }

    <!-- Конец вставки для RetailCrm <-> Simpla CMS -->
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
<div>
    <h1>Экспорт заказов в RetailCRM</h1>
    {if $message_error != 'no_permission'}
    <div style="clear: both; height: 28px;"></div>
    <input class="button_green" id="startExportOrdersToRetail" type="button" name="" value="Экспортировать" />
  <div id="resultExportOrdersRetailCRM"></div>
    {/if}
</div>
<hr>
<div>
    <h1>Экспорт товаров в ICML для RetailCRM</h1>
    {if $message_error != 'no_permission'}
    <div style="clear: both; height: 28px;"></div>
    <input class="button_green" id="startExportProdsToRetail" type="button" name="" value="Экспортировать" />
  <div id="resultExportGoodsRetailCRM"></div>
    {/if}
</div>
