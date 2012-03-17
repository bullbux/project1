<?php 
	require_once(LIB_ROOT.DS.'googlecheckout'.DS.'library'.DS.'googlecart.php');
	require_once(LIB_ROOT.DS.'googlecheckout'.DS.'library'.DS.'googleitem.php');
	require_once(LIB_ROOT.DS.'googlecheckout'.DS.'library'.DS.'googleshipping.php');
	require_once(LIB_ROOT.DS.'googlecheckout'.DS.'library'.DS.'googletax.php');
	class gooogleCart{
		private $merchant_id;
		private $merchant_key;
		private $merchent_server;
		private $merchant_currency;
		public $cart;
		public function __construct(){
			$this->merchant_id =  MERCHANT_ID;
			$this->merchant_key = MERCHANT_KEY;
			$this->merchent_server = MERCHANT_SERVER;
			$this->merchant_currency = MERCHANT_CURRENCY;
			$this->cart = new GoogleCart(MERCHANT_ID, MERCHANT_KEY,MERCHANT_SERVER,MERCHANT_CURRENCY);
		}

		public function addCartItem($name, $desc, $qty, $price, $item_weight='', $numeric_weight=''){
			$item_1 = new GoogleItem($name, $desc, $qty, $price, $item_weight, $numeric_weight); 
			$this->cart->AddItem($item_1);
		}

		public function generateButton(){
			$this->cart->SetEditCartUrl(CART_EDIT_URL);
			$this->cart->SetContinueShoppingUrl(CONTINUE_SHOPPING_URL);
			$this->cart->SetRequestBuyerPhone(true);
			return $this->cart->CheckoutButtonCode("SMALL");
		}
	}?>