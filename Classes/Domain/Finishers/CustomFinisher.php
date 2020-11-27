<?php
namespace Bolius\BoliusFormZendesk\Domain\Finishers;

use Bolius\BoliusZendesk\Service\ZendeskService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Error\Error;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;
use Zendesk\API\HttpClient;

class CustomFinisher extends AbstractFinisher
{

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
                    $newTicketArray[$fieldPropArray[0]] = $fieldValue;
                }
            }
        }

//        DebuggerUtility::var_dump($newTicketArray);
//        die;
        // Create a new ticket in Zendesk
        try {
//            $newTicket = $this->client->tickets()->create($newTicketArray);
            throw new FinisherException(sprintf('The message body must be of type string, "%s" given.', 'array'), 1235980069);
        } catch (\Exception $e){
            $message = $this->objectManager->get(Error::class, $e->getMessage(), 1606471141, [], 'Ticket could not be created in Zendesk!');

            /** @var FlashMessage $flashMessage */
            $flashMessage = $this->objectManager->get(
                FlashMessage::class,
                $message->render(),
                $message->getTitle(),
                2,
                true
            );

            $this->finisherContext->getControllerContext()->getFlashMessageQueue()->addMessage($flashMessage);

            // TODO: Handle this better! What can the user do now?
            // Log this and give the user a possibility to try again?
        }

//        DebuggerUtility::var_dump($newTicket);die;

        // TODO: Make a second input field below field selector where the user can
        // input the field him/her self. Values would be nested using '|'

        // EXTconf: Predefine group_id and more in extconf?
        // See all fields here: https://developer.zendesk.com/rest_api/docs/support/tickets#request-body

    }
}
