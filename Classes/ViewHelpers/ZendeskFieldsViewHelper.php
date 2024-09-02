<?php
declare(strict_types=1);

namespace Bolius\BoliusFormZendesk\ViewHelpers;

use Bolius\BoliusFormZendesk\Service\ZendeskHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class ZendeskFieldsViewHelper extends AbstractViewHelper
{
    public function initializeArguments(): void
    {
        parent::initializeArguments();

        $this->registerArgument(
            'fieldType',
            'string',
            'Define the field type [supported: ticket, user, organization]',
            false,
            'ticket'
        );
        $this->registerArgument(
            'optgroupLabel',
            'string',
            'The optgroup label, if empty options do not get wrapped in optgroup',
            false,
            ''
        );
    }

    public function render(): string
    {
        $fieldsHtml = '';
        /** @var ZendeskHandler $zendeskHandler */
        $zendeskHandler = GeneralUtility::makeInstance(ZendeskHandler::class);

        $fields = match ($this->arguments['fieldType']) {
            'user' => $zendeskHandler->getUserFieldsFromZendesk(),
            'organization' => $zendeskHandler->getOrganizationFieldsFromZendesk(),
            default => $zendeskHandler->getTicketFieldsFromZendesk(),
        };

        foreach ($fields as $field) {
            $fieldId = match ($field->type) {
                'subject' => 'subject',
                'description' => 'comment|body',
                'group' => 'group_id',
                'tickettype' => 'type',
                'priority' => 'priority',
                default => 'custom_field|' . $field->id,
            };

            $fieldOptions = '';
            if ($field->custom_field_options && is_array($field->custom_field_options)) {
                foreach ($field->custom_field_options as $option) {
                    $fieldOptions .= ' ' . $option->value . ',';
                }
            }
            if ($field->system_field_options && is_array($field->system_field_options)) {
                foreach ($field->system_field_options as $option) {
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

        if (strlen($this->arguments['optgroupLabel']) > 0) {
            $fieldsHtml = '<optgroup label="' . $this->arguments['optgroupLabel'] . '">' . $fieldsHtml . '</optgroup>';
        }

        return $fieldsHtml;
    }
}