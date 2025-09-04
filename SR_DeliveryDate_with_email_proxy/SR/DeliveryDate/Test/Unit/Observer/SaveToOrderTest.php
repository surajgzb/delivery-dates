<?php
namespace Customise\DeliveryDate\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\QuoteRepository;
use Customise\DeliveryDate\Model\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;

/**
 * @covers \Customise\DeliveryDate\Observer\SaveToOrder
 */
class SaveToOrderTest extends TestCase
{
    /**
     * @var QuoteRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $quoteRepositoryMock;

    /**
     * @var Validator|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validatorMock;

    /**
     * @var ManagerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $messageManagerMock;

    /**
     * @var SaveToOrder
     */
    private $observer;

    protected function setUp(): void
    {
        $this->quoteRepositoryMock = $this->createMock(QuoteRepository::class);
        $this->validatorMock = $this->createMock(Validator::class);
        $this->messageManagerMock = $this->createMock(ManagerInterface::class);
        $this->observer = new SaveToOrder(
            $this->quoteRepositoryMock,
            $this->validatorMock,
            $this->messageManagerMock
        );
    }

    public function testExecuteSetsOrderDataAndReturnsSelfWithValidDeliveryDate()
    {
        // Use new \DateTime() for dynamic dates
        $currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $validDeliveryDate = (clone $currentDateTime)->modify('+3 days')->format('Y-m-d');
        $deliveryInstructions = 'Deliver after 2 PM';

        // Mock the observer and order
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->createMock(Order::class);
        $quoteMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getDeliveryDate', 'getDeliveryInstructions'])
            ->getMock();

        // Mock observer to return order via getData
        $observerMock->expects($this->atLeastOnce())
            ->method('getData')
            ->with('order')
            ->willReturn($orderMock);

        // Mock order to return quote ID
        $orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(123);

        // Track setData calls for verification
        $setDataCalls = [];
        $orderMock->method('setData')
            ->willReturnCallback(function ($key, $value) use (&$setDataCalls) {
                $setDataCalls[] = [$key, $value];
                return $this->returnSelf();
            });

        // Mock order to expect exactly two setData calls
        $orderMock->expects($this->exactly(2))
            ->method('setData')
            ->willReturnSelf();

        // Mock quote repository to return quote
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($quoteMock);

        // Mock quote to return delivery date and instructions
        $quoteMock->expects($this->once())
            ->method('getDeliveryDate')
            ->willReturn($validDeliveryDate);
        $quoteMock->expects($this->once())
            ->method('getDeliveryInstructions')
            ->willReturn($deliveryInstructions);

        // Mock validator to accept the valid delivery date
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($validDeliveryDate)
            ->willReturn(true);

        // Mock message manager to ensure no error messages are added
        $this->messageManagerMock->expects($this->never())
            ->method('addErrorMessage');

        // Execute the observer
        $result = $this->observer->execute($observerMock);

        // Verify setData calls
        $this->assertEquals([
            ['delivery_instructions', $deliveryInstructions],
            ['delivery_date', $validDeliveryDate]
        ], $setDataCalls);

        // Verify observer returns itself
        $this->assertSame($this->observer, $result);
    }
}
