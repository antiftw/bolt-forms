<?php

declare(strict_types=1);

namespace Bolt\BoltForms;

use Bolt\BoltForms\Event\PostSubmitEventDispatcher;
use Bolt\Twig\Notifications;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Extension\RuntimeExtensionInterface;

class FormRuntime implements RuntimeExtensionInterface
{
    private Request$request;

    public function __construct(
        private readonly Notifications $notifications,
        private readonly Environment $twig,
        private readonly FormBuilder $builder,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly BoltFormsConfig $config,
        private readonly PostSubmitEventDispatcher $postSubmitEventDispatcher,
        RequestStack $requestStack,
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function run(string $formName = '', array $data = [], bool $warn = true): ?string
    {
        $config = $this->config->getConfig();
        $extension = $this->config->getExtension();

        if (! $config->has($formName)) {
            return $warn ? $this->notifications->warning(
                '[Boltforms] Incorrect usage of form',
                'The form "' . $formName . '" is not defined. '
            ) : '';
        }

        $formConfig = collect($config->get($formName));
        $form = $this->builder->build($formName, $data, $config, $this->dispatcher);

        $form->handleRequest($this->request);

        if ($form->isSubmitted()) {
            $this->postSubmitEventDispatcher->handle($formName, $form, $this->request);
        }

        $extension->dump($formConfig);
        $extension->dump($form);

        if ($config->get('honeypot')) {
            $honeypot = new Honeypot($formName);
            $honeypotName = $honeypot->generateFieldName(true);
        } else {
            $honeypotName = false;
        }

        if ($formConfig->has('templates') && isset($formConfig->get('templates')['form'])) {
            $template = $formConfig->get('templates')['form'];
        } else {
            $template = $config->get('templates')['form'];
        }

        return $this->twig->render($template, [
            'boltforms_config' => $config,
            'form_config' => $formConfig,
            'debug' => $config->get('debug'),
            'honeypot_name' => $honeypotName,
            'form' => $form->createView(),
            'submitted' => $form->isSubmitted(),
            'valid' => $form->isSubmitted() && $form->isValid(),
            'data' => $form->getData(),
            // Deprecated
            'formconfig' => $formConfig,
            // Deprecated
            'honeypotname' => $honeypotName,
        ]);
    }
}
