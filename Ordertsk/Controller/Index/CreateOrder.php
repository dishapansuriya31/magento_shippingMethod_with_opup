<?php
namespace Kitchen\Ordertsk\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class CreateOrder extends Action
{
    protected $quoteFactory;
    protected $quoteResource;
    protected $orderFactory;
    protected $orderSender;

    public function __construct(
        Context $context,
        QuoteFactory $quoteFactory,
        QuoteResource $quoteResource,
        OrderFactory $orderFactory,
        OrderSender $orderSender
    ) {
        parent::__construct($context);
        $this->quoteFactory = $quoteFactory;
        $this->quoteResource = $quoteResource;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
    }

    public function execute()
    {
        try {
            // Load active quote
            $quote = $this->quoteFactory->create()->loadActive($this->_objectManager->get('Magento\Customer\Model\Session')->getCustomer()->getId());

            if (!$quote->getId()) {
                throw new \Exception('No active quote found.');
            }

            // Convert quote to order
            $order = $this->quoteManagement->submit($quote);
            $this->quoteResource->delete($quote);

            // Send order email
            $this->orderSender->send($order);

            // Redirect to order view page
            $this->_redirect('sales/order/view/order_id/' . $order->getId());
        } catch (\Exception $e) {
            // Handle exception
            $this->messageManager->addError(__('An error occurred while creating the order: %1', $e->getMessage()));
            $this->_redirect('checkout/cart');
        }
    }
}
