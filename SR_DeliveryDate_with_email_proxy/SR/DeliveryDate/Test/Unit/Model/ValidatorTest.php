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

        $currentDate = (new \DateTime())->format('Y-m-d');
        $pastDate = (new \DateTime())->modify('-1 day')->format('Y-m-d');

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $pastDate) {
                if ($format !== 'Y-m-d') {
                    throw new \InvalidArgumentException('Only Y-m-d format is supported');
                }
                if ($input === null) {
                    return $currentDate;
                }
                return $pastDate;
            });

        $this->validator->validate($pastDate);
    }

    public function testDateBeforeMinDaysThrowsException()
    {
        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessageMatches('/Delivery on the selected date is not possible. The earliest delivery is on .* or later./');

        $currentDate = (new \DateTime())->format('Y-m-d');
        $deliveryDate = (new \DateTime())->modify('+1 day')->format('Y-m-d');
        $minValidDate = (new \DateTime())->modify('+2 days')->format('Y-m-d');

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $deliveryDate, $minValidDate) {
                if ($format !== 'Y-m-d') {
                    throw new \InvalidArgumentException('Only Y-m-d format is supported');
                }
                if ($input === null) {
                    return $currentDate;
                }
                if ($input === $deliveryDate) {
                    return $deliveryDate;
                }
                if (strpos($input, '+2 days') !== false) {
                    return $minValidDate;
                }
                return $input;
            });

        $this->scopeConfigMock->method('getValue')
            ->with(Validator::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE)
            ->willReturn(2);

        $this->validator->validate($deliveryDate);
    }

    public function testValidDateReturnsTrue()
    {
        $currentDate = (new \DateTime())->format('Y-m-d');
        $validDate = (new \DateTime())->modify('+3 days')->format('Y-m-d');
        $minValidDate = (new \DateTime())->modify('+2 days')->format('Y-m-d');

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $validDate, $minValidDate) {
                if ($format !== 'Y-m-d') {
                    throw new \InvalidArgumentException('Only Y-m-d format is supported');
                }
                if ($input === null) {
                    return $currentDate;
                }
                if ($input === $validDate) {
                    return $validDate;
                }
                if (strpos($input, '+2 days') !== false) {
                    return $minValidDate;
                }
                return $input;
            });

        $this->scopeConfigMock->method('getValue')
            ->with(Validator::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE)
            ->willReturn(2);

        $this->assertTrue($this->validator->validate($validDate));
    }

    public function testNegativeMinDaysIsTreatedAsZero()
    {
        $currentDate = (new \DateTime())->format('Y-m-d');
        $sameDate = (new \DateTime())->format('Y-m-d');

        $this->dateTimeMock->method('date')
            ->willReturnCallback(function ($format, $input = null) use ($currentDate, $sameDate) {
                if ($format !== 'Y-m-d') {
                    throw new \InvalidArgumentException('Only Y-m-d format is supported');
                }
                if ($input === null) {
                    return $currentDate;
                }
                if ($input === $sameDate) {
                    return $sameDate;
                }
                if (strpos($input, '+0 days') !== false) {
                    return $currentDate;
                }
                return $input;
            });

        $this->scopeConfigMock->method('getValue')
            ->with(Validator::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE)
            ->willReturn(-1);

        $this->assertTrue($this->validator->validate($sameDate));
    }
}
