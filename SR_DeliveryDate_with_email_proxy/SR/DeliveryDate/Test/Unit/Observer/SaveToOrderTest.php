<?php
namespace Customise\DeliveryDate\Observer;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\QuoteRepository;
use Customise\DeliveryDate\Model\Validator;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\Quote;
use Magento\Framework\Exception\LocalizedException;

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
        $currentDate = $currentDateTime->format('Y-m-d');
        $validDeliveryDate = (clone $currentDateTime)->modify('+3 days')->format('Y-m-d');
        $deliveryInstructions = 'Deliver after 2 PM';

        // Mock the observer and order
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->createMock(Order::class);
        $quoteMock = $this->createMock(Quote::class);

        // Mock observer to return order
        $observerMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        // Mock order to return quote ID and allow setting data
        $orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(123);
        $orderMock->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['delivery_instructions', $deliveryInstructions],
                ['delivery_date', $validDeliveryDate]
            )
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

        // Execute the observer and verify it returns itself
        $result = $this->observer->execute($observerMock);
        $this->assertSame($this->observer, $result);
    }

    public function testExecuteThrowsExceptionForInvalidDeliveryDate()
    {
        // Use new \DateTime() for dynamic dates
        $currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $invalidDeliveryDate = (clone $currentDateTime)->modify('-1 day')->format('Y-m-d');

        // Mock the observer and order
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->createMock(Order::class);
        $quoteMock = $this->createMock(Quote::class);

        // Mock observer to return order
        $observerMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        // Mock order to return quote ID
        $orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(123);
        $orderMock->expects($this->never())
            ->method('setData');

        // Mock quote repository to return quote
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($quoteMock);

        // Mock quote to return invalid delivery date
        $quoteMock->expects($this->once())
            ->method('getDeliveryDate')
            ->willReturn($invalidDeliveryDate);

        // Mock validator to throw exception for invalid date
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with($invalidDeliveryDate)
            ->willThrowException(new LocalizedException(__('Invalid date selected, please select date after today.')));

        // Mock message manager to ensure no error messages are added
        $this->messageManagerMock->expects($this->never())
            ->method('addErrorMessage');

        // Expect the LocalizedException to be thrown
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid date selected, please select date after today.');

        $this->observer->execute($observerMock);
    }

    public function testExecuteHandlesEmptyDeliveryDate()
    {
        // Use new \DateTime() for dynamic current date
        $currentDateTime = new \DateTime('now', new \DateTimeZone('UTC'));
        $currentDate = $currentDateTime->format('Y-m-d');
        $deliveryInstructions = 'Deliver to back door';

        // Mock the observer and order
        $observerMock = $this->createMock(Observer::class);
        $orderMock = $this->createMock(Order::class);
        $quoteMock = $this->createMock(Quote::class);

        // Mock observer to return order
        $observerMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        // Mock order to return quote ID and allow setting data
        $orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(123);
        $orderMock->expects($this->exactly(2))
            ->method('setData')
            ->withConsecutive(
                ['delivery_instructions', $deliveryInstructions],
                ['delivery_date', '']
            )
            ->willReturnSelf();

        // Mock quote repository to return quote
        $this->quoteRepositoryMock->expects($this->once())
            ->method('get')
            ->with(123)
            ->willReturn($quoteMock);

        // Mock quote to return empty delivery date and instructions
        $quoteMock->expects($this->once())
            ->method('getDeliveryDate')
            ->willReturn('');
        $quoteMock->expects($this->once())
            ->method('getDeliveryInstructions')
            ->willReturn($deliveryInstructions);

        // Mock validator to accept empty delivery date
        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with('')
            ->willReturn(true);

        // Mock message manager to ensure no error messages are added
        $this->messageManagerMock->expects($this->never())
            ->method('addErrorMessage');

        // Execute the observer and verify it returns itself
        $result = $this->observer->execute($observerMock);
        $this->assertSame($this->observer, $result);
    }
}
