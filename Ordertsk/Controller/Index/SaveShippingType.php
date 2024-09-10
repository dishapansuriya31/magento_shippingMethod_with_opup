<?php

namespace Kitchen\Ordertsk\Controller\Index;

class SaveShippingType extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $quoteFactory;
    protected $cartManagementInterface;
    protected $checkoutSession;
    protected $customerSession;
    protected $quoteRepository;

    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->quoteFactory = $quoteFactory;
        $this->quoteRepository = $quoteRepository;
    }

    public function execute()   
    {
        $result = $this->resultJsonFactory->create();
        $jsonData = $this->getRequest()->getContent();
        $data = json_decode($jsonData, true);

        $shippingData = 'null';

        if (isset($data['value'])) {
            $shippingData = $data['value'];
        }
        $customer = $this->customerSession->getCustomerDataObject();

        $quoteId = $this->cartManagementInterface->createEmptyCartForCustomer($customer->getId());

        $quotes = $this->quoteRepository->getActive($quoteId);
        // $model = $this->quoteFactory->create();
        // $quote  = $model->get($quoteId);    
        $quotes->setShippingType($shippingData);
        $this->quoteRepository->save($quotes);   



        // $quote = $this->checkoutSession->getQuote();

        // $quote->assignCustomer($this->customerSession->getCustomerDataObject());

        // return $quote;

        return $result->setData([
            'ID:' => $quotes->getId(),
            'shipType' => $shippingData, 
            'message' => 'Active quote save, updated'
        ]);
    }
}
