<?php
declare(strict_types=1);

namespace Bolius\BoliusFormZendesk\Service;

use Bolius\BoliusZendesk\Service\ZendeskService;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use Zendesk\API\Exceptions\AuthException;
use Zendesk\API\HttpClient;

class ZendeskHandler
{
    protected HttpClient $client;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws AuthException
     */
    public function __construct(ZendeskService $zendeskService)
    {
        $this->client = $zendeskService->getClient();
    }

    /**
     * Get available ticket fields from Zendesk API
     * @return array
     */
    public function getTicketFieldsFromZendesk(): array
    {
        $ticketFields = $this->client->ticketFields()->findAll();

        return array_filter($ticketFields->ticket_fields, [$this, 'filterActiveFields']);
    }

    public function getUserFieldsFromZendesk(): array
    {
        $userFields = $this->client->userFields()->findAll();

        return array_filter($userFields->user_fields, [$this, 'filterActiveFields']);
    }

    public function getOrganizationFieldsFromZendesk(): array
    {
        $organizationFields = $this->client->organizationFields()->findAll();

        return array_filter($organizationFields->organization_fields, [$this, 'filterActiveFields']);
    }

    private function filterActiveFields($field): mixed
    {
        return $field->active;
    }
}