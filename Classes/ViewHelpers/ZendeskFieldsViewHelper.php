<?php
namespace Bolius\BoliusFormZendesk\ViewHelpers;

use Bolius\BoliusFormZendesk\Service\ZendeskHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

class ZendeskFieldsViewHelper extends AbstractViewHelper
{

    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('fieldType', 'string', 'Define the field type [supported: ticket, user, organization]', false, 'ticket');
        $this->registerArgument('optgroupLabel', 'string', 'The optgroup label, if empty options do not get wrapped in optgroup', false, '');
    }

    public function render()
    {
        $fieldsHtml = '';
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        /** @var ZendeskHandler $zendeskHandler */
        $zendeskHandler = $objectManager->get(ZendeskHandler::class);

        switch ($this->arguments['fieldType']){
            case 'user':
                $fields = $zendeskHandler->getUserFieldsFromZendesk();
                break;
            case 'organization':
                $fields = $zendeskHandler->getOrganizationFieldsFromZendesk();
                break;
            default:
                $fields = $zendeskHandler->getTicketFieldsFromZendesk();
        }

        foreach ($fields as $field){

            switch ($field->type){
                case 'subject':
                    $fieldId = 'subject';
                    break;
                case 'description':
                    $fieldId = 'comment|body';
                    break;
                case 'group':
                    $fieldId = 'group_id';
                    break;
                case 'tickettype':
                    $fieldId = 'type';
                    break;
                case 'priority':
                    $fieldId = 'priority';
                    break;
                default:
                    $fieldId = 'custom_field|' . $field->id;
            }

            $fieldOptions = '';
            if($field->custom_field_options && is_array($field->custom_field_options)){
                foreach ($field->custom_field_options as $option){
                    $fieldOptions .= ' ' . $option->value . ',';
                }
            }
            if($field->system_field_options && is_array($field->system_field_options)){
                foreach ($field->system_field_options as $option){
                    $fieldOptions .= ' ' . $option->value . ',';
                }
            }

            $fieldsHtml .= '<option
                value="' . $fieldId . '"
                data-title="' . $field->title . '"
                data-description="' . $field->description . '"
                data-field-options="' . trim($fieldOptions, ',') . '"
                >' . $field->title .
                ' [' . $field->type . ']</option>' . PHP_EOL;
        }

        if(strlen($this->arguments['optgroupLabel']) > 0){
            $fieldsHtml = '<optgroup label="' . $this->arguments['optgroupLabel'] . '">' . $fieldsHtml . '</optgroup>';
        }

        return $fieldsHtml;
    }
}
