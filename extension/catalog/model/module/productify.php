<?php
################################################################################################
#  Productify XML Importer for Opencart 1.5.1.x From Productify http://productify.com		   #
################################################################################################
    class ModelModuleProductify extends Model
    {
        public function test_cron()
        {
            $task = $this->db->query("Select * from ".DB_PREFIX."productify_import where status = 1 order by import_id asc, status desc LIMIT 1 OFFSET 0");
            
            if($task->num_rows  == 0)
            {
                return null;
            }
            else
            {
            	return $task->row;
            }
        }
        
        public function update_database($imp_id,$status,$rem_skus,$processing)
        {
            $this->db->query("update ".DB_PREFIX."productify_import set status = $status, skus = '$rem_skus', processing = $processing where import_id = $imp_id");
        }
        
        public function update_productify_import()
        {
            
            //$this->db->query("Delete from ".DB_PREFIX."productify_import where status = 0");
        }
        
        public function update_records($imp_id,$added,$failed)
        {
            $current_det = $this->db->query("select imported, failed from ".DB_PREFIX."productify_import where import_id = $imp_id");
            $det = $current_det->row;
            //print_r($det);
            $old_imported = $det['imported'];
            $old_failed = $det['failed'];
            $new_imported = (int)$old_imported + (int)$added;
            $new_failed = (int)$old_failed + (int)$failed;
             
            $this->db->query("update ".DB_PREFIX."productify_import set imported = $new_imported, failed = $new_failed, processing = 0 where import_id = $imp_id");
        }
        
        
        public function addCategory($data) {
    	   $this->db->query("INSERT INTO " . DB_PREFIX . "category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', `column` = '" . (int)$data['column'] . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', date_modified = NOW(), date_added = NOW()");
    
    		$category_id = $this->db->getLastId();
    				
    		if (isset($data['image'])) {
    			$this->db->query("UPDATE " . DB_PREFIX . "category SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE category_id = '" . (int)$category_id . "'");
    		}
    		
    		foreach ($data['category_description'] as $language_id => $value) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "category_description SET category_id = '" . (int)$category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', description = '" . $this->db->escape($value['description']) . "'");
    		}
    
    		// MySQL Hierarchical Data Closure Table Pattern
    		$level = 0;
    		
    		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "category_path` WHERE category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");
    		
    		foreach ($query->rows as $result) {
    			$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
    			
    			$level++;
    		}
    		
    		$this->db->query("INSERT INTO `" . DB_PREFIX . "category_path` SET `category_id` = '" . (int)$category_id . "', `path_id` = '" . (int)$category_id . "', `level` = '" . (int)$level . "'");
    
    		if (isset($data['category_filter'])) {
    			foreach ($data['category_filter'] as $filter_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "category_filter SET category_id = '" . (int)$category_id . "', filter_id = '" . (int)$filter_id . "'");
    			}
    		}
    				
    		if (isset($data['category_store'])) {
    			foreach ($data['category_store'] as $store_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_store SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "'");
    			}
    		}
    		
    		// Set which layout to use with this category
    		if (isset($data['category_layout'])) {
    			foreach ($data['category_layout'] as $store_id => $layout) {
    				if ($layout['layout_id']) {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "category_to_layout SET category_id = '" . (int)$category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout['layout_id'] . "'");
    				}
    			}
    		}
    						
    		if ($data['keyword']) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'category_id=" . (int)$category_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
    		}
    		
    		//$this->cache->delete('category');
            return $category_id;
    	}
        
        public function addOption($data) {
    	   $this->db->query("INSERT INTO `" . DB_PREFIX . "option` SET type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "'");
    		
    		$option_id = $this->db->getLastId();
    		
    		foreach ($data['option_description'] as $language_id => $value) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . (int)$option_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
    		}
            $opt_val_ids = array();
    
    		if (isset($data['option_value'])) {
    			foreach ($data['option_value'] as $option_value) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
    				
    				$option_value_id = $this->db->getLastId();
                    
    				
    				foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$language_id . "', option_id = '" . (int)$option_id . "', name = '" . $this->db->escape($option_value_description['name']) . "'");
                        $opt_val_ids[$this->db->escape($option_value_description['name'])] = (int)$option_value_id;
    				}
    			}
    		}
            $result = $this->db->query("select name from `".DB_PREFIX."option_description` where option_id = '" . (int)$option_id . "'");
            //print_r($result);
            $name = $result->row['name'];
            //die();
            
            return array($name=>$option_id,"option_value_ids"=>$opt_val_ids);
    	}
        
        public function editOption($option_id, $data) {
    		$this->db->query("UPDATE `" . DB_PREFIX . "option` SET type = '" . $this->db->escape($data['type']) . "', sort_order = '" . (int)$data['sort_order'] . "' WHERE option_id = '" . (int)$option_id . "'");
    
    		$this->db->query("DELETE FROM " . DB_PREFIX . "option_description WHERE option_id = '" . (int)$option_id . "'");
    
    		foreach ($data['option_description'] as $language_id => $value) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "option_description SET option_id = '" . (int)$option_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "'");
    		}
    				
    		$this->db->query("DELETE FROM " . DB_PREFIX . "option_value WHERE option_id = '" . (int)$option_id . "'");
    		$this->db->query("DELETE FROM " . DB_PREFIX . "option_value_description WHERE option_id = '" . (int)$option_id . "'");
    		$opt_val_ids = array();
    		if (isset($data['option_value'])) {
    			foreach ($data['option_value'] as $option_value) {
    				if ($option_value['option_value_id']) {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_value_id = '" . (int)$option_value['option_value_id'] . "', option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
    				} else {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value SET option_id = '" . (int)$option_id . "', image = '" . $this->db->escape(html_entity_decode($option_value['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$option_value['sort_order'] . "'");
    				}
    				
    				$option_value_id = $this->db->getLastId();
    				
    				foreach ($option_value['option_value_description'] as $language_id => $option_value_description) {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "option_value_description SET option_value_id = '" . (int)$option_value_id . "', language_id = '" . (int)$language_id . "', option_id = '" . (int)$option_id . "', name = '" . $this->db->escape($option_value_description['name']) . "'");
                        $opt_val_ids[$this->db->escape($option_value_description['name'])] = (int)$option_value_id;
    				}
    			}
    		}
            $result = $this->db->query("select name from `".DB_PREFIX."option_description` where option_id = '" . (int)$option_id . "'");
            //print_r($result);
            $name = $result->row['name'];
            //die();
            
            return array($name=>$option_id,"option_value_ids"=>$opt_val_ids);
    	}
        
        public function addProduct($data) {
            $this->db->query("INSERT INTO " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', upc = '" . $this->db->escape($data['upc']) . "', ean = '" . $this->db->escape($data['ean']) . "', jan = '" . $this->db->escape($data['jan']) . "', isbn = '" . $this->db->escape($data['isbn']) . "', mpn = '" . $this->db->escape($data['mpn']) . "', location = '" . $this->db->escape($data['location']) . "', quantity = '" . (int)$data['quantity'] . "', minimum = '" . (int)$data['minimum'] . "', subtract = '" . (int)$data['subtract'] . "', stock_status_id = '" . (int)$data['stock_status_id'] . "', date_available = '" . $this->db->escape($data['date_available']) . "', manufacturer_id = '" . (int)$data['manufacturer_id'] . "', shipping = '" . (int)$data['shipping'] . "', price = '" . (float)$data['price'] . "', points = '" . (int)$data['points'] . "', weight = '" . (float)$data['weight'] . "', weight_class_id = '" . (int)$data['weight_class_id'] . "', length = '" . (float)$data['length'] . "', width = '" . (float)$data['width'] . "', height = '" . (float)$data['height'] . "', length_class_id = '" . (int)$data['length_class_id'] . "', status = '" . (int)$data['status'] . "', tax_class_id = '" . $this->db->escape($data['tax_class_id']) . "', sort_order = '" . (int)$data['sort_order'] . "', date_added = NOW()");
    		
    		$product_id = $this->db->getLastId();
    		
    		if (isset($data['image'])) {
    			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE product_id = '" . (int)$product_id . "'");
    		}
    		
    		foreach ($data['product_description'] as $language_id => $value) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', description = '" . $this->db->escape($value['description']) . "', tag = '" . $this->db->escape($value['tag']) . "'");
    		}
    		
    		if (isset($data['product_store'])) {
    			foreach ($data['product_store'] as $store_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
    			}
    		}
    
    		if (isset($data['product_attribute'])) {
    			foreach ($data['product_attribute'] as $product_attribute) {
    				if ($product_attribute['attribute_id']) {
    					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");
    					
    					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {				
    						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
    					}
    				}
    			}
    		}
    	
    		if (isset($data['product_option'])) {
    			foreach ($data['product_option'] as $product_option) {
    				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");
    				
    					$product_option_id = $this->db->getLastId();
    				
    					if (isset($product_option['product_option_value']) && count($product_option['product_option_value']) > 0 ) {
    						foreach ($product_option['product_option_value'] as $product_option_value) {
    							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
    						} 
    					}else{
    						$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_option_id = '".$product_option_id."'");
    					}
    				} else { 
    					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value = '" . $this->db->escape($product_option['option_value']) . "', required = '" . (int)$product_option['required'] . "'");
    				}
    			}
    		}
    		
    		if (isset($data['product_discount'])) {
    			foreach ($data['product_discount'] as $product_discount) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
    			}
    		}
    
    		if (isset($data['product_special'])) {
    			foreach ($data['product_special'] as $product_special) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
    			}
    		}
    		
    		if (isset($data['product_image'])) {
    			foreach ($data['product_image'] as $product_image) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET product_id = '" . (int)$product_id . "', image = '" . $this->db->escape(html_entity_decode($product_image['image'], ENT_QUOTES, 'UTF-8')) . "', sort_order = '" . (int)$product_image['sort_order'] . "'");
    			}
    		}
    		
    		if (isset($data['product_download'])) {
    			foreach ($data['product_download'] as $download_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
    			}
    		}
    		
    		if (isset($data['product_category'])) {
    			foreach ($data['product_category'] as $category_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
    			}
    		}
    		
    		if (isset($data['product_filter'])) {
    			foreach ($data['product_filter'] as $filter_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
    			}
    		}
    		
    		if (isset($data['product_related'])) {
    			foreach ($data['product_related'] as $related_id) {
    				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
    				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
    			}
    		}
    
    		if (isset($data['product_reward'])) {
    			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
    			}
    		}
    
    		if (isset($data['product_layout'])) {
    			foreach ($data['product_layout'] as $store_id => $layout) {
    				if ($layout['layout_id']) {
    					$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout['layout_id'] . "'");
    				}
    			}
    		}
    						
    		if ($data['keyword']) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int) $product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
    		}
    
    		if (isset($data['product_profiles'])) {
    			foreach ($data['product_profiles'] as $profile) {
    				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_profile` SET `product_id` = " . (int) $product_id . ", customer_group_id = " . (int) $profile['customer_group_id'] . ", `profile_id` = " . (int) $profile['profile_id']);
    			}
    		} 
    		
            return $product_id;
            
    		//$this->cache->delete('product');
    	}
        
        public function deleteProduct($product_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "product WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int) $product_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_profile` WHERE `product_id` = " . (int) $product_id);
		$this->db->query("DELETE FROM " . DB_PREFIX . "review WHERE product_id = '" . (int)$product_id . "'");
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id. "'");
		
		//$this->cache->delete('product');
	}
        
        public function addManufacturer($data) {
    	    $this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer SET name = '" . $this->db->escape($data['name']) . "', sort_order = '" . (int)$data['sort_order'] . "'");
    		
    		$manufacturer_id = $this->db->getLastId();
    
    		if (isset($data['image'])) {
    			$this->db->query("UPDATE " . DB_PREFIX . "manufacturer SET image = '" . $this->db->escape(html_entity_decode($data['image'], ENT_QUOTES, 'UTF-8')) . "' WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");
    		}
    		
    		if (isset($data['manufacturer_store'])) {
    			foreach ($data['manufacturer_store'] as $store_id) {
    				$this->db->query("INSERT INTO " . DB_PREFIX . "manufacturer_to_store SET manufacturer_id = '" . (int)$manufacturer_id . "', store_id = '" . (int)$store_id . "'");
    			}
    		}
    				
    		if ($data['keyword']) {
    			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'manufacturer_id=" . (int)$manufacturer_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
    		}
    		
    		//$this->cache->delete('manufacturer');
            return $manufacturer_id;
    	}
        
        public function check_category($data)
        {
            $cat_ids = array();
            //echo "category";
            //print_r($data);
            //echo "<br/>";
            $categories = explode("> ",$data);
            //print_r($categories);
            $cat = end($categories);
            foreach ($categories as $k=>$v)
            {
                $result = $this->db->query("Select * from  ".DB_PREFIX."category_description where name = '$v'");
                
                if($result->num_rows > 0)
                {
                    $cat_ids[$v] = $result->row['category_id'];
                    //return $result->row['category_id'];
                    //echo "<br />already available";
                }
                else
                {
                    //echo "add new Category";
                    $category = array(
                        "category_description"=>array("1"=>array("name"=>"$v","meta_description"=>"","meta_keyword"=>"","description"=>"")),
                        "path"=>"",
                        "parent_id"=>0,
                        "filter"=>"",
                        "category_store"=>array("0"=>0),
                        "keyword"=>"",
                        "image"=>"",
                        "top"=>1,
                        "column"=>1,
                        "sort_order"=>0,
                        "status"=>1,
                        "category_layout"=>array(array("layout_id"=>""))
                        );
                        if($k > 0)
                        {
                            $parent_cat = $categories[$k-1];
                            $parent_cat_id = $cat_ids[$parent_cat];
                            $category['parent_id'] = $parent_cat_id;
                            $category['path'] = $parent_cat;
                        }
                        
                    $cat = $this->addCategory($category);
                    $cat_ids[$v] = $cat; 
                }
            }
            //print_r($cat_ids);
            $return_cat_id = end($cat_ids);
            $return_cat = $this->endKey($cat_ids);
            return array($return_cat=>$return_cat_id);
            
            
        }//check category
        
        public function check_options($opt)
        {
            
            $return_det = array();
            foreach($opt as $o)
            {
                //print_r($o);
                foreach ($o as $optns)
                {
                    foreach($optns as $k=>$v)
                    {
                        //echo $k ." = ".$v;
                    
                        $result = $this->db->query("Select * from  ".DB_PREFIX."option_description where name = '$k'");
                    
                        if($result->num_rows > 0)
                        {
                            //print_r($result);
                            $options_det = $result->row;
                            $opt_id = $options_det['option_id'];
                            //echo $opt_id."<br />";
                            $opt_val_ids = array();
                            
                            //echo $v;
                            $opt_val = $this->db->query("Select * from  ".DB_PREFIX."option_value_description ovd JOIN ".DB_PREFIX."option_value ov ON ovd.option_value_id = ov.option_value_id where ov.option_id = $opt_id && ovd.name = '$v'");
                            if($opt_val->num_rows > 0)
                            {
                                //print_r($opt_val->rows);
                                foreach ($opt_val->rows as $op)
                                {
                                    $opt_val_ids[$op['name']] = $op['option_value_id'];
                                }
                                $return_det[] = array($k=>$opt_id,"option_value_ids"=>$opt_val_ids);
                                 
                                //echo "==>".$opt_val_det['option_value_id']."<br />";
                                
                            }
                            else
                            {
                                $available_opt = $this->db->query("Select * from  ".DB_PREFIX."option_value_description ovd JOIN ".DB_PREFIX."option_value ov ON ovd.option_value_id = ov.option_value_id where ov.option_id = $opt_id");
                                if($available_opt->num_rows >0)
                                {
                                    $opt_values = array();
                                    foreach($available_opt->rows as $ao)
                                    {
                                        //print_r($ao);
                                        $opt_values[] = array("option_value_id"=>$ao['option_value_id'],"option_value_description"=>array("1"=>array("name"=>$ao['name'])),"image"=>$ao['image'],"sort_order"=>$ao['sort_order']);
                                    }
                                    $opt_values[] = array("option_value_id"=>"","option_value_description"=>array("1"=>array("name"=>$v)),"image"=>"","sort_order"=>"");
                                    
                                }
                                
                                //echo "==>add new option value<br />";
                                $options = array(
                                    "option_description"=>array("1"=>array("name"=>$k)),
                                    "type"=>'select',
                                    "sort_order"=>"",
                                    "option_value"=>$opt_values,
                                    "required"=>""
                                    );
                                
                                $return_det[] = $this->editOption($opt_id,$options);
                            }
                            
                            
                        }
                        else
                        {
                            $opt_values = array();
                            $opt_values[] = array("option_value_id"=>"","option_value_description"=>array("1"=>array("name"=>$v)),"image"=>"","sort_order"=>"");
                            $options = array(
                                "option_description"=>array("1"=>array("name"=>$k)),
                                "type"=>'select',
                                "sort_order"=>"",
                                "option_value"=>$opt_values
                                );
                                
                                //print_r($options);
                            $return_det[] = $this->addOption($options);
                            
                        }
                    }
                    
                }
                //die();
            }
            
            return $return_det;
        }//check options
        
        public function check_manufacturer($data)
        {
            if($data == null)
            {
                return null;
            }
            $result = $this->db->query("Select * from  ".DB_PREFIX."manufacturer where name = '$data'");
            if($result->num_rows > 0)
            {
                $manufacturer = $result->row;
                $manu = $manufacturer['manufacturer_id'];
            }
            else
            {
                //add new manufacturer
                $manufacturer = array(
                            "name"=>$data,
                            "manufacturer_store"=>array(0),
                            "keyword"=>"",
                            "image"=>"",
                            "sort_order"=>""
                            );
                $manu =  $this->addManufacturer($manufacturer);
            }
            return $manu;
        }//check_manufacturer
        
        private function endKey($array){
         end($array);
         return key($array);
        }
        
        
        public function check_product_exists($name,$sku,$import_id)
        {
            $product = $this->db->query("select product_id from ".DB_PREFIX."product where sku = '$sku'");
            if($product->num_rows > 0)
            {
                $product_detail = $product->row;
                $product_id = $product_detail['product_id'];
                $check_product = $this->db->query("select * from ".DB_PREFIX."product_description where product_id = '$product_id' && name = '$name'");
                if($check_product->num_rows > 0)
                {
                    $this->deleteProduct($product_id);
                    $product = $check_product->row;
                    $prd_name = $product['name'];
                    $import = $this->db->query("select * from ".DB_PREFIX."productify_import where import_id = $import_id");
                    $import_det = $import->row;
                    $updated = json_decode($import_det['updated_products']);
                    
                    $updated[] = $prd_name;
                    $updated = json_encode($updated);
                    $this->db->query("update ".DB_PREFIX."productify_import set updated_products = '$updated' where import_id = $import_id");
                }
            }
        }
        
        public function check_pending()
        {
            //echo "checking remaining tasks<br />";
            $current_time = date("Y-m-d H:i:s");
            $check = $this->db->query("select * from ".DB_PREFIX."productify_import where modified_time < (NOW() - INTERVAL 10 MINUTE) && processing = 1 && status = 1");
            //echo "<pre>";print_r($check);echo "</pre>";
            if($check->num_rows > 0)
            {
                $process = $check->row;
                if($process['skus'] != "")
                {
                    $this->db->query("update ".DB_PREFIX."productify_import set processing = 0 where import_id = ".$process['import_id']);
                    $process['pendig_testing'] = 'remaining';
                }
                else
                {
                    $this->db->query("update ".DB_PREFIX."productify_import set processing = 0,status = 0 where import_id = ".$process['import_id']);
                    $process['pendig_testing'] = 'completed';
                }
                
                return $process;
            }
            else
            {
                return null;
            }
        }
        
        
        public function updated_prds($import_id)
        {
            $updated = $this->db->query("select updated_products from ".DB_PREFIX."productify_import where import_id = $import_id");
            $updated_det = $updated->row;
            $updated_products = json_decode($updated_det['updated_products']);
            return $updated_products;
        }
        
        
    }
?>