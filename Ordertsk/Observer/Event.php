<?php
namespace Kitchen\Ordertsk\Observer;
 
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\UrlInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
 
class Event implements ObserverInterface
{
    protected $request;
    protected $response;
    protected $messageManager;
    protected $quoteFactory;
    protected $checkoutSession;
    protected $url;
 
    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        ManagerInterface $messageManager,
        QuoteFactory $quoteFactory,
        CheckoutSession $checkoutSession,
        UrlInterface $url
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->messageManager = $messageManager;
        $this->quoteFactory = $quoteFactory;
        $this->checkoutSession = $checkoutSession;
        $this->url = $url;
    }
 
    public function execute(Observer $observer)
    {
        $quote = $this->checkoutSession->getQuote();
        $shipType = $quote->getShippingType();
 
        if (!$shipType) {
            $this->messageManager->addNoticeMessage('Please select a shipping type.');
            $redirectUrl = $this->url->getUrl('checkout/cart');
 
            $this->response->setRedirect($redirectUrl)->send();
            return;  
        }
    }
}
 