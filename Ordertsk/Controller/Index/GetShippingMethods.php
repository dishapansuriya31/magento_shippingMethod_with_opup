<?php
namespace Kitchen\Ordertsk\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Config as ShippingConfig;

class GetShippingMethods extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var ShippingConfig
     */
    protected $shippingConfig;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * GetShippingMethods constructor.
     *
     * @param Context       $context
     * @param JsonFactory   $resultJsonFactory
     * @param ShippingConfig $shippingConfig
     * @param ResultFactory $resultFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ShippingConfig $shippingConfig,
        ResultFactory $resultFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->shippingConfig = $shippingConfig;
        $this->resultFactory = $resultFactory;
    }

    /**
     * Execute view action
     *
     * @return \Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $shippingType = $this->getRequest()->getParam('shipping_type');

        // Check if shipping type is blank or invalid
        if (empty($shippingType) || !$this->isValidShippingType($shippingType)) {
            // Redirect customer to cart page
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('checkout/cart');
            return $resultRedirect;
        }

        // If shipping type is valid, proceed to get shipping methods
        // Your logic to get shipping methods based on the selected shipping type...
    }

    /**
     * Check if the shipping type is valid
     *
     * @param string $shippingType
     * @return bool
     */
    protected function isValidShippingType($shippingType)
    {
        // Your validation logic goes here
        // For example, you can check against a predefined list of valid shipping types
        // Return true if the shipping type is valid, otherwise return false
        return in_array($shippingType, ['Shipping', 'Pickup', 'Dealer Arrange Shipping']);
    }
}
