<?php

declare(strict_types=1);

namespace Bolt\BoltForms\EventSubscriber;

use Bolt\BoltForms\Event\PostSubmitEvent;
use Bolt\BoltForms\Honeypot;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormError;

class HoneypotSubscriber implements EventSubscriberInterface
{
    private PostSubmitEvent $event;

    public function __construct()
    {
    }

    public function handleEvent(PostSubmitEvent $event): PostSubmitEvent
    {
        $this->event = $event;

        $config = $this->event->getConfig();

        if (! $config->get('honeypot', false)) {
            return $event;
        }

        $honeypot = new Honeypot($event->getFormName());
        $fieldName = $honeypot->generateFieldName();

        $data = $event->getForm()->get($fieldName)->getData();

        if (! empty($data)) {
            $action = $config->get('spam-action', 'nothing');
            if ($action === 'mark-as-spam') {
                $event->markAsSpam(true);
            }

            if ($action === 'block') {
                $event->getForm()->addError(new FormError('An extra special error occurred.'));
            }
        }

        return $event;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'boltforms.post_submit' => ['handleEvent', 50],
        ];
    }
}
