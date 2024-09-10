<?php
 
namespace Kitchen\Ordertsk\Controller\Index;
 
class CustomShip extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $quoteFactory;
    protected $customerSession;
    protected $cartManagementInterface;
    protected $quoteRepository;
  
 
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->quoteFactory = $quoteFactory;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->quoteRepository = $quoteRepository;
    }
 
 
    public function execute()
{
    $result = $this->resultJsonFactory->create();
    $jsonData = $this->getRequest()->getContent();
    $data = json_decode($jsonData, true);

    $residentialType = isset($data['residential']) ? $data['residential'] : 'null';
    $liftgateType = isset($data['liftgate']) ? $data['liftgate'] : 'null';
    $deliveryType = isset($data['delivery']) ? $data['delivery'] : 'null';

    $customer = $this->customerSession->getCustomerDataObject();

    $quoteId = $this->cartManagementInterface->createEmptyCartForCustomer($customer->getId());

    $quotes = $this->quoteRepository->getActive($quoteId);

    $quotes->setResidential($residentialType);
    $quotes->setLiftgate($liftgateType);
    $quotes->setDeliveryAppointment($deliveryType);

    $this->quoteRepository->save($quotes);

    return $result->setData([
        'ID:' => $quotes->getId(),
        'Resi' => $residentialType,
        'Lift' => $liftgateType,
        'Deli' => $deliveryType,
        'message' => 'Active quote saved and updated'
    ]);
}

}
 