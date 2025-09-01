<?php
namespace SR\DeliveryDate\Test\Unit\Observer;

use PHPUnit\Framework\TestCase;
use SR\DeliveryDate\Observer\SaveDeliveryDateToOrder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event;

class SaveDeliveryDateToOrderTest extends TestCase
{
    public function testExecuteCopiesDeliveryDate()
    {
        $observer = $this->createMock(Observer::class);
        $event = $this->createMock(Event::class);
        $order = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setDeliveryDate'])
            ->getMock();
        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDeliveryDate'])
            ->getMock();

        $quote->method('getDeliveryDate')->willReturn('2025-09-01');
        $event->method('getOrder')->willReturn($order);
        $event->method('getQuote')->willReturn($quote);
        $observer->method('getEvent')->willReturn($event);

        $order->expects($this->once())->method('setDeliveryDate')->with('2025-09-01');

        $observerInstance = new SaveDeliveryDateToOrder();
        $observerInstance->execute($observer);
    }
}
