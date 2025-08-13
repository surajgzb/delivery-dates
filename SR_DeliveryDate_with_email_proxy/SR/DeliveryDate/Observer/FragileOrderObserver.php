<?php
namespace SR\DeliveryDate\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use SR\DeliveryDate\Model\Email\WarehouseNotifier;

class FragileOrderObserver implements ObserverInterface
{
    private $warehouseNotifier;

    public function __construct(WarehouseNotifier $warehouseNotifier)
    {
        $this->warehouseNotifier = $warehouseNotifier;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();
        $deliveryInstructions = $order->getDeliveryComment();

        if ($deliveryInstructions && stripos($deliveryInstructions, 'fragile') !== false) {
            $this->warehouseNotifier->sendWarehouseAlert($order);
        }
    }
}
