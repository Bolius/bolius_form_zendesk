<?php
namespace Bolius\BoliusFormZendesk\Service;

use Bolius\BoliusZendesk\Service\ZendeskService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Zendesk\API\HttpClient;

class ZendeskHandler
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var HttpClient
     */
    protected $client;

    public function __construct()
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->client = $this->objectManager->get(ZendeskService::class)->getClient();
    }

    /**
     * Get available ticket fields from Zendesk API
     *
     * @return array
     */
    public function getTicketFieldsFromZendesk()
    {
        $ticketFields = $this->client->ticketFields()->findAll();

        return array_filter($ticketFields->ticket_fields, array($this, 'filterActiveFields'));
    }

    public function getUserFieldsFromZendesk()
    {
        $userFields = $this->client->userFields()->findAll();

        return array_filter($userFields->user_fields, array($this, 'filterActiveFields'));
    }

    public function getOrganizationFieldsFromZendesk()
    {
        $organizationFields = $this->client->organizationFields()->findAll();

        return array_filter($organizationFields->organization_fields, array($this, 'filterActiveFields'));
    }

    private function filterActiveFields($field)
    {
        return $field->active;
    }
}
