<?php
declare(strict_types=1);

namespace Bolius\BoliusFormZendesk\Domain\Finishers;

use Bolius\BoliusFormZendesk\Event\AfterQuestionSubmittedEvent;
use Bolius\BoliusZendesk\Service\ZendeskService;
use Html2Text\Html2Text;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use Zendesk\API\Exceptions\AuthException;
use Zendesk\API\Exceptions\MissingParametersException;
use Zendesk\API\Exceptions\ResponseException;
use Zendesk\API\HttpClient;

class CustomFinisher extends AbstractFinisher
{
    protected LoggerInterface|Logger   $logger;
    protected HttpClient               $client;
    protected EventDispatcherInterface $eventDispatcher;

    /**
     * @throws ExtensionConfigurationPathDoesNotExistException
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws AuthException
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        LogManager               $logManager,
        ZendeskService           $zendeskService,
    ) {
        $this->logger = $logManager->getLogger('Bolius.BoliusFormZendesk');
        $this->client = $zendeskService->getClient();
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Executes this finisher
     * @throws FileDoesNotExistException
     * @throws MissingParametersException
     * @throws ResponseException
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal(): void
    {
        $formRuntime = $this->finisherContext->getFormRuntime();
        $formValues = $this->finisherContext->getFormValues();

        $ticketType = $this->parseOption('zendeskType');
        $ticketPriority = $this->parseOption('zendeskPriority');
        $ticketGroupId = $this->parseOption('zendeskGroupId');

        // Create a new ticket in Zendesk
        // IDEA: Support patching?
        $newTicketArray = [];

        $newTicketArray['type'] = $ticketType ?? 'question';
        $newTicketArray['priority'] = $ticketPriority ?? 'normal';
        $newTicketArray['group_id'] = $ticketGroupId ?? '';

        foreach ($formValues as $fieldName => $fieldValue) {
            $field = $formRuntime->getFormDefinition()->getElementByIdentifier($fieldName);

            if (is_string($fieldValue)) {
                /** @var Html2Text $html2Text */
                $html2Text = GeneralUtility::makeInstance(Html2Text::class, $fieldValue);
                $fieldValue = $html2Text->getText();
            }

            if (!$field) {
                continue;
            }

            $fieldProperties = $field->getProperties();

            if ($fieldProperties['zendeskField'] && strlen($fieldProperties['zendeskField']) > 0) {
                $fieldPropArray = explode('|', $fieldProperties['zendeskField']);

                if (count($fieldPropArray) > 1) {
                    if ($fieldPropArray[0] == 'custom_field') {
                        $newTicketArray['custom_fields'][] = [
                            'id'    => $fieldPropArray[1],
                            'value' => $fieldValue

                        ];
                    } else {
                        $newTicketArray[$fieldPropArray[0]][$fieldPropArray[1]] = $fieldValue;
                    }
                } else {
                    // Tags get sent as an array
                    if ($fieldPropArray[0] === 'tags') {
                        $fieldValue = explode('|', $fieldValue);
                    }

                    $newTicketArray[$fieldPropArray[0]] = $fieldValue;
                }
            }
        }

        // Create a new ticket in Zendesk
        try {
            $newTicket = $this->client->tickets()->create($newTicketArray);
            $this->logger->info('New ticket created in Zendesk', $newTicketArray);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage(), $newTicketArray);
        }

        if (!isset($newTicket, $newTicket->ticket->id) || !$newTicket || !$newTicket->ticket->id) {
            return;
        }

        $newTicketArray = $this->attachImage($newTicketArray, $newTicket);

        // Used to be a signal slot for: 'BoliusFormZendesk', 'afterQuestionSubmitted'
        $this->eventDispatcher->dispatch(
            new AfterQuestionSubmittedEvent([
                'data' => array_merge(
                    $newTicketArray,
                    ['id' => $newTicket->ticket->id]
                )
            ])
        );
    }

    /**
     * Attach image. Only supports one attachment at the moment.
     * @param array $newTicketArray
     * @param \stdClass $newTicket
     * @return array
     * @throws FileDoesNotExistException
     * @throws MissingParametersException
     * @throws ResponseException
     */
    private function attachImage(array $newTicketArray, \stdClass $newTicket): array
    {
        if (!isset($newTicketArray['attachment']) || !$newTicketArray['attachment'] instanceof FileReference) {
            return $newTicketArray;
        }

        $fileReference = $newTicketArray['attachment'];

        /** @var \TYPO3\CMS\Extbase\Domain\Model\File $file */
        $file = $fileReference->getOriginalResource()->getOriginalFile();
        $id = $file->getUid();

        /** @var File $attachment */
        $attachment = GeneralUtility::makeInstance(ResourceFactory::class)
            ->getFileObject($id);

        $this->client->tickets()
            ->attach([
                'file' => Environment::getPublicPath() . '/' . $attachment->getPublicUrl(),
                'type' => $attachment->getMimeType(),
                'name' => md5(time() . rand())
            ])->update($newTicket->ticket->id, [
                'comment' => [
                    'body' => 'File attachment',
                ],
            ]);

        return $newTicketArray;
    }
}