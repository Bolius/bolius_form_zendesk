<?php
declare(strict_types=1);

namespace Bolius\BoliusFormZendesk\Event;

class AfterQuestionSubmittedEvent
{
    public function __construct(
        public array $args
    ) {}
}