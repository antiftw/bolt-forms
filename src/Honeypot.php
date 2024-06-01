<?php

declare(strict_types=1);

namespace Bolt\BoltForms;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilder as SymfonyFormBuilder;

readonly class Honeypot
{

    public function __construct(private string $formName, private ?SymfonyFormBuilder $formBuilder = null) {}

    public function addField(): void
    {
        $fieldName = $this->generateFieldName();

        $options = [
            'required' => false,
            'attr' => [
                'tabindex' => '-1',
                'autocomplete' => 'off',
            ],
        ];

        $this->formBuilder->add($fieldName, TextType::class, $options);
    }

    public function generateFieldName($withFormName = false): string
    {
        $seed = preg_replace('/[^0-9]/', '', md5($_SERVER['APP_SECRET'] . $_SERVER['REMOTE_ADDR']));
        mt_srand($seed % PHP_INT_MAX);

        $values = ['field', 'name', 'object', 'string', 'value', 'input', 'required', 'optional', 'first', 'last', 'phone', 'telephone', 'fax', 'twitter', 'contact', 'approve', 'city', 'state', 'province', 'company', 'card', 'number', 'recipient', 'processor', 'transaction', 'domain', 'date', 'type'];

        if ($withFormName) {
            $parts = [$this->formName];
        } else {
            $parts = [];
        }

        // Note: we're using mt_rand here, because we explicitly want
        // pseudo-random results, to make sure it's reproducible.
        for ($i = 0; $i <= mt_rand(2, 3); $i++) {
            $parts[] = $values[mt_rand(0, \count($values) - 1)];
        }

        return implode('_', $parts);
    }

    public function isEnabled(): bool
    {
        return false;
        // Not sure what this was doing here, does not seem to be used
        // also $this->config does not exist ? So IDE is complaining
        // return $this->config->get('honeypot', false);
    }
}
