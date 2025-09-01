<?php
namespace SR\DeliveryDate\Test\Unit\Plugin\Checkout\Block;

use PHPUnit\Framework\TestCase;
use SR\DeliveryDate\Plugin\Checkout\Block\LayoutProcessor;

class LayoutProcessorTest extends TestCase
{
    private LayoutProcessor $plugin;

    protected function setUp(): void
    {
        $this->plugin = new LayoutProcessor();
    }

    public function testAfterProcessAddsDeliveryDateField()
    {
        $jsLayout = ['components' => ['checkout' => ['children' => ['steps' => ['children' => ['shipping-step' => ['children' => ['shippingAddress' => ['children' => []]]]]]]]]]];

        $result = $this->plugin->afterProcess(new \stdClass(), $jsLayout);

        $this->assertArrayHasKey(
            'delivery_date',
            $result['components']['checkout']['children']['steps']['children']['shipping-step']['children']['shippingAddress']['children']
        );
    }
}
