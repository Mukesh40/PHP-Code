<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class Index extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model(array('visitor_model','user_model','state_model','sale_model','pickuptime_model','saleimage_model','auctiondate_model','consignor_model','item_model','itemimage_model','itemcategory_model','emailblast_model','bid_model'));
		$this->load->model('banner_model');
		$session_data = $this->session->userdata('loggedin_user');
		$this->data['user_name'] = $session_data['user_name'];
		$this->data['user_type'] = $session_data['user_type'];
		$this->data['user_id'] = $session_data['id'];
	}
	public function index()
	{
		$customer_ip = $this->visitor_model->get_client_ip();
		$ip_count=$this->visitor_model->findCount(array('ip_address'=>$customer_ip));
		if($ip_count==0){ $this->visitor_model->insert(array('ip_address'=>$customer_ip,'visit_date'=>date('Y-m-d g:i:s'))); }
		
		$this->data['banners'] = $this->banner_model->findAll(array('status'=>'1'),"*","","","");
		$this->data['featured_sales'] = $this->sale_model->getSales('featured_sale','current',"index");
		$this->data['items_we_love'] = $this->sale_model->items_we_love('items_we_love','current',"index");
		$this->load->view('index',$this->data);
	}
	
	public function testinvoice(){
	
		
								$url = 'https://books.zoho.com/api/v3/invoices?authtoken=6572f5957dae6b3f8dcf353f5483a83a&organization_id=653048815';
								$param = array(
								'customer_id'=>'919322000000504010',
								'line_items' => array(
									array(
										'item_id'=>'919322000000557001',
										'project_id'=>'',
										'expense_id'=>'',
										'salesorder_item_id'=>'',
										'name'=>'sdfsdf',
										'description'=>'fgdgdfgdf',
										//'item_order'=>0,
										'rate'=>'2.00',
										'unit'=>'',
										'quantity'=>'2',
										'discount'=>'0%',
										'item_total'=> '4.00'
									)
								),
								'notes'=> 'Buyer Fee 15%',
								);
							
								$ch = curl_init($url);
								curl_setopt($ch, CURLOPT_VERBOSE, 1);
								curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
								curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
								curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
								curl_setopt($ch, CURLOPT_POST, TRUE);
								curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
								curl_setopt($ch, CURLOPT_POSTFIELDS, "&JSONString=".urlencode(json_encode($param)));
								$result = curl_exec($ch);
								$obj = json_decode($result);
								//print_r($obj);die;
								if(isset($obj->invoice)){
									$zoho_invoice_id = $obj->invoice->invoice_id;
									if(isset($zoho_invoice_id)){
										//$this->invoice_model->update_content($invoice['id'],array('zoho_invoice_id'=>$zoho_invoice_id));
										//$this->db->where('id', $invoice['id']);
										//$this->db->update('tbl_invoice', array('zoho_invoice_id'=>$zoho_invoice_id));
									}
								}
								curl_close($ch);
								
								if(isset($zoho_invoice_id)){
									//send invoice to user
									$url = 'https://books.zoho.com/api/v3/invoices/'.$zoho_invoice_id.'/email?authtoken=6572f5957dae6b3f8dcf353f5483a83a&organization_id=653048815';
									$param = array(
									"send_from_org_email_id"=> false,
									"to_mail_ids"=> [
										'ashoka0505@gmail.com'
									],
									
									"subject"=> "Invoice from TheEstateSale (Invoice#: 44)",
									"body"=> "Dear Customer,<br><br><br><br>Thanks for your business.<br><br><br><br>The invoice #44 is attached with this email. <br><br>Please find an overview of the invoice for your reference.<br><br><br><br>Invoice Overview: <br><br>Invoice # : 44 <br><br>Date :  <br><br>Amount :  <br><br><br><br>It was great working with you. Looking forward to working with you again.<br><br><br>\nRegards<br>\nTheEstateSale<br>\n\","
									);
								
									$ch = curl_init($url);
									curl_setopt($ch, CURLOPT_VERBOSE, 1);
									curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
									curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
									curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
									curl_setopt($ch, CURLOPT_POST, TRUE);
									curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/x-www-form-urlencoded"));
									curl_setopt($ch, CURLOPT_POSTFIELDS, "&JSONString=".urlencode(json_encode($param)));
									$result = curl_exec($ch);
									$obj = json_decode($result);
									//print_r($obj);
									
									//mail("shoaib.gensofts@gmail.com","Cron Test","Test Message");
								}
	
	}
}
