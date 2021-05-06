<?php
namespace Bolius\BoliusFormZendesk\Domain\Finishers;

use Bolius\BoliusZendesk\Service\ZendeskService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use Zendesk\API\HttpClient;

class CustomFinisher extends AbstractFinisher
{
    /** @var $logger \TYPO3\CMS\Core\Log\Logger */
    protected $logger;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var HttpClient
     */
    protected $client;

    public function __construct(string $finisherIdentifier = '')
    {
        parent::__construct($finisherIdentifier);

        /** @var LogManager $logManager */
        $logManager = GeneralUtility::makeInstance(LogManager::class);

        /** @var Logger logger */
        $this->logger = $logManager->getLogger('Bolius.BoliusFormZendesk');

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->client = $this->objectManager->get(ZendeskService::class)->getClient();
    }

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     *
     * @throws FinisherException
     */
    protected function executeInternal()
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

        foreach ($formValues as $fieldName => $fieldValue){
            $field = $formRuntime->getFormDefinition()->getElementByIdentifier($fieldName);

            if(!$field) continue;

            $fieldProperties = $field->getProperties();

            if($fieldProperties['zendeskField']
                && strlen($fieldProperties['zendeskField']) > 0){

                $fieldPropArray = explode('|', $fieldProperties['zendeskField']);
                if(count($fieldPropArray) > 1){

                    if($fieldPropArray[0] == 'custom_field'){
                        $newTicketArray['custom_fields'][] = [
                            'id' => $fieldPropArray[1],
                            'value' => $fieldValue
                        ];
                    } else {
                        $newTicketArray[$fieldPropArray[0]][$fieldPropArray[1]] = $fieldValue;
                    }

                } else {

                    // Tags get sent as an array
                    if($fieldPropArray[0] === 'tags'){
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
        } catch (\Exception $e){
            $this->logger->error($e->getMessage(), $newTicketArray);
        }

        if($newTicket && $newTicket->ticket->id){

            // Attach image. Only supports one attachment atm
            if ($newTicketArray['attachment'] && $newTicketArray['attachment'] instanceof FileReference) {

                $fileReference = $newTicketArray['attachment'];

                /** @var \TYPO3\CMS\Extbase\Domain\Model\File $file */
                $file = $fileReference->getOriginalResource()->getOriginalFile();
                $id = $file->getUid();

                /** @var File $attachment */
                $attachment = ResourceFactory::getInstance()->getFileObject($id);

                $this->client->tickets()->attach([
                    'file' => PATH_site . $attachment->getPublicUrl(false),
                    'type' => $attachment->getMimeType(),
                    'name' => md5(time() . rand())
                ])->update($newTicket->ticket->id, [
                    'comment' => [
                        'body' => 'File attachment',
                    ],
                ]);
            }

            /** @var Dispatcher $dispatcher */
            $dispatcher = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
            $dispatcher->dispatch('BoliusFormZendesk', 'afterQuestionSubmitted', [
                'data' => array_merge(
                    $newTicketArray,
                    ['id' => $newTicket->ticket->id]
                )
            ]);
        }
    }
}
