<?php
namespace Customise\DeliveryDate\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Quote\Model\QuoteRepository;
use Customise\DeliveryDate\Model\Validator;
use Magento\Framework\Message\ManagerInterface;

class SaveToOrder implements ObserverInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    
    private $messageManager;


    /**
     * @var $validator
     */
    private $validator;

    public function __construct(
        QuoteRepository $quoteRepository,
        Validator $validator,
        ManagerInterface  $messageManager
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->messageManager = $messageManager;
        $this->validator = $validator;
    }

    public function execute(Observer $observer)
    {
        $order = $observer->getOrder();
      
        $quote = $this->quoteRepository->get($order->getQuoteId());
        $this->validator->validate($quote->getDeliveryDate());

        $order->setData('delivery_instructions', $quote->getDeliveryInstructions());
        $order->setData('delivery_date', $quote->getDeliveryDate());
        
        return $this;
    }
}
