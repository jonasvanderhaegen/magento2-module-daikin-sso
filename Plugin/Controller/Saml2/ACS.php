<?php

namespace Jvdh\DaikinSsoProcessing\Plugin\Controller\Saml2;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Jvdh\DaikinSsoProcessing\Helper\Data;

use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;

use Foobar\SAML\Controller\Saml2\ACS as FoobarACS;

class ACS
{
    /**
     * Constructor method to inject dependencies
     *
     * @param ResultFactory $resultFactory Factory for creating result objects
     * @param ManagerInterface $messageManager Handles user-facing messages
     * @param Session $checkoutSession Manages the current checkout session
     * @param CustomerRepositoryInterface $customerRepository Handles customer data retrieval and storage
     * @param CartRepositoryInterface $quoteRepository Handles cart/quote data
     * @param Data $helperData Helper instance for SSO-related operations
     */
    public function __construct(
        protected ResultFactory $resultFactory,
        protected ManagerInterface $messageManager,
        private Session $checkoutSession,
        protected CustomerRepositoryInterface $customerRepository,
        private CartRepositoryInterface $quoteRepository,
        protected Data $helperData
    ) {
    }

    /**
     * After plugin for the ACS controller action to handle customer group assignment
     *
     * This method checks whether the SSO module is enabled and processes the SAML response to assign
     * the correct customer group if the customer does not already exist in the session.
     *
     * @param FoobarACS $subject The original ACS controller instance
     * @param mixed $result The original method's result
     * @return \Magento\Framework\Controller\Result\Redirect|
     *         \Magento\Framework\Controller\Result\Redirect&\Magento\Framework\Controller\ResultInterface|mixed
     * @throws NoSuchEntityException If the customer does not exist
     * @throws \Magento\Framework\Exception\InputException For invalid input
     * @throws \Magento\Framework\Exception\LocalizedException For localization-related errors
     * @throws \Magento\Framework\Exception\State\InputMismatchException For state-related errors
     */
    public function afterExecute(FoobarACS $subject, $result)
    {
        // Check if the SSO module is enabled
        if (!$this->helperData->isModuleEnabled()) {
            return $result;
        }

        // Check if the customer update event has already been dispatched
        if ($this->helperData->ssoCustomerUpdatedEventIsDispatched()) {
            return $result;
        }

        // Retrieve the customer from the session
        $customerSession = $subject->_getCustomerSession();
        $customer = $customerSession->getCustomer();
        $customerModel = $this->customerRepository->getById($customer->getId());

        // Process SAML response and extract attributes
        $auth = $subject->_getSAMLAuth();
        $auth->processResponse();
        $attributes = $auth->getAttributes();

        try {
            // Resolve the customer group ID based on the SAML attributes
            $groupId = $this->helperData->resolveCustomerGroupId($attributes);
        } catch (\Exception $e) {
            // Display an error message and redirect to the logout page on failure
            $this->messageManager->addError(__($e->getMessage()));
            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl('/customer/account/logout/');
        }

        // Assign the resolved group ID to the customer
        $customerModel->setGroupId($groupId);
        $customerSession->setCustomerGroupId($groupId);
        $this->customerRepository->save($customerModel);

        // Update the customer's quote with the new group ID
        $quote = $this->checkoutSession->getQuote();
        $quote->setCustomerGroupId($groupId);
        $quote->setCustomer($customerModel);
        $this->quoteRepository->save($quote);

        return $result;
    }
}
