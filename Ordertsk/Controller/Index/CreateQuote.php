<?php
namespace Kitchen\Ordertsk\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Model\QuoteRepository;
use Magento\Customer\Model\Session as CustomerSession;

class CreateQuote extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var QuoteFactory
     */
    protected $shippingQuoteFactory;

    /**
     * @var CartManagementInterface
     */
    protected $cartManagementInterface;

    /**
     * @var QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param QuoteFactory $shippingQuoteFactory
     * @param CartManagementInterface $cartManagementInterface
     * @param QuoteRepository $quoteRepository
     * @param CustomerSession $customerSession
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        QuoteFactory $shippingQuoteFactory,
        CartManagementInterface $cartManagementInterface,
        QuoteRepository $quoteRepository,
        CustomerSession $customerSession
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->shippingQuoteFactory = $shippingQuoteFactory;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->quoteRepository = $quoteRepository;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute action
     */
    public function execute()
    {
        $customerId = $this->customerSession->getCustomerId();

        $quoteId = $this->cartManagementInterface->createEmptyCartForCustomer($customerId);
        $quote = $this->quoteRepository->get($quoteId);
        $this->quoteRepository->save($quote);
        $quote->assignCustomer($this->customerSession->getCustomerDataObject());

        $quoteData = [
            'quote_id' => $quote->getId(),
            'customer_id' => $quote->getCustomerId(),
            // Add any other quote data you need to return
        ];

        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData($quoteData);
    }
}
