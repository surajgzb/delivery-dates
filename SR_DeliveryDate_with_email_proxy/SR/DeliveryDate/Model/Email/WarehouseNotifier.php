<?php
namespace SR\DeliveryDate\Model\Email;

use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;

class WarehouseNotifier
{
    private $transportBuilder;
    private $storeManager;

    public function __construct(
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager
    ) {
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
    }

    public function sendWarehouseAlert($order)
    {
        $transport = $this->transportBuilder
            ->setTemplateIdentifier('warehouse_alert_email_template')
            ->setTemplateOptions([
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $this->storeManager->getStore()->getId()
            ])
            ->setTemplateVars([
                'order' => $order
            ])
            ->setFrom([
                'name' => 'Store System',
                'email' => 'noreply@example.com'
            ])
            ->addTo('warehouse@example.com')
            ->getTransport();

        $transport->sendMessage();
    }
}
