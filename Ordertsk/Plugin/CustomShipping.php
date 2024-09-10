<?php
namespace Kitchen\Ordertsk\Plugin;
 
 
class CustomShipping
 
{
 
  protected $checkoutSession;
 
 
  public function __construct(
 
    \Magento\Checkout\Model\Session $checkoutSession
 
  ) {
 
    $this->checkoutSession   = $checkoutSession;
 
  }
 
 
  public function afterGetConfig(
 
    \Magento\Checkout\Model\DefaultConfigProvider $subject,
 
    $output
 
  ) {
 
    $quote = $this->checkoutSession->getQuote();
 
    
    $output['shipping_type'] = $quote->getShippingType();
    $output['residential'] = (int)$quote->getResidential();
    $output['liftgate'] = (int)$quote->getLiftgate();
    $output['delivery'] = (int)$quote->getDeliveryAppointment();
    

    return $output;
 
  }
 
 
   
 
}