<?php
namespace SR\DeliveryDate\Model;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * @covers \SR\DeliveryDate\Model\Validator
 */
class ValidatorTest extends TestCase
{
    /**
     * @var DateTime|\PHPUnit\Framework\MockObject\MockObject
     */
    private $dateTimeMock;

    /**
     * @var ScopeConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scopeConfigMock;

    /**
     * @var Validator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->dateTimeMock = $this->createMock(DateTime::class);
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->validator = new Validator($this->dateTimeMock, $this->scopeConfigMock);
    }

    public function testEmptyDeliveryDateReturnsTrue()
    {
        $this->assertTrue($this->validator->validate(''));
    }

    public function testPastDateThrowsException()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid date selected, please select date after today.');

        $currentDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $pastDate = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('-1 day')->format('Y-m-d');

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $pastDate) {
                if ($format !== 'Y-m-d' && $format !== 'Y-m-d H:i:s') {
                    throw new \InvalidArgumentException('Only Y-m-d or Y-m-d H:i:s format is supported');
                }
                if ($input === null) {
                    return $format === 'Y-m-d' ? $currentDate : $currentDate . ' 00:00:00';
                }
                return $format === 'Y-m-d' ? $pastDate : $pastDate . ' 00:00:00';
            });

        $this->validator->validate($pastDate);
    }

    public function testDateBeforeMinDaysThrowsException()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/Delivery on the selected date is not possible. The earliest delivery is on .* or later./');

        $currentDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $deliveryDate = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+1 day')->format('Y-m-d');
        $minValidDate = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 days')->format('Y-m-d');
        $minDays = 2;
        $minDaysTimestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->modify("+{$minDays} days")->getTimestamp();

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $deliveryDate, $minValidDate, $minDaysTimestamp) {
                if ($format !== 'Y-m-d' && $format !== 'Y-m-d H:i:s') {
                    throw new \InvalidArgumentException('Only Y-m-d or Y-m-d H:i:s format is supported');
                }
                if ($input === null) {
                    return $format === 'Y-m-d' ? $currentDate : $currentDate . ' 00:00:00';
                }
                if ($input === $deliveryDate) {
                    return $format === 'Y-m-d' ? $deliveryDate : $deliveryDate . ' 00:00:00';
                }
                if (is_numeric($input) && $input === $minDaysTimestamp) {
                    return $format === 'Y-m-d' ? $minValidDate : $minValidDate . ' 00:00:00';
                }
                return $format === 'Y-m-d' ? $input : $input . ' 00:00:00';
            });

        $this->scopeConfigMock->method('getValue')
            ->with(Validator::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE)
            ->willReturn($minDays);

        $this->validator->validate($deliveryDate);
    }

    public function testValidDateReturnsTrue()
    {
        $currentDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $validDate = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+3 days')->format('Y-m-d');
        $minValidDate = (new \DateTime('now', new \DateTimeZone('UTC')))->modify('+2 days')->format('Y-m-d');
        $minDays = 2;
        $minDaysTimestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->modify("+{$minDays} days")->getTimestamp();

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $validDate, $minValidDate, $minDaysTimestamp) {
                if ($format !== 'Y-m-d' && $format !== 'Y-m-d H:i:s') {
                    throw new \InvalidArgumentException('Only Y-m-d or Y-m-d H:i:s format is supported');
                }
                if ($input === null) {
                    return $format === 'Y-m-d' ? $currentDate : $currentDate . ' 00:00:00';
                }
                if ($input === $validDate) {
                    return $format === 'Y-m-d' ? $validDate : $validDate . ' 00:00:00';
                }
                if (is_numeric($input) && $input === $minDaysTimestamp) {
                    return $format === 'Y-m-d' ? $minValidDate : $minValidDate . ' 00:00:00';
                }
                return $format === 'Y-m-d' ? $input : $input . ' 00:00:00';
            });

        $this->scopeConfigMock->method('getValue')
            ->with(Validator::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE)
            ->willReturn($minDays);

        $this->assertTrue($this->validator->validate($validDate));
    }

    public function testNegativeMinDaysIsTreatedAsZero()
    {
        $currentDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $sameDate = (new \DateTime('now', new \DateTimeZone('UTC')))->format('Y-m-d');
        $zeroDaysTimestamp = (new \DateTime('now', new \DateTimeZone('UTC')))->getTimestamp();

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $sameDate, $zeroDaysTimestamp) {
                if ($format !== 'Y-m-d' && $format !== 'Y-m-d H:i:s') {
                    throw new \InvalidArgumentException('Only Y-m-d or Y-m-d H:i:s format is supported');
                }
                if ($input === null) {
                    return $format === 'Y-m-d' ? $currentDate : $currentDate . ' 00:00:00';
                }
                if ($input === $sameDate) {
                    return $format === 'Y-m-d' ? $sameDate : $sameDate . ' 00:00:00';
                }
                if (is_numeric($input) && $input === $zeroDaysTimestamp) {
                    return $format === 'Y-m-d' ? $currentDate : $currentDate . ' 00:00:00';
                }
                return $format === 'Y-m-d' ? $input : $input . ' 00:00:00';
            });

        $this->scopeConfigMock->method('getValue')
            ->with(Validator::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE)
            ->willReturn(-1);

        $this->assertTrue($this->validator->validate($sameDate));
    }
}
