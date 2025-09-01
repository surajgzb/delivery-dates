<?php
namespace SR\DeliveryDate\Test\Unit\Model\Validator;

use PHPUnit\Framework\TestCase;
use SR\DeliveryDate\Model\Validator\DeliveryDateValidator;

class DeliveryDateValidatorTest extends TestCase
{
    private DeliveryDateValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new DeliveryDateValidator();
    }

    public function testValidateRejectsEmpty()
    {
        $this->assertFalse($this->validator->validate(''));
    }

    public function testValidateRejectsPastDate()
    {
        $this->assertFalse($this->validator->validate('2000-01-01'));
    }

    public function testValidateAcceptsTodayOrFuture()
    {
        $this->assertTrue($this->validator->validate(date('Y-m-d')));
        $this->assertTrue($this->validator->validate(date('Y-m-d', strtotime('+1 day'))));
    }
}
