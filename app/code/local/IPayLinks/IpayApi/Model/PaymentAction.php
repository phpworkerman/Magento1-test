<?php
/**
 * iPayLinks Magento Plugin.
 * v1.0.1 -  Dec, 2015
 * 
 * 
 * Copyright (c) 2015 iPayLinks
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without 
 * modification, are permitted provided that the following conditions 
 * are met:
 * 
 *     - Redistributions of source code must retain the above copyright 
 *       notice, this list of conditions and the following disclaimer.
 *     - Redistributions in binary form must reproduce the above 
 *       copyright notice, this list of conditions and the following 
 *       disclaimer in the documentation and/or other materials 
 *       provided with the distribution.
 *     - Neither the name of the iPayLinks nor the names of its 
 *       contributors may be used to endorse or promote products 
 *       derived from this software without specific prior written 
 *       permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
 * POSSIBILITY OF SUCH DAMAGE.
 * 
 * @category    Mage
 * @package     IPayLinks_IpayApi_Model_PaymentAction
 * @copyright   Copyright (c) 2015 iPayLinks  (www.ipaylinks.com)
 * @license     http://opensource.org/licenses/bsd-license.php  BSD License
 */
class IPayLinks_IpayApi_Model_PaymentAction extends Mage_Payment_Model_Method_Cc {
	protected $_code = 'ipayapi';
	protected $_formBlockType = 'ipayapi/form';
	protected $_isGateway = true;
	protected $_canAuthorize = true;
	protected $_canCapture = true;
	protected $_canCapturePartial = true;
	protected $_canRefund = true;
	protected $_canRefundInvoicePartial = true;
	protected $_canVoid = true;
	protected $_canUseInternal = true;
	protected $_canUseCheckout = true;
	protected $_canUseForMultishipping = true;
	protected $_canSaveCc = false;
	protected $_authMode = 'auto';
	public function authorize(Varien_Object $payment, $amount) {
		// function exception_error_handler($errno, $errstr, $errfile, $errline ) {
		// throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
		// }
		
		// set_error_handler("exception_error_handler");
		
		// try{
		$service = $this->_initTransaction ( $payment );
		
		// general payment data
		$service->cardholder = $payment->getCcOwner ();
		$service->card = $payment->getCcNumber ();
		$service->exp = $payment->getCcExpMonth () . substr ( $payment->getCcExpYear (), 2, 2 );
		$service->cvv2 = $payment->getCcCid ();
		$service->amount = $amount;
		$service->ponum = $payment->getPoNumber ();
		$service->ccType = $payment->getCcType ();
		$service->ipayapiSession = $payment->getCcSsIssue ();
		
		// Mage::throwException(Mage::helper('paygate')->__('->'.$service->ipayapiSession));
		
		$service->grandTotalAmount = $payment->getAmountOrdered ();
		
		if ($this->getConfigData ( 'sandbox' )) {
			$service->custreceipt = true;
		}
		
		// if order exists, add order data
		$order = $payment->getOrder ();
		if (! empty ( $order )) {
			
			$orderid = $order->getIncrementId ();
			$service->invoice = $orderid;
			$service->orderid = $orderid;
			$service->ip = $order->getRemoteIp ();
			$service->email = $order->getCustomerEmail ();
			
			$service->tax = $order->getTaxAmount ();
			$service->shipping = $order->getShippingAmount ();
			
			$service->description = "Magento Order #" . $orderid;
			
			// billing info
			$billing = $order->getBillingAddress ();
			if (! empty ( $billing )) {
				// avs data
				list ( $avsstreet ) = $billing->getStreet ();
				$service->street = $avsstreet;
				$service->zip = $billing->getPostcode ();
				$service->billfname = $billing->getFirstname ();
				$service->billlname = $billing->getLastname ();
				$service->billcompany = $billing->getCompany ();
				$service->billstreet = $billing->getStreet ( 1 );
				$service->billstreet2 = $billing->getStreet ( 2 );
				$service->billcity = $billing->getCity ();
				$service->billstate = $billing->getRegion ();
				$service->billzip = $billing->getPostcode ();
				$service->billcountry = Mage::getModel('directory/country')->loadByCode($billing->getCountry())->getIso3Code();
				$service->billphone = $billing->getTelephone ();
				$service->custid = $billing->getCustomerId ();
			}
			// shipping info
			$shipping = $order->getShippingAddress ();
			if (! empty ( $shipping )) {
				$service->shipfname = $shipping->getFirstname ();
				$service->shiplname = $shipping->getLastname ();
				$service->shipcompany = $shipping->getCompany ();
				$service->shipstreet = $shipping->getStreet ( 1 );
				$service->shipstreet2 = $shipping->getStreet ( 2 );
				$service->shipcity = $shipping->getCity ();
				$service->shipstate = $shipping->getRegion ();
				$service->shipzip = $shipping->getPostcode ();
				$service->shipcountry = Mage::getModel('directory/country')->loadByCode($shipping->getCountry())->getIso3Code();
				$service->shipphone = $shipping->getTelephone ();
			}
			// line item data
			foreach ( $order->getAllVisibleItems () as $item ) {
				try {
					$sku = implode ( '<br />', Mage::helper ( 'catalog' )->splitSku ( $item->getSku () ) );
					$sku = trim ( $sku );
					$product = Mage::getModel ( 'catalog/product' )->loadByAttribute ( 'sku', $sku );
					if (is_object ( $product )) {
						// $firstProduct .= $product->getName()."|";
						$urlPath = $product->getUrlPath ();
						if (! empty ( $urlPath )) {
							$url = Mage::getUrl ( $urlPath, array (
									'_secure' => true 
							) );
						} else {
							$url = Mage::getBaseUrl ( 'web' );
						}
						$service->addLine ( $item->getSku (), $item->getName (), '', $item->getPrice (), $item->getQtyToInvoice (), $item->getTaxAmount (), $url );
					} else {
						$service->addLine ( $item->getSku (), $item->getName (), '', $item->getPrice (), $item->getQtyToInvoice (), $item->getTaxAmount (), '' );
					}
				} catch ( Exception $e1 ) {
					$service->addLine ( $item->getSku (), $item->getName (), '', $item->getPrice (), $item->getQtyToInvoice (), $item->getTaxAmount (), '' );
				}
			}
			// process transactions
			$service->Process ();
			// $payment->setTransactionId($service->ipaylinksOrderNo);
			// $payment->setIsTransactionClosed(1);
			
			if ($service->resultcode == 'S') {
				$payment->setCcApproval ( $service->ipaylinksOrderNo )->setLastTransId ( $service->ipaylinksOrderNo )->setIsTransactionPending ( true )->setIsFraudDetected ( false )->setStatus ( self::ACTION_AUTHORIZE );
				$order->addStatusHistoryComment ( 'iPayLinks order no : ' . $service->ipaylinksOrderNo );
				$order->setStatus ( Mage_Sales_Model_Order::STATE_PENDING_PAYMENT );
				$order->addStatusToHistory ( 'pending', Mage::helper ( 'paygate' )->__ ( 'iPayLinks order processing completed,  waiting for asynchronous notification.' ) );
				$order->setStatus ( 'pending' );
				$order->setState ( 'pending' );
				$order->save ();
				
				$isTranSuccess = true;
				Mage::getSingleton('core/session')->setIsTranSuccess($isTranSuccess);
				
			} else if ($service->resultcode == 'F') {
				$mconfig = Mage::getStoreConfig ( 'payment/ipayapi' );
				if (! $mconfig ['save_fail_order']) {
					Mage::throwException ( Mage::helper ( 'paygate' )->__ ( 'Payment authorization error:  ' . $service->error . '(' . $service->errorcode . ')' ) );
				} else {
					$payment->setCcApproval ( $service->ipaylinksOrderNo )->setLastTransId ( $service->ipaylinksOrderNo )->setIsTransactionPending ( false )->setIsFraudDetected ( false )->setStatus ( self::ACTION_AUTHORIZE );
					$order->addStatusHistoryComment ( 'iPayLinks order no : ' . $service->ipaylinksOrderNo );
					// $order->setStatus('pending');
					// $order->setState('pending');
					$order->save ();
					// Mage::app()->getFrontController()->getResponse()->setRedirect("http://www.ipaylinks.com");
					
					$isTranSuccess = false;
					Mage::getSingleton('core/session')->setIsTranSuccess($isTranSuccess);
				}
			} else if ($service->resultcode == 'E') { 
				Mage::log ( "exception occurs of synchronized requesting, order id is : " . $orderid, null, "iPayLinks.log" );
				Mage::throwException ( Mage::helper ( 'paygate' )->__ ( 'Payment failed(2001), Please kindly try again later.' ) );
			}
		}
	}
	
	/**
	 * Setup the iPayLinks transaction api class.
	 *
	 * Much of this code is common to all commands
	 *
	 * @param Mage_Sales_Model_Document $pament        	
	 * @return IPayLinks_IpayApi_Model_ApiService
	 */
	protected function _initTransaction(Varien_Object $payment) {
		$tran = Mage::getModel ( 'ipayapi/ApiService' );
		
		if ($this->getConfigData ( 'sandbox' ))
			$tran->usesandbox = true;
// 		$tran->software = 'IPayLinks_IpayApi 1.0';
		return $tran;
	}
}

