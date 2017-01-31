<?php

class FoxyStripeOptionInventoryManager extends DataExtension
{
    /**
     * @var array
     */
    private static $db = [
        'ControlInventory' => 'Boolean',
        'PurchaseLimit' => 'Int',
    ];

    /**
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $fields->removeByName(array(
            'PurchaseLimit',
            'EmbargoLimit',
            'NumberPurchased',
        ));

        $fields->addFieldsToTab('Root.Inventory', array(
            CheckboxField::create('ControlInventory', 'Control Inventory?')
                ->setDescription('limit the number of this product available for purchase'),
            DisplayLogicWrapper::create(
                NumericField::create('PurchaseLimit')
                    ->setTitle('Number Available')
                    ->setDescription('add to cart form will be disabled once number available equals purchased'),
                ReadonlyField::create('NumberPurchased', 'Purchased', $this->getNumberPurchased())//,
            )->displayIf('ControlInventory')->isChecked()->end(),
        ));
    }

    /**
     * @return bool
     */
    public function getHasInventory()
    {
        return $this->owner->ControlInventory && $this->owner->PurchaseLimit != 0;
    }

    /**
     * @return bool
     */
    public function getIsOptionAvailable()
    {
        if ($this->getHasInventory()) {
            return $this->owner->PurchaseLimit > $this->getNumberPurchased();
        }
        return true;
    }

    /**
     * @return int
     */
    public function getNumberAvailable()
    {
        if ($this->getIsOptionAvailable()) {
            return (int)$this->owner->PurchaseLimit - (int)$this->getNumberPurchased();
        }
    }

    /**
     * @return int
     */
    public function getNumberPurchased()
    {
        $ct = 0;
        if ($this->getOrders()) {
            foreach ($this->getOrders() as $order) {
                $ct += $order->Quantity;
            }
        }
        return $ct;
    }

    /**
     * @return DataList
     */
    public function getOrders()
    {
        if ($this->owner->ID) {
            return OrderDetail::get()->filter('Options.ID', $this->owner->ID);
        }
        return false;
    }

    /**
     * @param $available
     */
    public function updateOptionAvailability(&$available)
    {
        if ($this->getHasInventory()) {
            $available = $this->getIsOptionAvailable();
        }
    }
}