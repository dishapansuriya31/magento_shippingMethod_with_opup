<?php
/**
* Copyright © Magento, Inc. All rights reserved.
* See COPYING.txt for license details.
*/
namespace Kitchen\Ordertsk\Block\Order;
 
use Magento\Sales\Model\Order\Address;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;
use Magento\Sales\Model\OrderFactory;
 
/**
* Invoice view  comments form
*
* @api
* @author      Magento Core Team <core@magentocommerce.com>
* @since 100.0.2
*/
class Info extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Kitchen_Ordertsk::order/info.phtml';
 
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;
 
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;
 
    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;
    protected $orderFactory;
 
    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param array $data
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        OrderFactory $orderFactory,
        AddressRenderer $addressRenderer,
        
        array $data = []
    ) {
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->coreRegistry = $registry;
        $this->orderFactory = $orderFactory;
        $this->_isScopePrivate = true;
        parent::__construct($context, $data);
    }
 
    /**
     * @return void
     */
    protected function _prepareLayout()
    {
        $this->pageConfig->getTitle()->set(__('Order # %1', $this->getOrder()->getRealOrderId()));
        $infoBlock = $this->paymentHelper->getInfoBlock($this->getOrder()->getPayment(), $this->getLayout());
        $this->setChild('payment_info', $infoBlock);
    }
 
    /**
     * @return string
     */
    public function getPaymentInfoHtml()
    {
        return $this->getChildHtml('payment_info');
    }
 
    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }
 
    /**
     * Returns string with formatted address
     *
     * @param Address $address
     * @return null|string
     */
    public function getFormattedAddress(Address $address)
    {
        return $this->addressRenderer->format($address, 'html');
    }
 
    public function getShippingType()
    {
        $order = $this->getOrder();
        if ($order) {
            return $order->getShippingType();
        }
        return null;
    
    }
    public function getResidential()
    {
        $order = $this->getOrder();
        if ($order) {
            return $order->getResidential();
        }
        return null;
    
    }
    public function getLiftgate()
    {
        $order = $this->getOrder();
        if ($order) {
            return $order->getLiftgate();
        }
        return null;
    
    }
    public function getDeliveryAppointment()
    {
        $order = $this->getOrder();
        if ($order) {
            return $order->getDeliveryAppointment();
        }
        return null;
    
    }
 
}
 