<?php
################################################################################################
#  Productify XML Importer for Opencart 1.5.1.x From Productify http://productify.com		   #
################################################################################################
class ControllerModuleProductify extends Controller {
	
	private $error = array(); 
    
    public function install() {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "productify_import` (
          `import_id` int(11) NOT NULL AUTO_INCREMENT,
          `date_added`  datetime NOT NULL,
          `modified_time` TIMESTAMP NOT NULL,
          `status` tinyint(1) NOT NULL,
          `url` varchar(255) NOT NULL,
          `skus` text NOT NULL,
          `email` varchar(255) NOT NULL,
          `images` tinyint(1) NOT NULL,
          `enable_products` tinyint(1) NOT NULL,
          `total_import` int(11),
          `imported` int(11),
          `failed` int(11),
          `updated_products` text, 
          `processing` tinyint(1),
          PRIMARY KEY (`import_id`)
        )");
 }
 public function uninstall() {
    $this->db->query("DROP TABLE IF EXISTS`" . DB_PREFIX . "productify_import` ");
 }
	
	public function index($result = "") {   
		//Load the language file for this module
		$this->load->language('module/productify');
        //$this->load->model("module/productify");

		//Set the title from the language file $_['heading_title'] string
		$this->document->setTitle($this->language->get('heading_title'));
		
		//Load the settings model. You can also add any other models you want to load here.
		$this->load->model('setting/setting');
		
		//Save the settings if the user has submitted the admin form (ie if someone has pressed save).
		/*if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('productify', $this->request->post);		
					
			$this->session->data['success'] = $this->language->get('text_success');
						
			$this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}*/
        if(isset($this->request->post['search']))
        {
            $url = $this->request->post['url'];
            $this->data['url'] = $url;
            $xml_url = strpos($url,".xml");
            $http_url = strpos($url,"http");
            
            if($http_url === 0 && $xml_url !== false)
            {
                $file_headers = @get_headers($url);
            }
            
            if($xml_url === false)
            {
                $this->data['error'] = "Please Provide a proper XML feed URL";
            }
            elseif($http_url !== 0)
            {
                $this->data['error'] = "Please Provide a proper XML feed URL";
            }
            elseif($file_headers[0] == 'HTTP/1.1 404 Not Found') {
                $this->data['error'] = "The feed URL entered is incorrect. Feed not found or invalid format";
            }
            elseif(simplexml_load_string(file_get_contents($url)))
            {
                $xml = simplexml_load_string(file_get_contents($url));
                $feed_validity = 1;
                $test_product = $xml->products->product;
                //echo "<pre>";
                //print_r($test_product);
                if(! isset($test_product->product_code)){$feed_validity = 0;}
                if(! isset($test_product->product_name)){$feed_validity = 0;}
                if(! isset($test_product->brand)){$feed_validity = 0;}
                if(! isset($test_product->short_description)){$feed_validity = 0;}
                if(! isset($test_product->detailed_description)){$feed_validity = 0;}
                if(! isset($test_product->categories)){$feed_validity = 0;}
                if(! isset($test_product->categories->category)){$feed_validity = 0;}
                if(! isset($test_product->skus)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->id)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->retail_price)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->sale_price)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->ean)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->upc)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->weight)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->stock)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->variants)){$feed_validity = 0;}
                if(! isset($test_product->skus->sku->variants->variant)){$feed_validity = 0;}
                if(! isset($test_product->media)){$feed_validity = 0;}
                if(! isset($test_product->media->image_url)){$feed_validity = 0;}
                
                //echo "feed status = ".$feed_validity;echo "</pre>";die();
                if($feed_validity == 1)
                {
                    $this->data['xml'] = $xml;
                }
                else
                {
                    $this->data['error'] = "The feed URL entered is incorrect. Feed not found or invalid format";
                }
                
            }
            else
            {
                $this->data['error'] = "The feed URL entered is incorrect. Feed not found or invalid format";
                //echo "Failed to load the url<br />Maybe the url you provided is not a xml feed";
                //die();
            }
            
        }
        
        if(isset($this->request->post['save_products']))
        {
            $this->data['step'] = 4;
            $step = 4;
        }
        if($result != "")
        {
            $this->data['step'] = 5;
            $success = 0;
            $failed = 0;
            foreach($result as $r)
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
            
            $this->data['success'] = $success;
            $this->data['failure'] = $failed;
        }
        

		//This is how the language gets pulled through from the language file.
		//
		// If you want to use any extra language items - ie extra text on your admin page for any reason,
		// then just add an extra line to the $text_strings array with the name you want to call the extra text,
		// then add the same named item to the $_[] array in the language file.
		//
		// 'my_module_example' is added here as an example of how to add - see admin/language/english/module/productify.php for the
		// other required part.
		
		$text_strings = array(
				'heading_title',
				'text_enabled',
				'text_disabled',
				'text_content_top',
				'text_content_bottom',
				'text_column_left',
				'text_column_right',
				'entry_layout',
				'entry_limit',
				'entry_image',
				'entry_position',
				'entry_status',
				'entry_sort_order',
				'button_save',
				'button_cancel',
				'button_add_module',
				'button_remove',
				'entry_example' //this is an example extra field added
		);
		
		foreach ($text_strings as $text) {
			$this->data[$text] = $this->language->get($text);
		}
		//END LANGUAGE
		
		//The following code pulls in the required data from either config files or user
		//submitted data (when the user presses save in admin). Add any extra config data
		// you want to store.
		//
		// NOTE: These must have the same names as the form data in your productify.tpl file
		//
		$config_data = array(
				'my_module_example' //this becomes available in our view by the foreach loop just below.
		);
		
		foreach ($config_data as $conf) {
			if (isset($this->request->post[$conf])) {
				$this->data[$conf] = $this->request->post[$conf];
			} else {
				$this->data[$conf] = $this->config->get($conf);
			}
		}
	
		//This creates an error message. The error['warning'] variable is set by the call to function validate() in this controller (below)
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		//SET UP BREADCRUMB TRAIL. YOU WILL NOT NEED TO MODIFY THIS UNLESS YOU CHANGE YOUR MODULE NAME.
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
		$this->data['action'] = $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
        
        
        $this->data['import_new'] = $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL');

	
		//This code handles the situation where you have multiple instances of this module, for different layouts.
		$this->data['modules'] = array();
		
		if (isset($this->request->post['my_module_module'])) {
			$this->data['modules'] = $this->request->post['my_module_module'];
		} elseif ($this->config->get('my_module_module')) { 
			$this->data['modules'] = $this->config->get('my_module_module');
		}		

		$this->load->model('design/layout');
		
		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		//Choose which template file will be used to display this request.
		$this->template = 'module/productify.tpl';
		$this->children = array(
			'common/header',
			'common/footer',
		);
                
        if(isset($step))
        {
                $url = $this->request->post['url'];
                $skus = array_unique($this->request->post['skus']);
                $import_images = (isset($this->request->post['import_images']))?$this->request->post['import_images']:0;
                $active_products = (isset($this->request->post['active_products']))?$this->request->post['active_products']:0;
                $email = $this->request->post['email'];
                $total_import = $this->request->post['total_import'];
            //if(1){
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
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
                
                $message = "<html>
                        <head>
                            <title>Data imported successfully</title>
                        </head>
                        <body>
                            <p>
                            Hi there,<br /><br />
                            Your import has been completed.<br /><br />
                            Import Status: ".$tot_added." (successful) and ".$tot_failed." (failed)<br /><br />
                            Thank you!
                            </p>
                        </body>
                        </html>";
                $to = $email;
                $subject = "Data imported successfully";
                
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
                
                $this->data['message'] = "Import process completed. <br />pleasae make sure to confirm the categories, product options and their images"; 
                $this->data['step'] = 5;
                
            } else {
                //$total_import = count($skus);
                $skus = json_encode($skus);
                
                $this->load->model("module/productify");
                $add_to_import = $this->model_module_productify->add_to_import($url,$skus,$import_images,$active_products,$email,$total_import);
                if($add_to_import)
                {
                    $new_url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG); 
                    $cron_url = $new_url->link("module/productify");
                    
                    //exec("curl --silent $cron_url");
                    
                    $output = shell_exec('crontab -l');
                    
                    if(strpos($output,$cron_url) === false)
                    {
                        file_put_contents('/tmp/crontab.txt', $output."*/1 * * * * wget -q -O /dev/null $cron_url".PHP_EOL);
                        exec('crontab /tmp/crontab.txt');
                    }
                    $this->data['message'] = "Your import has been added to the cron. It may take many minutes to complete depending upon the number of products you selected to import. You will receive an email when the import process completes.<br />After completion of the process please make sure to confirm the categories, product options and their images.<br />";
                    $this->data['step'] = 5;
                    
                }
                else
                {
                    $this->data['message'] = "Failed to add to cron Please Retry";
                    $this->data['step'] = 5;
                }
            }
        }

		//Send the output.
        $this->response->setOutput($this->render());
	}
	
	/*
	 * 
	 * This function is called to ensure that the settings chosen by the admin user are allowed/valid.
	 * You can add checks in here of your own.
	 * 
	 */
	private function validate() {
		if (!$this->user->hasPermission('modify', 'module/productify')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}
    
    private function addProduct($data)
    {
        //echo "<pre>";
        //print_r($data);
        
        $cat = $data['category'];
        $brand = $data['brand'];
        $opt = array();
        foreach ($data['sku']['variants'] as $key=>$val)
        {
            $key = ucwords(strtolower($key));
            $opt["$key"] =$val; 
        }
        
        
        
        $this->load->model('module/productify');
        
        
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
            $i = 0;
            foreach($opt_ids as $op)
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
                        $product_option[$i]['required'] = 0;
                    }
                    else
                    {
                        $j= 0;
                        foreach($v as $key=>$val)
                        {
                            $product_option[$i]['product_option_value'][$j]['option_value_id'] = $val;
                            $product_option[$i]['product_option_value'][$j]['product_option_value_id'] = "";
                            $product_option[$i]['product_option_value'][$j]['quantity'] = $data['sku']['stock'];
                            $product_option[$i]['product_option_value'][$j]['subtract'] = 1;
                            $product_option[$i]['product_option_value'][$j]['price_prefix'] = "";
                            $product_option[$i]['product_option_value'][$j]['price'] = "";
                            $product_option[$i]['product_option_value'][$j]['weight_prefix'] = "";
                            $product_option[$i]['product_option_value'][$j]['weight'] = "";
                            $product_option[$i]['product_option_value'][$j]['points_prefix'] = "";
                            $product_option[$i]['product_option_value'][$j]['points'] = "";
                            $j++;
                        }
                    }
                }
            $i++;
            }
        }
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
                        $img = "../image/data/".$image;
                        copy($url,$img);
                        //file_put_contents($img, file_get_contents($url));flush();
                        //$this->saveImage($i['image_url'],$image);
                        $images[] = "data/".$image;
                       
                       //resize and save uploaded
                       $this->model_tool_simpleimage->load("../image/data/".$image) ;
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
                       
                        if($i['default'] == "true")
                        {
                            $first_image = $image;
                        }
                    }
                }
                
                
                $product_image = array();
                if(count($images)>1)
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
        	"sku"=>$data['sku']['id'],
        	"upc"=>$data['sku']['upc'],
        	"ean"=>$data['sku']['ean'],
        	"jan"=>"",
        	"isbn"=>"",
        	"mpn"=>"",
        	"location"=>"",
        	"price"=>$data['sku']['retail_price']/100,
        	"tax_class_id"=>0,
        	"quantity"=>$data['sku']['stock'],
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
            "weight"=>$data['sku']['weight'],
            "weight_class_id"=>$data['sku']['weight_class'],
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
            "product_special"=>array(array("customer_group_id"=>1,"priority"=>"","price"=>$data['sku']['sale_price']/100,"date_start"=>"","date_end"=>"")),
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
    
    public function completed($result = "")
    {
        //Load the language file for this module
		$this->load->language('module/productify');

		//Set the title from the language file $_['heading_title'] string
		$this->document->setTitle($this->language->get('heading_title'));
		
		//Load the settings model. You can also add any other models you want to load here.
		$this->load->model('setting/setting');
		
		//Save the settings if the user has submitted the admin form (ie if someone has pressed save).
		
        if($result != "")
        {
            $this->data['step'] = 5;
            $success = 0;
            $failed = 0;
            foreach($result as $r)
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
            
            $this->data['success'] = $success;
            $this->data['failure'] = $failed;
        }
        

		//This is how the language gets pulled through from the language file.
		//
		// If you want to use any extra language items - ie extra text on your admin page for any reason,
		// then just add an extra line to the $text_strings array with the name you want to call the extra text,
		// then add the same named item to the $_[] array in the language file.
		//
		// 'my_module_example' is added here as an example of how to add - see admin/language/english/module/productify.php for the
		// other required part.
		
		$text_strings = array(
				'heading_title',
				'text_enabled',
				'text_disabled',
				'text_content_top',
				'text_content_bottom',
				'text_column_left',
				'text_column_right',
				'entry_layout',
				'entry_limit',
				'entry_image',
				'entry_position',
				'entry_status',
				'entry_sort_order',
				'button_save',
				'button_cancel',
				'button_add_module',
				'button_remove',
				'entry_example' //this is an example extra field added
		);
		
		foreach ($text_strings as $text) {
			$this->data[$text] = $this->language->get($text);
		}
		//END LANGUAGE
		
		//The following code pulls in the required data from either config files or user
		//submitted data (when the user presses save in admin). Add any extra config data
		// you want to store.
		//
		// NOTE: These must have the same names as the form data in your productify.tpl file
		//
		$config_data = array(
				'my_module_example' //this becomes available in our view by the foreach loop just below.
		);
		
		foreach ($config_data as $conf) {
			if (isset($this->request->post[$conf])) {
				$this->data[$conf] = $this->request->post[$conf];
			} else {
				$this->data[$conf] = $this->config->get($conf);
			}
		}
	
		//This creates an error message. The error['warning'] variable is set by the call to function validate() in this controller (below)
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		//SET UP BREADCRUMB TRAIL. YOU WILL NOT NEED TO MODIFY THIS UNLESS YOU CHANGE YOUR MODULE NAME.
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
		$this->data['action'] = $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
        
        
        $this->data['import_new'] = $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL');

	
		//This code handles the situation where you have multiple instances of this module, for different layouts.
		$this->data['modules'] = array();
		
		if (isset($this->request->post['my_module_module'])) {
			$this->data['modules'] = $this->request->post['my_module_module'];
		} elseif ($this->config->get('my_module_module')) { 
			$this->data['modules'] = $this->config->get('my_module_module');
		}		

		$this->load->model('design/layout');
		
		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		//Choose which template file will be used to display this request.
		$this->template = 'module/productify.tpl';
		$this->children = array(
			'common/header',
			'common/footer',
		);

		//Send the output.
		$this->response->setOutput($this->render());
    }
    
    public function processing()
    {
        //Load the language file for this module
		$this->load->language('module/productify');

		//Set the title from the language file $_['heading_title'] string
		$this->document->setTitle($this->language->get('heading_title'));
		
		//Load the settings model. You can also add any other models you want to load here.
		$this->load->model('setting/setting');
		
		//Save the settings if the user has submitted the admin form (ie if someone has pressed save).
		
        $this->data['step'] = 5;
		//This is how the language gets pulled through from the language file.
		//
		// If you want to use any extra language items - ie extra text on your admin page for any reason,
		// then just add an extra line to the $text_strings array with the name you want to call the extra text,
		// then add the same named item to the $_[] array in the language file.
		//
		// 'my_module_example' is added here as an example of how to add - see admin/language/english/module/productify.php for the
		// other required part.
		
		$text_strings = array(
				'heading_title',
				'text_enabled',
				'text_disabled',
				'text_content_top',
				'text_content_bottom',
				'text_column_left',
				'text_column_right',
				'entry_layout',
				'entry_limit',
				'entry_image',
				'entry_position',
				'entry_status',
				'entry_sort_order',
				'button_save',
				'button_cancel',
				'button_add_module',
				'button_remove',
				'entry_example' //this is an example extra field added
		);
		
		foreach ($text_strings as $text) {
			$this->data[$text] = $this->language->get($text);
		}
		//END LANGUAGE
		
		//The following code pulls in the required data from either config files or user
		//submitted data (when the user presses save in admin). Add any extra config data
		// you want to store.
		//
		// NOTE: These must have the same names as the form data in your productify.tpl file
		//
		$config_data = array(
				'my_module_example' //this becomes available in our view by the foreach loop just below.
		);
		
		foreach ($config_data as $conf) {
			if (isset($this->request->post[$conf])) {
				$this->data[$conf] = $this->request->post[$conf];
			} else {
				$this->data[$conf] = $this->config->get($conf);
			}
		}
	
		//This creates an error message. The error['warning'] variable is set by the call to function validate() in this controller (below)
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
		
		//SET UP BREADCRUMB TRAIL. YOU WILL NOT NEED TO MODIFY THIS UNLESS YOU CHANGE YOUR MODULE NAME.
  		$this->data['breadcrumbs'] = array();

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => false
   		);

   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
   		$this->data['breadcrumbs'][] = array(
       		'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL'),
      		'separator' => ' :: '
   		);
		
		$this->data['action'] = $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL');
		
		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');
        
        
        $this->data['import_new'] = $this->url->link('module/productify', 'token=' . $this->session->data['token'], 'SSL');

	
		//This code handles the situation where you have multiple instances of this module, for different layouts.
		$this->data['modules'] = array();
		
		if (isset($this->request->post['my_module_module'])) {
			$this->data['modules'] = $this->request->post['my_module_module'];
		} elseif ($this->config->get('my_module_module')) { 
			$this->data['modules'] = $this->config->get('my_module_module');
		}		

		$this->load->model('design/layout');
		
		$this->data['layouts'] = $this->model_design_layout->getLayouts();

		//Choose which template file will be used to display this request.
		$this->template = 'module/productify.tpl';
		$this->children = array(
			'common/header',
			'common/footer',
		);

		//Send the output.
		$this->response->setOutput($this->render());
    }
    /*
    private function saveImage($urlImage, $title)
    {

        $fullpath = '../image/data/'.$title;
        $ch = curl_init ($urlImage);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        $rawdata=curl_exec($ch);
        curl_close ($ch);
        if(file_exists($fullpath)){
            unlink($fullpath);
        }
        $fp = fopen($fullpath,'x');
        $r = fwrite($fp, $rawdata);
    
        $this->setMemoryLimit($fullpath);
    
        fclose($fp);
    
        return $r;
    }
    
    private function setMemoryLimit($filename){
       set_time_limit(50);
       $maxMemoryUsage = 258;
       $width  = 0;
       $height = 0;
       $size   = ini_get('memory_limit');
    
       list($width, $height) = getimagesize($filename);
       $size = $size + floor(($width * $height * 4 * 1.5 + 1048576) / 1048576);
    
       if ($size > $maxMemoryUsage) $size = $maxMemoryUsage;
    
       ini_set('memory_limit',$size.'M');
    
    }
    */
    
    public function process($data)
    {
        $url = $data['url'];
        $skus = $data['skus'];
        $import_images = $data['import_images'];
        $active_products = $data['active_products'];
        
        $xml = simplexml_load_file($url) or die("Could not load the URL file.Please Retry.");
        //echo "<pre>";
        
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
                    $sku = array(
                            "id"=>"$prd_sku",
                            "sale_price"=>"$s->sale_price",
                            "retail_price"=>"$s->retail_price",
                            "stock"=>"$s->stock",
                            "ean"=>"$s->ean",
                            "upc"=>"$s->upc",
                            "weight"=>"$s->weight"
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
        
        $message = "Completed, the product import process.";
        $to = $data['email'];
        $subject = "Productify Processing Complete";
        //mail($to, $subject, $message);
        $mail = new Mail();
        
        $mail->protocol = $this->config->get('config_mail_protocol');
        $mail->parameter = $this->config->get('config_mail_parameter');
        $mail->hostname = $this->config->get('config_smtp_host');
        $mail->username = $this->config->get('config_smtp_username');
        $mail->password = $this->config->get('config_smtp_password');
        $mail->port = $this->config->get('config_smtp_port');
        $mail->timeout = $this->config->get('config_smtp_timeout');     
        
        $mailText = html_entity_decode($message,ENT_QUOTES, 'UTF-8');
        
        //$mail->setFrom($this->request->post['email']);
        //$mail->setSender('Productify Xml Import');
        $mail->setTo($to);
        $mail->setSubject(html_entity_decode($subject, ENT_QUOTES, 'UTF-8'));
        $mail->setHtml($mailText);
        
        $mail->send();
        
    }
}
?>