<?php
namespace Jvdh\DaikinSsoProcessing\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;

class Data extends AbstractHelper
{
    /**
     * @var bool Flag to track whether the customer update event has been dispatched
     */
    protected bool $customerUpdatedEvent = false;

    /**
     * @var array Defines the priority order for group mapping
     */
    private static array $groupPriorityMapping = [
        'internal',
        'distributor',
        'dealer',
        'consultant'
    ];

    /**
     * @var array List of allowed email domains for specific group mapping
     * This data should be securely stored in a configuration file or environment variable.
     */
    private static array $emailDomains = [
        // Placeholder values; replace with environment configuration retrieval
        getenv('DAIKIN_EMAIL_DOMAINS') ? explode(',', getenv('DAIKIN_EMAIL_DOMAINS')) : []
    ];

    /**
     * @var array Mapping of customer groups with their associated codes, countries, and specific emails
     * This data should ideally be retrieved from a secure configuration source or database.
     */
    private static array $customerGroupMapping = [
        // Example structure; real data should be securely stored externally
        13 => [
            'countries' => [
                'Czechia', 'CZ', 'Slovakia', 'SK', 'Romania', 'RO', 'Hungary', 'HU', 'Austria', 'AT', 'Serbia', 'RS'
            ],
            'codes' => [
                'foobar_internal'
            ]
        ],
        14 => [
            'countries' => [
                'Czechia', 'CZ', 'Slovakia', 'SK', 'Romania', 'RO', 'Hungary', 'HU', 'Austria', 'AT', 'Serbia', 'RS', 'Bulgaria', 'BG'
            ],
            'codes' => [
                'foobar_dealer1',
            ]
        ],
        20 => [
            'countries' => [
                'Belgium', 'BE'
            ],
            'codes' => [
                'foobar_internal'
            ],
            'emails' => [
                getenv('DAIKIN_ADMIN_EMAILS') ? explode(',', getenv('DAIKIN_ADMIN_EMAILS')) : []
            ]
        ],
        // Other groups follow a similar secure configuration retrieval pattern
    ];

    /**
     * Get the current state of the customerUpdatedEvent flag
     *
     * @return bool Indicates if the customer updated event has been dispatched
     */
    public function ssoCustomerUpdatedEventIsDispatched(): bool
    {
        return $this->customerUpdatedEvent;
    }

    /**
     * Mark the customerUpdatedEvent flag as dispatched
     *
     * @return void
     */
    public function dispatchedSsoCustomerUpdatedEvent(): void
    {
        $this->customerUpdatedEvent = true;
    }

    /**
     * Check if the module is enabled in the configuration settings
     *
     * @return bool True if the module is enabled, false otherwise
     */
    public function isModuleEnabled(): bool
    {
        return $this->scopeConfig->getValue(
            'foobar_saml_customer/advanced/enabled_for_daikin',
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Check if the given email belongs to one of the predefined domains
     *
     * @param string $email The email to validate
     * @return bool True if the email domain matches, false otherwise
     */
    protected function isOneOfEmailDomains(string $email): bool
    {
        foreach (self::$emailDomains as $domain) {
            if (preg_match("/@{$domain}\./", $email)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the priority value for a specific group based on predefined mapping
     *
     * @param string $group The group name to evaluate
     * @return int The priority value (lower is higher priority)
     */
    protected function getGroupPriority(string $group): int
    {
        foreach (self::$groupPriorityMapping as $priority => $pattern) {
            if (strstr($group, $pattern)) {
                return $priority;
            }
        }
        return count(self::$groupPriorityMapping);
    }

    /**
     * Determine the group with the highest priority from a list of groups
     *
     * @param array $groups List of group names
     * @return string The group with the highest priority
     */
    protected function resolveDaikinGroupByPriority(array $groups): string
    {
        usort($groups, function ($a, $b) {
            return $this->getGroupPriority($a) < $this->getGroupPriority($b) ? -1 : 1;
        });

        return array_shift($groups);
    }

    /**
     * Convert a string of group names into an array and determine the group with the highest priority
     *
     * @param array $attributes Associative array containing group information
     * @return string The resolved group name
     */
    public function resolveDaikinGroup($attributes): string
    {
        if (!is_array($attributes['groups'])) {
            $attributes['groups'] = explode(',', $attributes['groups']);
        }

        return $this->resolveDaikinGroupByPriority($attributes['groups']);
    }

    /**
     * Resolve the customer group code based on SAML attributes
     *
     * @param array $attributes Associative array containing SAML attributes
     * @return int The resolved customer group ID
     * @throws LocalizedException If no mapping is found
     */
    protected function resolveCustomerGroupCode($attributes): int
    {
        // Check if the customer's email belongs to one of the predefined domains
        if ($this->isOneOfEmailDomains($attributes['email'][0])) {
            return 10; // affiliates_budget
        }

        $daikinGroup = $this->resolveDaikinGroup($attributes);

        foreach (self::$customerGroupMapping as $code => $daikinGroups) {
            if (!in_array($daikinGroup, $daikinGroups['codes'])) {
                continue;
            }
            if (array_key_exists('countries', $daikinGroups)
                && !in_array($attributes['country'][0], $daikinGroups['countries'])) {
                continue;
            }
            if (array_key_exists('emails', $daikinGroups)
                && !in_array($attributes['email'][0], $daikinGroups['emails'])) {
                continue;
            }
            return $code;
        }

        throw new LocalizedException(
            new Phrase(
                "Can't map values to customer group. Please contact us."
            )
        );
    }

    /**
     * Public wrapper method to resolve the customer group ID based on SAML attributes
     *
     * @param array $attributes Associative array containing SAML attributes
     * @return int The resolved customer group ID
     */
    public function resolveCustomerGroupId($attributes): int
    {
        return $this->resolveCustomerGroupCode($attributes);
    }
}
