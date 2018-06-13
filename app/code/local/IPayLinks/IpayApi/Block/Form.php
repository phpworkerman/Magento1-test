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
 * @package     IPayLinks_IpayApi_Block_Form
 * @copyright   Copyright (c) 2015 iPayLinks  (www.ipaylinks.com)
 * @license     http://opensource.org/licenses/bsd-license.php  BSD License
 */


class IPayLinks_IpayApi_Block_Form extends Mage_Payment_Block_Form
{
	protected $_paymentConfig;
	
    protected function _construct()
    {
		$this->_paymentConfig = Mage::getStoreConfig('payment/ipayapi');
        $this->setTemplate('ipayapi/form.phtml');
        parent::_construct();
    }
    
    /**
     * Retrieve payment configuration object
     *
     * @return Mage_Payment_Model_Config
     */
    protected function _getConfig()
    {
        return Mage::getSingleton('payment/config');
    }
	
	public function createExtendedFraudProfilingSession($iPayLinksInfo)
	{
// 		if(empty($this->_paymentConfig['extendedfraudprofiling'])) return false;
		
		$checkout = Mage::getSingleton('checkout/session');
		$stepIsAllowed = $checkout->getStepData('payment', 'allow');
		$stepIsComplete = $checkout->getStepData('payment', 'complete');
		
		if(!$stepIsAllowed || $stepIsComplete)
		{
			return false;
		}
		
		try
		{
			$method = $this->getMethod();
		
			if(!$method) return false;
			
			// payment info might not be avliable at this point (first render?)
			$paymentInfo = $method->getInfoInstance();
		}
		catch (Exception $e)
		{
			Mage::logException($e);
			return false;
		}
		
		$results = self::_getExtendedFraudProfilingSession($this->_paymentConfig, $iPayLinksInfo);
		
		// Mage::log('createExtendedFraudProfilingSession() sessionid: '.$results['sessionid']);
		
		if($results)
		{
			// efp = extended fraud profiling
			$paymentInfo->setAdditionalInformation('ipayapi_efpSessionId', $results['sessionid']);
		}
		
		return $results;
	}
    
    public function getCcAvailableTypes()
    {
        $types = $this->_getConfig()->getCcTypes();
        $types['JC'] = 'JCB';
        if ($method = $this->getMethod()) {
            $availableTypes = $method->getConfigData('cctypes');
            if ($availableTypes) {
                $availableTypes = explode(',', $availableTypes);
                foreach ($types as $code=>$name) {
                    if (!in_array($code, $availableTypes)) {
                        unset($types[$code]);
                    }
                }
            }
        }
        return $types;
    }
    
    public function getCcMonths(){
      $months['01'] = 'January';
      $months['02'] = 'February';
      $months['03'] = 'March';
      $months['04'] = 'April';
      $months['05'] = 'May';
      $months['06'] = 'June';
      $months['07'] = 'July';
      $months['08'] = 'August';
      $months['09'] = 'September';
      $months['10'] = 'October';
      $months['11'] = 'November';
      $months['12'] = 'December';
      return $months;
    }
    public function getCcYears()
    {
      for($i=0; $i<=10; $i++)
         $years[date('Y',strtotime("+$i years"))] = date('Y',strtotime("+$i years"));
      return $years;
    }
	
	static protected function _getExtendedFraudProfilingSession($config, $iPayLinksInfo)
	{
		// assemble template
		$query ='org_id=' .$iPayLinksInfo['orgId'] . '&session_id=' . $iPayLinksInfo['iPayLinksId']. $iPayLinksInfo['sessionId'];
		$baseurl = "https://h.online-metrix.net/fp/";

		$out = '<p style="background:url('.$baseurl.'clear.png?'.$query.'&m=1)"></p>';
		$out .= '<img src="'.$baseurl.'clear.png?'.$query.'&m=2" alt="">';
		$out .= '<object type="application/x-shockwave-flash" data="'.$baseurl.'fp.swf?'.$query.'" width="1" height="1" id="thm_fp">';
		$out .= '<param name="movie" value="'.$baseurl.'fp.swf?'.$query.'" />';
		$out .= '<div></div></object>';
		$out .= '<script src="'.$baseurl.'check.js?'.$query.'" type="text/javascript"></script>';

		// cast xml 'objects' as strings to avoid weird issues elsewhere (like setAdditionalInfo for payments)
		return array('sessionid'=> $iPayLinksInfo['sessionId'], 'orgid'=> $iPayLinksInfo['orgId'], 'html'=> $out);
	}
	
	public function createIPayLinksDeviceFingerprintID(){
		$guid =  $this->getGuid();
		return array(
			'orgId' => $this->_paymentConfig['sandbox']?'1snn5n9w':'k8vif92e',
			'iPayLinksId' => 'ipaylinks',
			'sessionId' => $this->_paymentConfig['ipaylinks_user_id'].'-'.$guid
		);
	}
	
	public function getGuid() {
		return strtoupper(md5(uniqid(mt_rand(), true)));
	}	
	
	public function getIconFileName()
    {
    	$_config = Mage::getStoreConfig('payment/ipayapi');
        return $_config['form_icon_file'];
    }
}