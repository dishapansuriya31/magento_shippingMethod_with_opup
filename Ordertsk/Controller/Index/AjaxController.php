<?php
namespace Kitchen\Oredertsk\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class AjaxController extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        // Your AJAX request handling code here
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['message' => 'AJAX request successful']);
        return $resultJson;
    }
}
