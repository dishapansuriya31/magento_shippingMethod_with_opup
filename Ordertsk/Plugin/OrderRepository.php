<?php
// File: Roope/OrderComment/Plugin/OrderRepositoryPlugin.php
 
namespace Kitchen\Ordertsk\Plugin;
 
use Magento\Sales\Api\Data\OrderExtensionFactory;
use Magento\Sales\Api\Data\OrderExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
 
/**
* Class OrderRepositoryPlugin
*/
class OrderRepository {
  
  /**
   * Order Comment field name
   */
  // const FIELD_NAME = 'custom_status';
 
  /**
   * Order Extension Factory
   *
   * @var OrderExtensionFactory
   */
  protected $extensionFactory;
 
  /**
   * OrderRepositoryPlugin constructor
   *
   * @param OrderExtensionFactory $extensionFactory
   */
  public function __construct(OrderExtensionFactory $extensionFactory) {
    $this->extensionFactory = $extensionFactory;
  }
 
  /**
   * @param OrderRepositoryInterface $subject
   * @param OrderInterface $order
   * @return void
   */
  public function afterGet(OrderRepositoryInterface $subject, OrderInterface $order) {
 
    $shippingTypes = $order->getData('shipping_type');
    $residential = $order->getData('residential');
    $liftgate = $order->getData('liftgate');
    $delivery = $order->getData('delivery');
 
    // $orderComment = $order->getData(self::FIELD_NAME);
    $extensionAttributes = $order->getExtensionAttributes();
    $extensionAttributes = $extensionAttributes ? $extensionAttributes : $this->extensionFactory->create();
    // $extensionAttributes->setCustomStatus($orderComment ?? '');
    $shippingTypes = $shippingTypes ? $shippingTypes : '';
    $extensionAttributes->setShippingType($shippingTypes);
 
    $residential = $residential ? $residential : '';
    $extensionAttributes->setResidential($residential);
 
    $liftgate = $liftgate ? $liftgate : '';
    $extensionAttributes->setLiftgate($liftgate);
 
    $delivery = $delivery ? $delivery : '';
    $extensionAttributes->setDelivery($delivery);
 
    $order->setExtensionAttributes($extensionAttributes);
    return $order;
  }
 
  /**
   * @param OrderRepositoryInterface $subject
   * @param OrderSearchResultInterface $searchResult
   * @return void
   */
  public function afterGetList(OrderRepositoryInterface $subject, OrderSearchResultInterface $searchResult) {
    $orders = $searchResult->getItems();
    foreach ($orders as &$order) {
      $order = $this->afterGet($subject, $order);
    }
    return $searchResult;
  }
 
  /**
   * @param OrderRepositoryInterface $subject
   * @param OrderInterface $order
   * @return void
   */
  public function beforeSave(OrderRepositoryInterface $subject, OrderInterface $order) {
    $extensionAttributes = $order->getExtensionAttributes() ?: $this->extensionFactory->create();
    if ($extensionAttributes !== null && $extensionAttributes->getShippingType() !== null) {
      $comment = $extensionAttributes->getShippingType();
      $order->setShippingType($comment);
    }
    return [$order];
  }
 
}