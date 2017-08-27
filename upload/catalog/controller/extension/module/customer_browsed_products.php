<?php
class ControllerExtensionModuleCustomerBrowsedProducts extends Controller {
	public function index($setting) {
      	$this->load->language('extension/module/customer_browsed_products');

        $data['heading_title'] = $this->language->get('heading_title');
		$data['text_tax'] = $this->language->get('text_tax');
		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');

		$this->load->model('catalog/product');
		$this->load->model('tool/image');  

        $user_last_viewed = array();
		if($this->customer->isLogged()){
		$checkCartNotEmpty = $this->cart->hasProducts();

			if (isset($checkCartNotEmpty) && $checkCartNotEmpty > 0){
				$data['heading_title'] = $this->language->get('related_products_title');
						$results1 = $this->cart->getProducts();
						foreach ($results1 as $key) {
							$product_id =$key['product_id'];
							$this->load->model('catalog/category');
							$product_cat = $this->model_catalog_product->getCategories($product_id);
							$category_id = $product_cat[0]['category_id'];
							$related_category = array(
							        'filter_category_id' => $category_id,
							        'start'=>0,
							        'limit'=>$setting['limit']//get the value of max config
							    );
	                     $results =$this->model_catalog_product->getProducts($related_category);
						}
			}
		   else{
	                $results = $this->cache->get('recently_viewed');
	                $data['user_last_viewed'] = array();
					if (isset($this->request->cookie['last_viewed_products'])) {
					            $data['user_last_viewed'] = explode(',', $this->request->cookie['last_viewed_products']);
					        } 
					else if (isset($this->session->data['last_viewed_products'])) {
					            $data['user_last_viewed'] = $this->session->data['last_viewed_products'];
					        }
					if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {

					           (int)$product_id = $this->request->get['product_id'];   

					            (int)$data['user_last_viewed'] = array_diff($data['user_last_viewed'], array($product_id));

					            array_unshift($data['user_last_viewed'], $product_id);

					            setcookie('last_viewed_products', implode(',',$data['user_last_viewed']), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);

					            if (!isset($this->session->data['last_viewed_products']) || $this->session->data['last_viewed_products'] != $data['user_last_viewed']) {
					                $this->session->data['last_viewed_products'] = $data['user_last_viewed'];
					            }
					     $count_last_viewed = $setting['limit'];

					     $resultsProductId = array();
					     $results = array();

					             if (isset($count_last_viewed) && $count_last_viewed > 0) {
					                 for ($i = 0; $i < $count_last_viewed; $i++) {

					                     $key = isset($product_id) ? $i + 1 : $i;
	                                     
					                     if (isset($data['user_last_viewed'][$key])) {
					                         $resultsProductId[]= $data['user_last_viewed'][$key];
					                         
					                   $results[$i] = $this->model_catalog_product->getProduct($resultsProductId[$i]);
					                         
					                     }
					                 }
					             $this->cache->set('recently_viewed', $results);

					         }     
				 }
			  }
        }
  else{

     	            $checkCache = $this->cache->get('guest_recently_viewed');
     	            if (isset($checkCache)) {
     	            	$results = $this->cache->get('guest_recently_viewed');
     	            	$data['guest_last_viewed'] = array();
					if (isset($this->request->cookie['guest_last_viewed_products'])) {
					            $data['guest_last_viewed'] = explode(',', $this->request->cookie['guest_last_viewed_products']);
					        } 
					else if (isset($this->session->data['guest_last_viewed_products'])) {
					            $data['guest_last_viewed'] = $this->session->data['guest_last_viewed_products'];
					        }
					if (isset($this->request->get['route']) && $this->request->get['route'] == 'product/product') {

					           (int)$product_id = $this->request->get['product_id'];   

					            (int)$data['guest_last_viewed'] = array_diff($data['guest_last_viewed'], array($product_id));

					            array_unshift($data['guest_last_viewed'], $product_id);

					            setcookie('guest_last_viewed_products', implode(',',$data['guest_last_viewed']), time() + 60 * 60 * 24 * 30, '/', $this->request->server['HTTP_HOST']);

					            if (!isset($this->session->data['guest_last_viewed_products']) || $this->session->data['guest_last_viewed_products'] != $data['guest_last_viewed']) {
					                $this->session->data['guest_last_viewed_products'] = $data['guest_last_viewed'];
					            }
					     $count_last_viewed = $setting['limit'];

					     $resultsProductId = array();
					     $results = array();

					             if (isset($count_last_viewed) && $count_last_viewed > 0) {
					                 for ($i = 0; $i < $count_last_viewed; $i++) {

					                     $key = isset($product_id) ? $i + 1 : $i;
	                                     
					                     if (isset($data['guest_last_viewed'][$key])) {
					                         $resultsProductId[]= $data['guest_last_viewed'][$key];
					                         
					                   $results[$i] = $this->model_catalog_product->getProduct($resultsProductId[$i]);
					                         
					                     }
					                 }
					             $this->cache->set('guest_recently_viewed', $results);

					         }     
				 }


     	            } 
     	            else if(!isset($checkCache)){
     	            	/////
 	 //$results = array();
     	 	$high_rating = array(
						        'start'=>1,
						        'limit'=>$setting['limit'], //get the value of max config
						        'rating',
						        'order'=> 'ASC'
			                );
     	 $results = $this->model_catalog_product->getProducts($high_rating);
     	            	
     	            }    	            
  	
     }
		if ($results) {
			foreach ($results as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height']);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $setting['width'], $setting['height']);
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = $result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			return $this->load->view('extension/module/customer_browsed_products', $data);
		}
	}
}
