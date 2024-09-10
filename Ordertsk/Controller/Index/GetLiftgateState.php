<?php
namespace Kitchen\Ordertsk\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\ObjectManager;

class GetLiftgateState extends Action
{
    /**
     * @var JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    /**
     * Retrieve the state of the liftgate option
     *
     * @return \Magento\Framework\Controller\Result\Json
     * @throws NotFoundException
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        try {
            // Logic to determine the state of the liftgate option
            $liftgateChecked = $this->isLiftgateChecked();

            return $result->setData(['liftgateChecked' => $liftgateChecked]);
        } catch (\Exception $e) {
            return $result->setHttpResponseCode(\Magento\Framework\Webapi\Exception::HTTP_INTERNAL_ERROR)
                ->setData(['error' => $e->getMessage()]);
        }
    }

    /**
     * Example logic to determine if the liftgate option is checked
     *
     * @return bool
     */
    protected function isLiftgateChecked()
    {
        // Add your logic here to determine if the liftgate option is checked
        // For demonstration purposes, I'll return a hardcoded value
        return true; // Set to true if the liftgate option is checked, false otherwise
    }
}
