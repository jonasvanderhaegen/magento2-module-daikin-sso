<?php

namespace Jvdh\DaikinSsoProcessing\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Jvdh\DaikinSsoProcessing\Helper\Data;

class Saml2CustomerSuccessfullyUpdated implements ObserverInterface
{
    /**
     * @var Data Helper class to manage SSO customer updates
     */
    protected Data $helperData;

    /**
     * Constructor method to inject the Data helper dependency
     *
     * @param Data $helperData Helper instance for handling SSO-related operations
     */
    public function __construct(Data $helperData)
    {
        $this->helperData = $helperData;
    }

    /**
     * Handles the event triggered when a customer is successfully updated through SAML2
     *
     * This method sets a flag in the helper to indicate that the customer update
     * event has been dispatched. This ensures that plugins or other components relying
     * on this state can act accordingly.
     *
     * @param Observer $observer The event observer instance
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $this->helperData->dispatchedSsoCustomerUpdatedEvent();
    }
}
