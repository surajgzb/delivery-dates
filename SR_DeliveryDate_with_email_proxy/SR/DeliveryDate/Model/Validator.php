<?php
namespace SR\DeliveryDate\Model;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException as LocalizedException;

class Validator
{
    const XML_PATH_MIN_DAYS = 'sales/delivery_settings/min_delivery_days';
    
    /**
     * @var DateTime
     */
    private $dateTime;

     /**
     * @var ScopConfigInterface
     */
    private $scopeConfig;


    /**
     * Validator constructor.
     *
     * @param DateTime $dateTime
     */
    public function __construct(
        DateTime $dateTime,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->dateTime = $dateTime;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param string $deliveryDate
     * @return bool
     */
    public function validate($deliveryDate)
    {
         if(!$deliveryDate) {
            return true; //optional field
        }
        
        if ($deliveryDate) {
            $deliveryDate = $this->dateTime->date('Y-m-d H:i:s', $deliveryDate);
            $now = $this->dateTime->date('Y-m-d H:i:s');
            if ($now > $deliveryDate) {
                throw new \Magento\Framework\Exception\LocalizedException(
                __('Invalid date selected, please select date after today.')
            );
            }
        }
        $todayDate = $this->dateTime->date('Y-m-d');

        $minDays = (int) $this->scopeConfig->getValue(self::XML_PATH_MIN_DAYS, ScopeInterface::SCOPE_STORE);

        if ($minDays < 0) {
            $minDays = 0;
        }

        $minValidDate = $this->dateTime->date('Y-m-d', strtotime("+{$minDays} days"));
        
        if ($deliveryDate < $minValidDate) {
            throw new LocalizedException(
                __('Delivery on the selected date is not possible. The earliest delivery is on ' . $minValidDate . ' or later.')
            );
        }

        return true;
    }
}
