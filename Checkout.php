<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Checkout extends CI_Controller {
	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('visitor_model','user_model','state_model','sale_model','pickuptime_model','saleimage_model','auctiondate_model','consignor_model','item_model','itemimage_model','itemcategory_model','emailblast_model','emailsubscriber_model','bid_model','bidincrement_model','sellerclient_model','cart_model','usercard_model', 'order_model','companyinfo_model'));

		$this->load->helper('cookie');
		$session_data = $this->session->userdata('loggedin_user');
		$this->data['user_name'] = $session_data['user_name'];
		$this->data['user_id'] = $session_data['id'];
		$this->data['user_type'] = $session_data['user_type'];
		include APPPATH . 'third_party/stripe/init.php';
		
	}

public function cart_popup($item_slug){
	if($item_slug)
	{
		$this->data['item'] = $this->item_model->find(array('slug'=>$item_slug));
		$this->data['sale'] = $this->sale_model->find(array('id'=>$this->data['item']['sale_id']));
		$this->data['company'] = $this->companyinfo_model->find(array('user_id'=>$this->data['sale']['seller_id']));
		$this->data['sale_images_count'] = $this->saleimage_model->findCount(array('sale_id'=>$this->data['sale']['id']));
		$this->data['item_images_count'] = $this->itemimage_model->findCount(array('sale_id'=>$this->data['sale']['id']));
		$this->data['item_images'] = $this->itemimage_model->findAll(array('item_id'=>$this->data['item']['id']),"*",array('cover_photo'=>'DESC'),"","");
		$this->data['auction_date'] = $this->auctiondate_model->find(array('sale_id'=>$this->data['sale']['id']));
		$this->data['seller'] = $this->user_model->find(array('id'=>$this->data['sale']['seller_id']));
		$this->data['bids'] = $this->bid_model->findAll(array('item_id'=>$this->data['item']['id']),"*",array('bid_amount'=>'DESC'),"","");
		$this->data['bid_count'] = $this->bid_model->findCount(array('item_id'=>$this->data['item']['id']));
		$this->data['max_bid'] = $this->bid_model->getMaxBid($this->data['item']['id']);
		$this->data['bid_increments'] = $this->bidincrement_model->findAll(array('status'=>'1'),"*","","","");
		$c_max_bid = empty($this->data['max_bid']->max_bid) ? '1' : $this->data['max_bid']->max_bid;
		$this->data['next_inc'] = $this->bidincrement_model->getNextBidIncrement($c_max_bid);
		$this->data['fees'] = $this->admin_model->find(array('id'=>'1'),array('buyer_fee'));
		//print_r($this->data['next_inc']);die;
		$this->load->view('checkout/cart-popup',$this->data);
	}
}

	public function add_to_cart($item_id=""){
		if($item_id==""){
			echo json_encode(array('status'=>false,'message'=>'Something went wrong, please try again!'));
		}else{

			if($this->cart_model->find(array('item_id'=>$item_id, 'user_id'=>$this->data['user_id'], 'session_id'=>session_id()))){
				echo json_encode(array('status'=>true,'message'=>'Data added successfully!'));
			}else{

				$item = $this->item_model->find(array('id'=>$item_id));

				$data_arr = array();
				$data_arr['item_id'] = $item['id'];
	    		$data_arr['sale_id'] = $item['sale_id'];
	    		$data_arr['user_id'] = $this->data['user_id'];
	    		$data_arr['seller_id'] = $item['seller_id'];
	    		$data_arr['session_id'] = session_id();

	    		/*
	    		[item_title] => this is lot for buy now testing
	    		[description] => this is lot for buy now testing
	    		[feature_item] => 0
	    		[consignor] => 5
	    		[commission] => 1.00
	    		[can_shipped] => 1
	    		[measurements] => {"height":"2","width":"2","length":"2","weight":"20"}
	    		[status] => 1
	    		[add_date] => 2018-06-27
	    		[slug] => this-is-lot-for-buy-now-testing
			    [is_sold] => 0
			    [sold_price] => 0.00
			    [is_taxable] => 0
			    [zoho_item_id] => 919322000000436001
			    [a_item_id] => 10072-01
			    [sort_order] => 0
			    [total_views] => 79
			    [is_published] => 1
			    [item_end_date] => 2018-07-04 01:00:00
			    [buy_now] => 1
			    [buy_price] => 10.00
			    [buy_bin_shipping] => 1
			    [buy_local_pickup] => 0
			    */

			    $this->cart_model->insert($data_arr);

		    	echo json_encode(array('status'=>true,'message'=>'Data added successfully!'));
			}
		}
	}

	function getShippingInfo(){
		$item = $this->item_model->find(array('id'=>$_POST['item_id']),array('id','sale_id','measurements'));
		$sale = $this->sale_model->find(array('id'=>$item['sale_id']));

		$shipping_from_zipcode = ($_POST['shipping_from_zipcode']!="")?$_POST['shipping_from_zipcode']:$sale;
		$postal_code = ($_POST['postal_code']!="")?$_POST['postal_code']:'32091';
		$state = ($_POST['state']!="")?$_POST['state']:'FL';
		$city = ($_POST['city']!="")?$_POST['city']:'starke';
		$address = ($_POST['address']!="")?$_POST['address']:'345 Chambers Street';

		if(!empty($item['measurements'])){
			$m_arr = json_decode($item['measurements']);
			$height = $m_arr->height;
			$width = $m_arr->width;
			$length = $m_arr->length;
			$weight = $m_arr->weight;
			$dim_weight = ($height*$width*$length)/166;
			
		}else{
			$height = 5.0;
			$width = 5.0;
			$length = 5.0;
			$weight = 1.0;
			$dim_weight = 1.0;
		}
		$param = array(
			
			  "shipment" => array(
				"validate_address" => "no_validation",
				"carrier_id"=> "se-189937",
				"service_code"=> "ups_ground",
				"ship_to" => array(
				  "name" => "",
				  "phone" => "512-485-4282",
				  "company_name" => "test company",
				  "address_line1" => $address,
				  "city_locality" => $city,
				  "state_province" => $state,
				  "postal_code" => $postal_code,
				  "country_code" => "US"
				),
				"ship_from" => array(
				  "name" => "John Doe",
				  "phone" => "512-485-4282",
				  "company_name" => "test company",
				  "address_line1" => $sale['address'],
				  "address_line2" => "",
				  "city_locality" => $sale['city'],
				  "state_province" => $sale['state'],
				  "postal_code" => $sale['shipping_from_zipcode'],
				  "country_code" => "US"
				),
				"packages" => [
				  array(
					"weight" => array(
					  "value" => $dim_weight,
					  "unit" => "ounce"
					)
				  )
				]
			  ),
			  "rate_options"=> array(
				"carrier_ids" => [ "se-189937" ]
			  )
		);
		
		$url = "https://api.shipengine.com/v1/labels";
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_VERBOSE, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json","api-key:wtuIe8+M9geZqaJN3vE2GcwlocN1eXSvQa60reQcHrc"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($param));
		$result = curl_exec($ch);
		$obj = json_decode($result);
		$resArr = (array)$obj;
		//echo "<pre>	";
		//print_r($resArr);
		
		if(is_array($resArr) && count($resArr)>0){
			if(isset($resArr['status']) && $resArr['status']=="completed"){
				$responseArr = array('status'=>'success', 'ship_info'=>array('ship_date'=>date('Y-m-d', strtotime($resArr['ship_date'])), 'ship_cost'=>$resArr['shipment_cost']));
			}elseif(isset($resArr['errors'])){
				$responseArr = array('status'=>'fail', 'ship_info'=>array('error_code'=>$resArr['errors'][0]->error_code, 'error_message'=>$resArr['errors'][0]->message));
			}
		}


		echo json_encode($responseArr);
		exit;
	}
	
	public function buy_now(){
		$cart_info = array();
		$time_left = "";
		$card_details = array();
		$sql = "select tbc.*, tbu.user_name, tbu.stripe_customer_id, tbu.stripe_card_id, tbi.item_title, tbi.buy_price, tbi.item_end_date, tbi.slug as item_slug, tbu.contact_number from tbl_cart tbc left join tbl_user tbu on tbc.user_id=tbu.id left join tbl_items tbi on tbc.item_id=tbi.id where tbc.user_id='".$this->data['user_id']."' AND session_id='".session_id()."'";
		$query = $this->db->query($sql);
		$res = $query->result_array($query);
		//echo "<br><br>";

		$item = $this->item_model->find(array('id'=>$res[0]['item_id']));
		$sale = $this->sale_model->find(array('id'=>$res[0]['sale_id']));

		$shippingInfo = array('offer_shipping'=>$sale['offer_shipping'], 'shipping_from_zipcode'=>$sale['shipping_from_zipcode'],'pickup_available'=>$sale['pickup_available']);

		if($sale['pickup_available']==1){
			$this->data['pickup_times'] = $this->pickuptime_model->findAll(array('sale_id'=>$sale['id']),"*",array('id'=>'ASC'),"","");
		}

		if(!empty($res)){
			$cart_info[] = $res[0];
			/* calculate time left */
			$auction_date = $this->auctiondate_model->find(array('sale_id'=>$cart_info[0]['sale_id']));
			$timeZone = $this->config->item($auction_date['timezone'], 'counter_time_zones');
			$curDateTime = (new DateTime($timeZone))->format('Y-m-d H:i:s');	
			$time_left = $this->sale_model->getTime($cart_info[0]['item_end_date'],$curDateTime);

			$card_details = $this->usercard_model->find(array('user_id'=>$this->data['user_id'],'is_primary'=>'1'));
			/* end calculate time left */

			//$shipping_info = $this->getShippingInfo($cart_info[0]['item_id'], $cart_info[0]['sale_id']);
			//echo "<pre>";
			//	print_r($shipping_info);
			//echo "</pre>";

		}
		//echo "<pre>";
			//print_r($item);
			//print_r($sale);
		//print_r($cart_info);
		//echo "</pre>";
		$this->data['cart_info'] = $cart_info;
		$this->data['sale_details'] = $sale;
		$this->data['time_left'] = $time_left;
		$this->data['card_details'] = $card_details;
		$this->data['shippingInfo'] = $shippingInfo;
		$this->load->view('checkout/buy-now',$this->data);
	}


	public function place_order(){
		$this->data['user'] = $this->user_model->find(array('id'=>$this->data['user_id']));
		$payment_status = false;
		$order_arr = array();
		if($_POST){
			$sale_id = $_POST['sale_id'];
			$item_id = $_POST['item_id'];
			$buy_price = $_POST['buy_price'];

			$user_id = $_POST['user_id'];
			$seller_id = $_POST['seller_id'];

			$user_name = $_POST['user_name'];
			$address = $_POST['address'];
			$city = $_POST['city'];
			$state = $_POST['state'];
			$postal_code = $_POST['postal_code'];
			$phone_number = $_POST['phone_number'];

			if($buy_price>0){
				$order_arr['sale_id'] = $sale_id;
				$order_arr['item_id'] = $item_id;
				$order_arr['buy_price'] = $buy_price;

				$order_arr['user_id'] = $user_id;
				$order_arr['seller_id'] = $seller_id;

				$order_arr['user_name'] = $user_name;
				$order_arr['address'] = $address;
				$order_arr['city'] = $city;
				$order_arr['state'] = $state;
				$order_arr['postal_code'] = $postal_code;
				$order_arr['phone_number'] = $phone_number;
				$order_arr['session_id'] = session_id();
				$order_arr['order_status'] = 0;

				$order = $this->order_model->insert($order_arr);
				
				$buyer_fee = number_format(($buy_price*15)/100, 2, '.', '');
				
				$grand_total = $buy_price+$buyer_fee;
				
				//\Stripe\Stripe::setApiKey("sk_live_JK604I1YrVUlsc2Y8ZS9TDtg");
				\Stripe\Stripe::setApiKey("sk_test_Ho5eUtX5hxZ5EBwlMkS4fLh4");

				if($_POST['use_card_on_file']==1){
					$charge = \Stripe\Charge::create(array(
				   		"amount" => $grand_total*100, // amount in cents, again
				   		"currency" => "usd",
				   		"description" => "Payment for Buy Now plus 15% buyer fee",
				   		"customer" => $this->data['user']['stripe_customer_id'])
					);

					$c_status = $charge->status;
					$payment_status = true;
				}else{
					$token = Stripe\Token::create(array(
					  "card" => array(
					  "number" => $this->input->post("credit_card"),
					  "exp_month" => $this->input->post("expiry_month"),
					  "exp_year" => $this->input->post("expiry_year"),
					  "cvc" => $this->input->post("cvv")
						)
					));
					$customer = \Stripe\Customer::create(array(
						  "source" => $token,
						  "description" =>$this->data['user']['email_address'])
					);
					
					// Charge the Customer instead of the card
					$charge = \Stripe\Charge::create(array(
					   "amount" => $grand_total*100, // amount in cents, again
					   "currency" => "usd",
					   "description" => "Payment for Buy Now plus 15% buyer fee",
					   "customer" => $customer->id)
					);
					$c_status = $charge->status;
					$payment_status = true;
				}

				if($payment_status){
					$arr = array('order_status'=>'1');
					$this->order_model->update_content($order['id'], $arr);

					$item_arr = array('is_sold'=>'1', 'is_published'=>'0', 'sold_type'=>'1');
					$this->item_model->update_content($item_id, $item_arr);

					session_regenerate_id();
					//$this->session->set_flashdata('status', '<p class="success">Your Order has been placed. Thanks for your purchase.</p>');
					redirect(base_url('checkout/success'),'refresh');
				}else{
					//$this->session->set_flashdata('status', '<p class="error">Payment can\'t be completed right now. Please try again.</p>');
					redirect(base_url('checkout/failed'),'refresh');	
				}
			}else{
				$payment_status = false;
				//$this->session->set_flashdata('status', '<p class="error">Payment can\'t be completed right now. Please try again.</p>');
				redirect(base_url('checkout/failed'),'refresh');
			}

		}
	}

	public function success(){
		$this->data['user'] = $this->user_model->find(array('id'=>$this->data['user_id']));
		$this->load->view('checkout/buy-success',$this->data);
	}

	public function failed(){
		$this->data['user'] = $this->user_model->find(array('id'=>$this->data['user_id']));
		$this->load->view('checkout/buy-failed',$this->data);
	}
	

}
