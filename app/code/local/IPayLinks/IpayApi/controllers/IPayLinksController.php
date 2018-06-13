<?php
class IPayLinks_IpayApi_IPayLinksController extends Mage_Core_Controller_Front_Action {
	public function createInvoice($order) {
		try {
			$savedQtys = array ();
			$invoice = Mage::getModel ( 'sales/service_order', $order )->prepareInvoice ( $savedQtys );
			Mage::register ( 'current_invoice', $invoice );
			$invoice->register ();
// 			$invoice->setEmailSent ( true );
			$invoice->getOrder ()->setCustomerNoteNotify ( true );
			$invoice->getOrder ()->setIsInProcess ( true );
			$transactionSave = Mage::getModel('core/resource_transaction')
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
			$invoice->setState ( "2" );
			$invoice->setCanVoidFlag ( false );
			$invoice->pay ();
			$transactionSave->save ();
			$invoice->sendEmail ( true, "" );
			
			if ($invoice->getEmailSent()) {
				$order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PROCESSING, Mage::helper ( 'paygate' )->__( 'Invoice #' . $invoice->getIncrementId() . ' is notified to customer.' ), true );
				$order->save();
			}
		} catch ( Exception $e ) {
			Mage::logException ( $e );
			echo print_r ( $e );
		}
	}
	public function notifyAction() {
		Mage::log ( '*****************received message from ipaylinks******************', null, "iPayLinks.log" );
		if ($this->getRequest ()->isPost ()) {
			$config = Mage::getStoreConfig ( 'payment/ipayapi' );
			$postData = $this->getRequest ()->getPost ();
			$method = 'post';
			$partner = $config ['ipaylinks_user_id'];
			$security_code = $config ['ipaylinks_user_key'];

			ksort($postData);
			$signStr='';
			$mysign='';
			foreach ($postData as $key => $value) {
				if ('' !== $value && 'signMsg' !== $key) $signStr .= '&' . $key . '=' . $value;
// 				Mage::log ( "$key ==> $value", null, "iPayLinks.log" );
			}
			$signStr=substr($signStr, 1);
			$signParams = $signStr; // for log
			$signStr .= "&pkey=".$security_code;
			
			if($postData['signType']=='2'){
				$mysign = strtolower(md5($signStr));
			}
// 			Mage::log ( " ===verifying signStr is :" . $signParams , null, "iPayLinks.log" );
			Mage::log ( "** verifying signMsg is :" . $mysign , null, "iPayLinks.log" );

			if ($mysign == $postData ["signMsg"]) {
				$order = Mage::getModel ( 'sales/order' );
				Mage::log ( 'orderId is ' . $postData ['orderId'], null, "iPayLinks.log" );
				
				for($i=0; $i<4; $i++){
					sleep(3);
					$realOrderId = $order->loadByIncrementId($postData['orderId'])->getRealOrderId();
					if($realOrderId) break;
				}
					
				$ext1Rtn = $postData ["remark"];
				$stateFromIpayLinks = $postData ["resultCode"];
				
				if ($realOrderId) {
					$ext1 = $order->getPayment()->getCcSsIssue();
					
					if($ext1 != $ext1Rtn) {
 						Mage::log ( 'Different remark of OrderId '. $realOrderId . ': ' . $ext1 . ' in sys, ' . $ext1Rtn . ' from ipaylinks.', null, "iPayLinks.log" );
 						echo 'OK';
					} else {
						$currency_code = $postData['currencyCode'];
						$currentOrderAmount = $order->getGrandTotal();
						
						if(in_array(strtoupper($currency_code),array('JPY','KRW','VND','ISK'))){
							$currentOrderAmount = floor($currentOrderAmount) * 100;
						}else{
							$currentOrderAmount = round($currentOrderAmount, 2) * 100;
						}
						
						
						$ipaylinksOrderAmount = intval ($postData["orderAmount"] );
						Mage::log ( "order amount from DB is :" . $currentOrderAmount, null, "iPayLinks.log" );
						Mage::log ( "order amount from ipaylinks is :" . $ipaylinksOrderAmount, null, "iPayLinks.log" );
						$currentOrderAmounts = "$currentOrderAmount";
						if ($ipaylinksOrderAmount != $currentOrderAmounts) {
							Mage::log ( "order amount is not equal to ipaylinks order amount", null, "iPayLinks.log" );
							header( "HTTP/1.1 901 Handling failed",true, 901);
							echo 'orderAmount is Wrong';
							exit ();
						}
						$mstate = $order->getState ();
						Mage::log ( "order state from DB is " . $mstate, null, "iPayLinks.log" );
						
						if (Mage_Sales_Model_Order::STATE_NEW == $mstate || Mage_Sales_Model_Order::STATE_PROCESSING == $mstate || Mage_Sales_Model_Order::STATE_CLOSED == $mstate || Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW == $mstate) {
							if(Mage_Sales_Model_Order::STATE_PROCESSING!=$mstate){
								if ('0000' == $stateFromIpayLinks) {
									if (Mage_Sales_Model_Order::STATE_PROCESSING != $mstate) {
										$order->setState ( Mage_Sales_Model_Order::STATE_PROCESSING );
										$order->setStatus ( Mage_Sales_Model_Order::STATE_PROCESSING );
										$order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_PROCESSING, Mage::helper ( 'paygate' )->__ ( 'Transaction is paid successfully.' ) );
										try {
											$order->save ();
											$this->createInvoice ( $order );
											echo 'OK';
										} catch ( Exception $e ) {
											Mage::log ( 'Save Order Exception 0000: ' . $e, null, "iPayLinks.log" );
											header( "HTTP/1.1 901 Handling failed",true, 901);
											echo 'Save Order Exception:' . $e;
											exit ();
										}
									} else {
										echo 'OK';
									}
								} else {
									if (Mage_Sales_Model_Order::STATE_CLOSED != $mstate) {
										$order->setData ( 'state', Mage_Sales_Model_Order::STATE_CLOSED );
										$order->setData ( 'status', Mage_Sales_Model_Order::STATE_CLOSED );
										$order->addStatusToHistory ( Mage_Sales_Model_Order::STATE_CLOSED, Mage::helper ( 'paygate' )->__ ( 'IpayApi acquiring failed(' . $postData ['resultCode'] . ':' . $postData ['resultMsg'] . ').' ) );
										try {
											$order->save ();
											echo 'OK';
										} catch ( Exception $e ) {
											Mage::log ( 'Save Order Exception ' . $stateFromIpayLinks . ": " . $e, null, "iPayLinks.log" );
											header( "HTTP/1.1 901 Handling failed",true, 901);
											echo 'Save Order Exception:' . $e;
											exit ();
										}
									} else {
										echo 'OK';
									}
								}
							}else{ // order status is already processing, no handling
								Mage::log("order status from ipaylinks async notification is " . $stateFromIpayLinks . ", but is already processing in sys.", null,"iPayLinks.log" );
								echo 'OK';
							}
						} else { // order status is not 'new' or 'processing' or 'closed' or 'review'
							Mage::log ( "order status is not new or processing or closed or review", null, "iPayLinks.log" );
							echo 'OK';
						}
					}
					
				} else {
					$mconfig = Mage::getStoreConfig ( 'payment/ipayapi' );
					if ('0000' !== $stateFromIpayLinks && ! $mconfig ['save_fail_order']) {
						echo 'OK';
					} else {
						Mage::log ( "Order is not create.", null, "iPayLinks.log" );
						header( "HTTP/1.1 901 Handling failed",true, 901);
						echo 'Order is not create.';
						exit ();
					}
				}
			} else {
				Mage::log ( "CheckSignFail:" . $signParams, null, "iPayLinks.log" );
				header( "HTTP/1.1 901 Handling failed",true, 901);
				echo 'Check sign fail.';
				exit ();
			} 
		} else {
			Mage::log ( 'Method is not post', null, "iPayLinks.log" );
			header( "HTTP/1.1 901 Handling failed",true, 901);
			echo 'Method is not post';
			exit ();
		}
	}
}