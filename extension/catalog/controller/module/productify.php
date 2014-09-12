<?php
################################################################################################
#  Productify XML Importer for Opencart 1.5.1.x From Productify http://productify.com		   #
################################################################################################
define("IMPORT_AT_ONCE",5);  
class ControllerModuleProductify extends Controller {
    
public function index()
    {
        //echo "starting import<br />";
        set_time_limit(0);
        $this->load->model("module/productify");
        $this->check_pending_processes();
        $tasks = $this->model_module_productify->test_cron();
        if($tasks == null)
        {
            //die("no task to do");
            $this->model_module_productify->update_productify_import();
            
            $output = shell_exec('crontab -l');
            $current_cron_array = explode("\n",$output);
            $current_url = $this->url->link("module/productify");
            /*echo "<pre>";
            echo $current_url."<br />";
            print_r($current_cron_array);
            
            echo "</pre>";
            */
            $new_cron = array();
            $update_cron = 0;
            foreach($current_cron_array as $cur_crn)
            {
                if(strpos($cur_crn,$current_url) != false)
                {
                    $update_cron = 1;
                    continue;
                }
                else
                {
                    $new_cron[] = $cur_crn;
                }
            }
            
            if($update_cron == 1)
            {
                $new_cron_text = implode("\n",$new_cron);
                file_put_contents('/tmp/crontab.txt', $new_cron_text.PHP_EOL);
                exec('crontab /tmp/crontab.txt');
            }
        }
        else
        {
        	if($tasks['processing'] == 1)
        	{
        		die("another process running");
        	}
        	else
        	{
      			//echo "importing";
      			$this->process($tasks);
        	}

        }
    }
    
    public function process($data)
    {
        
        
        $url = $data['url'];
        $skus = $data['skus'];
        $import_images = $data['images'];
        $active_products = $data['enable_products'];
        $skus = $this->change_std_toarray(json_decode($skus));
        
        $import_skus = array();
        $remaining_skus = array();
        $i = 0;
        foreach($skus as $s)
        {
            $i++;
            if($i<=IMPORT_AT_ONCE)
            {
                $import_skus[] = $s; 
            }
            else
            {
                $remaining_skus[] = $s;
            }
        }
        
        if(count($remaining_skus) == 0)
        {
            $status = 0;
            $rem_skus = "";
        }
        else
        {
            $status = 1;
            $rem_skus = json_encode($remaining_skus);
        }
        $skus = $import_skus;
        $processing = 1;
        $this->model_module_productify->update_database($data['import_id'],$status,$rem_skus,$processing);
        
        
        
        $xml = simplexml_load_file($url) or die("Could not load the URL file.Please Retry.");
        
        
        foreach($xml->products->product as $prd)
        {
            $upload = 0;
            //print_r($prd);
            //echo "<br />";
            $product = array();
            $product = array(
                            "product_code"=>"$prd->product_code",
                            "name"=>"$prd->product_name",
                            "brand"=>"$prd->brand",
                            "short_description"=>"$prd->short_description",
                            "detail_description"=>"$prd->detailed_description",
                            "warranty"=>"$prd->warranty",
                            "website_url"=>"$prd->website_url",
                            "import_images"=>$import_images,
                            "enable"=>$active_products
                           );
                           
            $categories = $prd->categories->category;
            $product['category'] = "$categories";
            $images = array();
            foreach($prd->media->image_url as $img)
			{
			    //print_r($img);
				$image_default = "false";
				foreach($img->attributes() as $a => $b)
				{
					$image_default = "$b";
				}
				$images[] = array("default"=>"$image_default","image_url"=>"$img");
			}
            $product['media'] = $images;
            //echo "<br />";
            $sku = array();
            
            $num_sku = 0;
            foreach($prd->skus->sku as $s)
            {
                $variants = array();
                $upload = 1;
                //print_r($s);
                $prd_sku =  "$s->id";
                if(in_array($prd_sku,$skus))
                {
                    $weight_units = preg_split('#(?<=\d)(?=[a-z])#i', $s->weight);
                    //print_r($weight_units);
                    $weight = $weight_units[0];
                    $weight_class = ($weight_units[1] == 'g')?2:1;
                    /*$sku = array(
                            "id"=>"$prd_sku",
                            "sale_price"=>"$s->sale_price",
                            "retail_price"=>"$s->retail_price",
                            "stock"=>"$s->stock",
                            "ean"=>"$s->ean",
                            "upc"=>"$s->upc",
                            "weight"=>"$weight",
                            "weight_class"=>"$weight_class"
                            );*/
                    $sku[$num_sku]['id'] = $prd_sku;
                    $sku[$num_sku]['sale_price'] = $s->sale_price;
                    $sku[$num_sku]['retail_price'] = $s->retail_price;
                    $sku[$num_sku]['stock'] = $s->stock;
                    $sku[$num_sku]['ean'] = $s->ean;
                    $sku[$num_sku]['upc'] = $s->upc;
                    $sku[$num_sku]['weight'] = $weight;
                    $sku[$num_sku]['weight_class'] = $weight_class;
                    
                    foreach($s->variants->variant as $var)
                    {
                        foreach($var->attributes() as $a=>$v)
                        {
                            $variants[] = array("$v"=>"$var");
                        }
                    }
                    $sku[$num_sku]['variants'] = $variants;
                    //$added[] = $this->addProduct($product);
                }
                else
                {
                    $upload = 0;
                }
                $num_sku++;
            }
            
            if($upload == 1)
            {
                $product['import_id'] = $data['import_id'];
                $product['sku'] = $sku;
                $added[] = $this->addProduct($product);
            }
        }
        
        $success = 0;
        $failed = 0;
        foreach($added as $r)
        {
            if($r == 1)
            {
                $success++;
            }
            else
            {
                $failed++;
            }
        }
        //echo "success = $success, failed = $failed<br />";
        //echo $data['email'].'<br />';
        //echo "status = ".$status.'<br />';
        if($status == 0 && $data['email'] != "")
        {
            $updated_prds = $this->model_module_productify->updated_prds($data['import_id']);
            $updated_products_string = "";
            $total_updated = count($updated_prds);
            if($total_updated > 0)
            {
                $updated_products_string .= "<strong>Updated products:</strong><br />";
                foreach($updated_prds as $upd)
                {
                    $updated_products_string .= $upd."<br />";
                }
            }
            //echo "sending email";
            $tot_added = (int)$data['imported'] + $success - $total_updated;
            $tot_failed = (int)$data['failed'] + $failed;
            $message = "<html>
                        <head>
                            <title>Data imported successfully</title>
                        </head>
                        <body>
                            <p>
                            Hi there,<br /><br />
                            The Productify Import has been successfully completed.<br /><br />
                            <strong>Details:</strong> <br />
                            Total records imported: ".$tot_added."<br />
                            Total records updated: ".$total_updated."<br />
                            Total records failed: ".$tot_failed."<br />
                            <br />".$updated_products_string."<br />
                            Please login into your store to see the imported Products.
                            Note, depending on preferences chosen, you may have to enable the Products 
                            or Categories within the admin to display the products in your store.
                            <br /><br />
                            Regards
                            </p>
                        </body>
                        </html>";
            $to = $data['email'];
            $subject = "Data imported successfully";
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'From:Productify.com<noreply@productify.com>';
            //echo mail($to, $subject, $message,$headers);
            $mail = new Mail();
            
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');     
            
            $mailText = html_entity_decode($message,ENT_QUOTES, 'UTF-8');
            
            $mail->setFrom('noreply@productify.com');
            $mail->setSender('Productify.com');
            $mail->setTo($to);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($mailText);
            $mail->send();
            
        }
        else
        {
            //echo "updating database<br />";
            $this->model_module_productify->update_records($data['import_id'],$success,$failed);
        }
    }
    
    private function addProduct($data)
    {
        $this->load->model('module/productify');
        
        $prd_name = $data['name'];
        $prd_sku = $data['sku'][0]['id'];
        
        $cat_name = $this->model_module_productify->check_product_exists($prd_name,$prd_sku,$data['import_id']);
        
        
        $cat = $data['category'];
        $brand = $data['brand'];
        $opt = array();
        foreach ($data['sku'] as $tmp_skus)
        {
            foreach ($tmp_skus['variants'] as $key=>$val)
            {
                $key = ucwords(strtolower($key));
                $opt[]["$key"] =$val;
            }
        }
        
        $cat_name = $this->model_module_productify->check_category($cat);
        $opt_ids = $this->model_module_productify->check_options($opt);
        $manufac = $this->model_module_productify->check_manufacturer($brand);
        $product_category = array();
        foreach($cat_name as $c)
        {
            $product_category[] = $c;
        }
        
        $product_option = array();
        if($opt != null)
        {
            $previous_key = "";
            $new_option_ids = array();
            $i=0;
            foreach($opt_ids as $optn)
            {
                foreach($optn as $o=>$v)
                {
                    if($o != "option_value_ids")
                    {
                        $new_option_ids[$i][$o] = $v;
                        if($o != $previous_key)
                        {
                            $new_option_ids[$i]["option_value_ids"][] = $optn['option_value_ids'];    
                        }
                        else
                        {
                            $i++;
                            $previous_key = $o;
                        }
                    }
                }
            }
            
            //print_r($new_option_ids);
            //die();
            
            foreach($new_option_ids as $op)
            {
                foreach($op as $o=>$v)
                {
                    //print_r($v);
                    if($o != "option_value_ids")
                    {
                        $product_option[$i]['product_option_id'] = "";
                        $product_option[$i]['name'] = $o;
                        $product_option[$i]['option_id'] = $v;
                        $product_option[$i]['type'] = "select";
                        $product_option[$i]['required'] = 1;
                    }
                    else
                    {
                        $j= 0;
                        foreach($v as $value)
                        {
                            foreach($value as $val)
                            {
                                $product_option[$i]['product_option_value'][$j]['option_value_id'] = $val;
                                $product_option[$i]['product_option_value'][$j]['product_option_value_id'] = "";
                                $product_option[$i]['product_option_value'][$j]['quantity'] = $data['sku'][$j]['stock'];
                                $product_option[$i]['product_option_value'][$j]['subtract'] = 1;
                                $product_option[$i]['product_option_value'][$j]['price_prefix'] = "";
                                $product_option[$i]['product_option_value'][$j]['price'] = "";
                                $product_option[$i]['product_option_value'][$j]['weight_prefix'] = "";
                                $product_option[$i]['product_option_value'][$j]['weight'] = "";
                                $product_option[$i]['product_option_value'][$j]['points_prefix'] = "";
                                $product_option[$i]['product_option_value'][$j]['points'] = "";
                            }
                            $j++;
                        }
                    }
                }
            $i++;
            }
        }
        //echo "<pre>";
//        print_r($product_option);
//        echo "</pre>";
        
            $this->load->model("tool/simpleimage");
            unset($product_image);
            if($data['import_images'] == 1)
            {
                if($data['media'] != null)
                {
                    $first_image = "";
                    $images = array();
                    foreach ($data['media'] as $i)
                    {
                        $image_array = explode("/",$i['image_url']);
                        $image = end($image_array);
                        $url = $i['image_url'];
                        $img = "./././image/data/".$image;
                        $images[] = "data/".$image;
                       
                        if($i['default'] == "true")
                        {
                            $first_image = $image;
                        }
                        $image_from_url = file_get_contents($i['image_url']);
                        $import_image = file_put_contents("$img", $image_from_url);
                        
                        if($import_image)
                        {
                           //resize and save uploaded
                           $this->model_tool_simpleimage->load($img) ;
                           $width = $this->model_tool_simpleimage->getWidth();
                           $height = $this->model_tool_simpleimage->getHeight();
                           
                           if($width > 1000 || $height > 1000)
                           {
                                if($width < $height)
                                {
                                    $this->model_tool_simpleimage->resizeToWidth(1000);
                                }
                                else
                                {
                                   $this->model_tool_simpleimage->resizeToHeight(1000); 
                                }
                           }
                           else
                           {
                                $this->model_tool_simpleimage->resizeToWidth($width);
                           }
                           
                            $this->model_tool_simpleimage->save($img);
                        }
                        else
                        {
                            die("<br />failed to import image");
                        }
                        
                        
                    }
                }
                
                
                $product_image = array();
                if(count($images) >= 1)
                {
                    for($i = 0;$i< count($images);$i++)
                    {
                        if("data/".$first_image == $images[$i])
                        {
                            continue;
                        }
                        $product_image[] = array("image"=>$images[$i],"sort_order"=>""); 
                    }
                }
            }
            $product_categories = explode('>',$cat);
            $prd_cat = end($product_categories);
            
        
        //insert the product
        $product = array(
        	"product_description"=>array("1"=>array("name"=>$data['name'],
                                                    "meta_description"=>$data['short_description'],
                                                    "meta_keyword"=>"",
                                                    "description"=>$data['detail_description'],
                                                    "tag"=>"")),
        	"model"=>($data['product_code'] != null)?$data['product_code']:$data['name'],
        	"sku"=>$data['sku'][0]['id'],
        	"upc"=>$data['sku'][0]['upc'],
        	"ean"=>$data['sku'][0]['ean'],
        	"jan"=>"",
        	"isbn"=>"",
        	"mpn"=>"",
        	"location"=>"",
        	"price"=>$data['sku'][0]['retail_price']/100,
        	"tax_class_id"=>0,
        	"quantity"=>$data['sku'][0]['stock'],
        	"minimum"=>"1",
        	"subtract"=>1,
        	"stock_status_id"=>5,
            "shipping"=>1,
            "keyword"=>"",
            "image"=>(isset($first_image))?"data/".$first_image:"",
            "date_available"=>date("Y-m-d",time() - 60 * 60 * 24),
            "height"=>"",
            "width"=>"",
            "length"=>"",
            "length_class_id"=>1,
            "weight"=>$data['sku'][0]['weight'],
            "weight_class_id"=>$data['sku'][0]['weight_class'],
            "status"=>$data['enable'],
            "sort_order"=>1,
            "manufacturer"=>($brand != "")?$brand:"",
            "manufacturer_id"=>"",
            "category"=>$prd_cat,
            "product_category"=>$product_category,
            'filter'=>"",
            "product_store"=>array("0"),
            "download"=>"",
            "related"=>"",
            "option"=>"",
            "product_option"=>$product_option,
            "product_special"=>array(array("customer_group_id"=>1,"priority"=>"","price"=>$data['sku'][0]['sale_price']/100,"date_start"=>"","date_end"=>"")),
            "points"=>"",
            "product_reward"=>array("1"=>array("points"=>"")),
            "product_layout"=>array(array("layout_id"=>""))
            );
            if(isset($product_image) && $product_image != null)
            {
                $product['product_image'] = $product_image;
            }
            if(isset($manufac) && $manufac != "")
            {
                $product['manufacturer_id'] = $manufac;
            }
            
         $insert = $this->model_module_productify->addProduct($product);
        $this->data['product'] = "$insert";
        if($insert != "")
        {
            //echo $insert;
            //die();
            return 1;
            
        }
        
    }//addProduct
    
    function change_std_toarray($array)
    {
        if (is_array($array))
        {
            foreach ($array as $key => $value)
            {
                if (is_array($value))
                {
                    $array[$key] = $this->change_std_toarray($value);
                }
                if ($value instanceof stdClass)
                {
                    $array[$key] = $this->change_std_toarray((array)$value);
                }
            }
        }
        if ($array instanceof stdClass)
        {
            return $this->change_std_toarray((array)$array);
        }
        return $array;
    }
    
    
    function test_email()
    {
         $message = "<html>
                        <head>
                            <title>testing email sending</title>
                        </head>
                        <body>
                            <p>
                            testemail,<br /><br />
                            This is a sample of email sending which may not be working at certain times.<br />
                            </p>
                        </body>
                        </html>";
         $to = "umesh.c@access-keys.com";
         $subject = "test email sending";
        //mail($to,$subject,$message);
        $mail = new Mail();
            
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');     
            
            $mailText = html_entity_decode($message,ENT_QUOTES, 'UTF-8');
            
            $mail->setFrom('noreply@productify.com');
            $mail->setSender('Productify.com');
            $mail->setTo($to);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($mailText);
            $mail->send();
    }
    
    
    public function import_all($data)
    {
        
        
        $url = $data['url'];
        $skus = $data['skus'];
        $import_images = $data['images'];
        $active_products = $data['enable_products'];
        $skus = $this->change_std_toarray(json_decode($skus));
        
        //$import_skus = array();
//        $remaining_skus = array();
//        $i = 0;
//        foreach($skus as $s)
//        {
//            $i++;
//            if($i<=IMPORT_AT_ONCE)
//            {
//                $import_skus[] = $s; 
//            }
//            else
//            {
//                $remaining_skus[] = $s;
//            }
//        }
//        
//        if(count($remaining_skus) == 0)
//        {
//            $status = 0;
//            $rem_skus = "";
//        }
//        else
//        {
//            $status = 1;
//            $rem_skus = json_encode($remaining_skus);
//        }
//        $skus = $import_skus;
//        $this->model_module_productify->update_database($data['import_id'],$status,$rem_skus);
        
        
        
        $xml = simplexml_load_file($url) or die("Could not load the URL file.Please Retry.");
        
        
        foreach($xml->products->product as $prd)
        {
            //print_r($prd);
            //echo "<br />";
            $product = array();
            $product = array(
                            "product_code"=>"$prd->product_code",
                            "name"=>"$prd->product_name",
                            "brand"=>"$prd->brand",
                            "short_description"=>"$prd->short_description",
                            "detail_description"=>"$prd->detailed_description",
                            "warranty"=>"$prd->warranty",
                            "website_url"=>"$prd->website_url",
                            "import_images"=>$import_images,
                            "enable"=>$active_products
                           );
                           
            $categories = $prd->categories->category;
            $product['category'] = "$categories";
            $images = array();
            foreach($prd->media->image_url as $img)
			{
			    //print_r($img);
				$image_default = "false";
				foreach($img->attributes() as $a => $b)
				{
					$image_default = "$b";
				}
				$images[] = array("default"=>"$image_default","image_url"=>"$img");
			}
            $product['media'] = $images;
            //echo "<br />";
            foreach($prd->skus->sku as $s)
            {
                //print_r($s);
                $prd_sku =  "$s->id";
                $sku = array();
                if(in_array($prd_sku,$skus))
                {
                    $weight_units = preg_split('#(?<=\d)(?=[a-z])#i', $s->weight);
                    //print_r($weight_units);
                    $weight = $weight_units[0];
                    $weight_class = ($weight_units[1] == 'g')?2:1;
                    $sku = array(
                            "id"=>"$prd_sku",
                            "sale_price"=>"$s->sale_price",
                            "retail_price"=>"$s->retail_price",
                            "stock"=>"$s->stock",
                            "ean"=>"$s->ean",
                            "upc"=>"$s->upc",
                            "weight"=>"$weight",
                            "weight_class"=>"$weight_class"
                            );
                    $variants = array();
                    foreach($s->variants->variant as $var)
                    {
                        foreach($var->attributes() as $a=>$v)
                        {
                            $variants[] = array("$v"=>"$var");
                        }
                    }
                    
                    $sku['variants'] = $variants;
                    $product['sku'] = $sku;
                    $added[] = $this->addProduct($product);
                }
            }
        }
        
        $success = 0;
        $failed = 0;
        foreach($added as $r)
        {
            if($r == 1)
            {
                $success++;
            }
            else
            {
                $failed++;
            }
        }
        
        //echo $data['email'].'<br />';
        //echo "status = ".$status.'<br />';
        if($data['email'] != "")
        {
            //echo "sending email";
            $tot_added = (int)$data['imported'] + $success;
            $tot_failed = (int)$data['failed'] + $failed;
            $message = "<html>
                        <head>
                            <title>Data imported successfully</title>
                        </head>
                        <body>
                            <p>
                            Hi there,<br /><br />
                            The Productify Import has been successfully completed.<br /><br />
                            Details: <br />
                            Total records imported: ".$tot_added."<br />
                            Total records updated: 2<br />
                            Total records failed: ".$tot_failed."<br />
                            
                            Updated products:<br />
                            Glass Tagine Vase-Two Tone-Red/Orange-28cm SM94-GLASS
                            Glass Tagine Vase-Two Tone-Red/Orange-28cm SM94-CERAMIC
                            Glass Tagine Vase-Two Tone-Blue-28cm SM95
                            Tagine 6.5inch - Cream CG291
                            Glass Tagine Vase-Two Tone-Brown Orange-45cm SM93
                            Tagine 8.5 inch - Cream CG290
                            Tagine 6.5inch - Teal CG321
                            
                            Please login into your store to see the imported Products. Note, depending on preferences chosen, you may have to enable the Products or Categories within the admin to display the products in your store.
                            Thank you!
                            </p>
                        </body>
                        </html>";
            $to = $data['email'];
            $subject = "Data imported successfully";
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            $headers .= 'From:Productify.com<noreply@productify.com>';
            //echo mail($to, $subject, $message,$headers);
            $mail = new Mail();
            
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');     
            
            $mailText = html_entity_decode($message,ENT_QUOTES, 'UTF-8');
            
            $mail->setFrom('noreply@productify.com');
            $mail->setSender('Productify.com');
            $mail->setTo($to);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($mailText);
            $mail->send();
            
            $this->model_module_productify->update_database($data['import_id'],0,"");
            
        }
        else
        {
            echo "no need to send email. all downloaded";
            //echo "updating database<br />";
//            $this->model_module_productify->update_records($data['import_id'],$success,$failed);
        }
    }
    
    
    public function check_import()
    {
            $output = shell_exec('crontab -l');
            $current_cron_array = explode("\n",$output);
            $current_url = $this->url->link("module/productify");
            echo $current_url."<br />";
            //echo "Cron Status<br />";
            $new_cron = array();
            $update_cron = 0;
            foreach($current_cron_array as $cur_crn)
            {
                //echo strpos($cur_crn,$current_url)."<br />";
                if(strpos($cur_crn,$current_url) != false)
                {
                    $cron_stat = 1;
                    break;
                }
                else
                {
                    $cron_stat = 0;
                }
            }
            echo ($cron_stat == 1)?"cron is created":"cron is not created" ;
            $this->load->model('module/productify');
            $task = $this->model_module_productify->test_cron();
            echo "<br />current task<br /><pre>";
            if($task != null)
            {
                ?>
                <table>
                    <tr>
                        <td colspan="2">Import Details</td>
                    </tr>
                    <tr>
                        <td>Date Added</td>
                        <td><?php echo date("Y-m-d H:i:s",strtotime($task['date_added']))?></td>
                    </tr>
                    <tr>
                        <td>Status</td>
                        <td><?php echo ($task['status'] == 1)?"Active":"Inactive/Completed"?></td>
                    </tr>
                    <tr>
                        <td>XML Feed URL</td>
                        <td><?php echo $task['url']?></td>
                    </tr>
                    <tr>
                        <td>Total Products to import</td>
                        <td><?php echo $task['total_import']?></td>
                    </tr>
                    <tr>
                        <td>Import Images</td>
                        <td><?php echo ($task['images'] == 1)?"True":"False"?></td>
                    </tr>
                    <tr>
                        <td>Make Imports Active</td>
                        <td><?php echo ($task['enable_products'] == 1)?"True":"False" ?></td>
                    </tr>
                    <tr>
                        <td>Current Condition</td>
                        <td><?php echo ($task['processing'] ==1)?"Importing":"Waiting"?></td>
                    </tr>
                    <tr>
                        <td>Reporting email</td>
                        <td><?php echo $task['email']?></td>
                    </tr>
                </table>
                
                <?php
            }
            else
            {
                echo "no task<br />";
            }
            echo "</pre>";
    }
    
    public function check_pending_processes()
    {
        $this->load->model("module/productify");
        $process = $this->model_module_productify->check_pending();
        if($process != null)
        {
            $import_status = ($process['pendig_testing'] == "remaining")
                                ?"The import process will continue but you might not get some products imported. So the total imported imported products might differ than the number that appears on the response email."
                                :"The import has been removed from the cron job list. Please retry the import process.<br /><br />";
            $message = "<html>
                        <head>
                            <title>Productify Import Notice</title>
                        </head>
                        <body>
                            <p>
                            Hi there,<br /><br />
                            The import you started at ".$process['date_added']." has failed to continue due to some internal errors.<br /><br />
                            Import Status: ".$process['imported']." (successful) and ".$process['failed']." (failed)<br /><br />".
                            $import_status
                            ."<br /><br />Thank you!
                            </p>
                        </body>
                        </html>";
            $to = $process['email'];
            $subject = "Products import has failed";
            //mail($to,$subject,$message);
            $mail = new Mail();
            
            $mail->protocol = $this->config->get('config_mail_protocol');
            $mail->parameter = $this->config->get('config_mail_parameter');
            $mail->hostname = $this->config->get('config_smtp_host');
            $mail->username = $this->config->get('config_smtp_username');
            $mail->password = $this->config->get('config_smtp_password');
            $mail->port = $this->config->get('config_smtp_port');
            $mail->timeout = $this->config->get('config_smtp_timeout');     
            
            $mailText = html_entity_decode($message,ENT_QUOTES, 'UTF-8');
            
            $mail->setFrom('noreply@productify.com');
            $mail->setSender('Productify.com');
            $mail->setTo($to);
            $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
            $mail->setHtml($mailText);
            $mail->send();
            
        }
    }
    
}
?>