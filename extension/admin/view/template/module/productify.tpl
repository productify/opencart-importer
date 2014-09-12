<?php define("PERPAGE_ITEMS",10);?>
<?php echo $header; ?>
<div id="content">
<div class="breadcrumb">
  <?php foreach ($breadcrumbs as $breadcrumb) { ?>
  <?php echo $breadcrumb['separator']; ?><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a>
  <?php } ?>
</div>
<?php if ($error_warning) { ?>
<div class="warning"><?php echo $error_warning; ?></div>
<?php } ?>
<div class="box">
  <div class="heading">
    <h1><img src="view/image/module.png" alt="" /> <?php echo $heading_title; ?></h1>
    <div class="buttons">
        <a class="button" onclick="location = '<?php echo $import_new;?>'"><span>Import New Products</span></a>
        <a onclick="location = '<?php echo $cancel; ?>';" class="button"><span><?php echo $button_cancel; ?></span></a>
    </div>
  </div>
  <div class="content">
    <span class="productify-title">
        Productify.com Extension - This module makes it easy to import products from <a href="http://productify.com" target="_blank">Productify.com</a>.
    </span>
    <div style="clear:both;"></div>
    <?php
    
        if(isset($step))
        {
            $step = ($step == 4)?4:5;
            //$step = 4;
        }
        elseif( (!isset($url) && !isset($_GET['step']) ) || isset($error))
        {
            $step = 1;
        }
        elseif(isset($url))
        {
            $step = 2;
        }
        
        
        
    ?>
    <div class="steps" style="float:left;width:20%;">
    <ul class="steps_list">
        <li id="step_1" class="<?php echo ($step == 1)?"active":""?>">Step 1</li>
        <li id="step_2" class="<?php echo ($step == 2)?"active":""?>">Step 2</li>
        <li id="step_3" class="<?php echo ($step == 3)?"active":""?>">Step 3</li>
        <li id="step_4" class="<?php echo ($step == 4)?"active":""?>">Step 4</li>
        <li id="step_5" class="<?php echo ($step == 5)?"active":""?>">Step 5</li>
    </ul>
    </div>
    <div class="details" style="float: left;width:70%;padding-left:30px;border-left: 1px solid black; ">
        <?php
        if($step == 1)
        {
            if(isset($error))
                {
                    echo "<br /><span id='error_message'>".$error."</span><br />";
                }
            ?>
            <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                <h2>Start Here:</h2>
                <input type="text" name="url" placeholder='Feed URL:' size="90" required="" /><br />
                <span>(Enter valid productify.com feed URL here. If you're not sure read here <a href="http://productify.com/" target="_blank">Productify</a> ) </span><br />
                <input type="submit" name="search" value="Start"/>
            </form>
            <?php
        }
        elseif($step == 2)
        {
            if(isset($url))
            {
                if(isset($error))
                {
                    echo "<br /><span id='error_message'>".$error."</span><br />";
                    ?>
                    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                        <h2>Start Here:</h2>
                        <input type="text" name="url" placeholder='Feed URL:' size="90" required="" /><br />
                        <span>(Enter valid productify.com feed URL here. If you're not sure read here <a href="http://productify.com/" target="_blank">Productify</a> ) </span><br />
                        <input type="submit" name="search" value="Start"/>
                    </form>
                    <?php
                    
                }
                elseif(isset($xml->products->product) )
                {
                    ?>
                    <div class='products_listing_div'>
                    <h2>Product Listing</h2>
                    <form method="post" action="<?php echo $action; ?>">
                        <input type='hidden' name='url' value = '<?php echo $url;?>'/>
                        <input type='hidden' id='total_import' name='total_import' value ="0"/>
                        <table border='1px' class="products_table">
                            <thead>
                                <tr class='product_heading'>
                                    <th><input type="checkbox" name="all" id="all"/></th>
                                     <th>Product Name</th>
                                     <th>Brand</th>
                                     <th></th>
                                     <th>SKU</th>
                                     <th>Variant</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $i=1;
                                $total_prds = 0;
                                $total_skus = 0;
                                    foreach($xml->products->product as $prd)
                                    {
                                        $total_prds++;
                                        $j=1;
                                        foreach($prd->skus->sku as $s)
                                        {
                                            $total_skus++;
                                            if($j == 1)
                                            {
                                                ?>
                                                <tr class="tr_<?php echo $i?>" style="display: <?php echo ($i != 1)?"none":'' ?>;">
                                                    <td><?php //echo $total_prds;?><input type="checkbox" value="" class="products" id="prd_<?php echo $total_prds;?>" /></td>
                                                    <td><?php echo $prd->product_name;?></td>
                                                    <td><?php echo $prd->brand;?></td>
                                                    <td><input type="checkbox" class="skus sku_<?php echo $total_prds;?>" name='skus[]' value='<?php echo $s->id;?>'/></td>
                                                    <td><?php echo $s->id;?></td>
                                                    <td><?php /*foreach ($s->variants->variant->attributes() as $a => $b){echo $a,'="',$b,"\"\n";}*/echo $s->variants->variant;?></td>
                                                </tr>
                                                <?php
                                            }
                                            else
                                            {
                                                ?>
                                                <tr class="tr_<?php echo $i?>" style="display: <?php echo ($i != 1)?"none":'' ?>;">
                                                    <td></td>
                                                    <td></td>
                                                    <td></td>
                                                    <td><input type="checkbox" class="skus sku_<?php echo $total_prds;?>" name='skus[]' value='<?php echo $s->id;?>' /></td>
                                                    <td><?php echo $s->id;?></td>
                                                    <td><?php /*foreach ($s->variants->variant->attributes() as $a => $b){echo $a,'="',$b,"\"\n";}*/echo $s->variants->variant;?></td>
                                                </tr>
                                                <?php
                                            }
                                            $j++;
                                        }
                                        //print_r($prd);
                                       
                                       if($total_prds % PERPAGE_ITEMS == 0)
                                       {
                                        $i++;
                                       }
                                            
                                    }
                                ?>
                            </tbody>
                        </table>
                        <div class="pagination_div">
                        <button type='button' class="first_button">&lt;&lt; First</button>
                        <?php
                        for($page = 1; $page<=$i;$page++)
                        {
                            if($page == $i && $total_prds % PERPAGE_ITEMS == 0)
                            {
                                continue;
                            }
                            if($page > 10)
                            {break;}
                            $state = ($page == 1)?"active_btn":"";
                            echo "<button class='pagination_button $state' type='button'>$page</button>";
                            
                        }
                        ?>
                        <button type='button' class="last_button" id="<?php echo $i;?>">Last &gt;&gt;</button>
                        </div>
                        <br /><button type="button" id="goto_step3" >Next</button>
                        </div>
                        
                        <div class="import_options_div" style="display:none;">
                        <h2>Import options</h2>
                
                        <p>You've selected:
                        <br />
                        <span id="selected_prds">0</span> Products (with <span id="selected_skus">0</span> SKUs)<br />
                        </p>
                        <p>
                        Other options<br />
                        <input  type="checkbox" id="import_images" name="import_images" value="1"/>Import product images?<br />
                        <input  type="checkbox" id="active_products" name="active_products" value="1"/>Make all imported products active?<br />
                        <div>
                        This process might take several minutes depending on number of data you've chosen to process. 
                        You can close the window once you press "Import" button. Everything will be processed in the background. 
                        <br /><br />
                        If you want to receive a notification email once done, please enter your email below: 
                        </div>
                        <label>Email</label><br /><input id="email" type="email" name="email" size="60" placeholder="youremail@email.com" />
                        </p>
                        <button type="button" id="goto_step2">Back</button>
                        <button type="button"  value="continue" id="continue_btn">Import</button>
                        <input type="submit" style="display:none;" name="save_products" value="continue" id="submit_to_import"/>
                        </div>
                        <div class="processing"></div>
                    </form>
                    <div class="step4_div" style="display:none;">
                        <h2>Processing</h2>
                        <p>
                            Your product import request is beign processed, please wait till we redirect you to the next step.
                        </p>
                    </div>
                    <label class="xml_details">Total Records Fetched:<?php echo $total_prds." products, ". $total_skus." SKUs"?></label>
                    
                    <?php
                }
                else
                {
                    echo "<br /><span id='error_message'>Invalid Feed Format Please Try Again:</span><br />";
                    ?>
                    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form">
                        <h2>Start Here:</h2>
                        <input type="text" name="url" placeholder='Feed URL:' size="90" required="" /><br />
                        <span>(Enter valid productify.com feed URL here. If you're not sure read here <a href="http://productify.com/" target="_blank">Productify</a> ) </span><br />
                        <input type="submit" name="search" value="Start"/>
                    </form>
                    <?php
                }
                //echo "</pre>";
                
            }
            else
            {
                echo "Please Provide a URL to continue";
            }
            
            
        }
        elseif($step == 5)
        {
            ?>
            <div>
                <h2>Complete</h2>
                <p>
                <!-- 
                Your Import is working on background, you will receive an email on completion of the process.<br />
                Some categories and new options will be added automatically by this product importer, before viewing the imported products on the front end make sure to change the categories and the options as required.
                -->
                <?php
                if(isset($message))
                {
                    echo $message;
                }
                if(isset($final_cron))
                {
                    echo "<pre>";
                    print_r($final_cron);
                    echo "</pre>";
                }
                /*
                echo "Congratulations, import from productify.com is complete!<br />";
                echo $success." records - Imported successfully.<br />";
                echo $failure." records - Failed to Import.";
                */
                ?>
                </p>
            </div>
            <?php
        }
        elseif($step == 4)
        {
            ?>
            <!-- step 4-->
            <div class="step4_div">
                <h2>Processing</h2>
                <p>
                    It might take several minutes. Please don't close your browser window until you get to Success Page.<br />Once done, you'll be automatically redirected to success page!
                </p>
            </div>
            <?php
        }
        else
        {
            echo "Please Proceed to 'Import New Products'";
        }
        ?>
    </div>
    
    
    <?php
    
    ?>
    
  </div>
</div>
</div>
<?php echo $footer; ?>
<style>
    .active{
        color:rgb(145, 131, 158);
    }
    label.xml_details {
        position: absolute;
        top: 240px;
        color: rgb(30, 61, 160);
    }

    table.products_table {
        margin-top: 40px;
    }
    .pagination_button.active_btn {
    color: blue;
    background-color: lightsteelblue;
    font-weight: bold;
    }
    span#error_message {
    color: rgb(255,0,0);
    }
</style>
<script>
    $(document).ready(function(){
        //alert('testing');
        $('input#all').click(function(){
            if($(this).is(':checked'))
            {
                $('input:checkbox.products').attr("checked",true);
                $('input:checkbox.skus').attr("checked",true);
            }
            else
            {
                $('input:checkbox.products').attr("checked",false);
                $('input:checkbox.skus').attr("checked",false);
            }
            $("#selected_prds").html($('.products:checked').size());
            $("#total_import").val($('.products:checked').size());
            $("#selected_skus").html($('.skus:checked').size());
        });//input#all click
        
        $('input.products').click(function(){
            var prd_sku = $(this).attr("id");
            var unique_id = prd_sku.split("_");
            //alert(unique_id[1]);
            if($(this).is(':checked'))
            {
                $('input:checkbox.sku_'+unique_id[1]).attr("checked",true);
            }
            else
            {
                $('input:checkbox.sku_'+unique_id[1]).attr("checked",false);
            }
            $("#selected_prds").html($('.products:checked').size());
            $("#total_import").val($('.products:checked').size());
            $("#selected_skus").html($('.skus:checked').size());
        });//input.products click
        
        $('input.skus').click(function(){
            var classes = $(this).attr("class");
            var class2 = classes.split(" ");
            var unique_class = class2[1].split("_");
            //alert(unique_class[1]);
            if($(this).is(":checked"))
            {
                $("input:checkbox#prd_"+unique_class[1]).attr("checked",true);
            }
            else
            {
                //alert($('input:checkbox.sku_'+unique_class[1]+':checked').size());
                //return false;
                if($('input:checkbox.sku_'+unique_class[1]+':checked').size() == 0)
                {
                    $("input:checkbox#prd_"+unique_class[1]).attr("checked",false);
                }
            }
            $("#selected_prds").html($('.products:checked').size());
            $("#total_import").val($('.products:checked').size());
            $("#selected_skus").html($('.skus:checked').size());
             
        });//input.skus click
        $('.pagination_button').click(function(){
            var page = $(this).html();
            //alert(page);
            $('.pagination_button').removeClass("active_btn");
            $('table.products_table tr').attr("style","display:none");
            $('tr.product_heading').removeAttr('style');
            $('tr.tr_'+page).removeAttr("style");
            var this_page = page;
            var last_page = parseInt($('.last_button').attr('id'));
            var page_diff = parseInt(last_page)-parseInt(this_page);
            if(page_diff < 10)
            {
                this_page = parseInt(last_page)-9; 
            }
            
            if(parseInt(this_page) <= 5)
            {
                this_page = 1;
            }
            else
            {
                this_page = parseInt(this_page) - 5;
            }
            $.each($('.pagination_button'),function(){
                $(this).removeAttr("style");
                $(this).html(this_page);
                if(this_page == page)
                {
                    $(this).addClass('active_btn');
                }
                this_page = parseInt(this_page) + 1;
            });
        });//pagination_button click
        
        $('.first_button').click(function(){
            $('.pagination_button:first').html("1");
            $('.pagination_button:first').click();
        });
        $('.last_button').click(function(){
            var last = $(this).attr("id");
            $('.pagination_button:first').html(last);
            $('.pagination_button:first').click();
        });
        
        
        $('#goto_step3').click(function(){
            if($('#selected_prds').html() != 0)
            {
                $('.import_options_div').show();
                $('.xml_details').hide();
                $('.products_listing_div').hide();
                $('li#step_2').removeClass("active");
                $('li#step_3').addClass("active");
            }
            else
            {
                alert("Select at least 1 product to continue.");
            }
                
        });//#goto_step3 .click 
            
        $('#goto_step2').click(function(){
            $('.import_options_div').hide();
            $('.xml_details').show();
            $('.products_listing_div').show();
            $('li#step_2').addClass("active");
            $('li#step_3').removeClass("active");
        });//#goto_step2 click
        
        $('#continue_btn').click(function(){
            if($('#email').val() != "")
            {
                $("#submit_to_import").click();
                $('.import_options_div').hide();
                $('.step4_div').show();
                $('li#step_4').addClass("active");
                $('li#step_3').removeClass("active");
                setTimeout(function(){
                    $("#submit_to_import").click();
                },3000);
            }
        });//#continue_btn click
    });//document.ready close
</script>