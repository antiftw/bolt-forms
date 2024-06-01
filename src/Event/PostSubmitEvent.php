<?php

declare(strict_types=1);

namespace Bolt\BoltForms\Event;

use Bolt\BoltForms\BoltFormsConfig;
use Bolt\Extension\ExtensionInterface;
use Carbon\Carbon;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;
use Tightenco\Collect\Support\Collection;

class PostSubmitEvent extends Event
{
    public const string NAME = 'boltforms.post_submit';
    private bool $spam = false;
    private Collection $attachments;

    public function __construct(
        private readonly Form $form,
        private readonly BoltFormsConfig $config,
        private readonly string $formName,
        private readonly Request $request
    ) {
        $this->attachments = collect([]);
    }

    public function getFormName(): string
    {
        return $this->formName;
    }

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getExtension(): ?ExtensionInterface
    {
        return $this->config->getExtension();
    }

    public function getConfig(): Collection
    {
        return $this->config->getConfig();
    }

    public function getFormConfig(): Collection
    {
        return new Collection($this->getConfig()->get($this->formName));
    }

    public function getMeta(): array
    {
        return [
            'ip' => $this->request->getClientIp(),
            'timestamp' => Carbon::now(),
            'path' => $this->request->getRequestUri(),
            'url' => $this->request->getUri(),
            'attachments' => $this->getAttachments(),
        ];
    }

    public function markAsSpam($spam): void
    {
        $this->spam = $spam;
    }

    public function isSpam(): bool
    {
        return $this->spam;
    }

    public function addAttachments(array $attachments): void
    {
        $this->attachments = $this->attachments->merge($attachments);
    }

    public function getAttachments(): array
    {
        return $this->attachments->toArray();
    }
}
