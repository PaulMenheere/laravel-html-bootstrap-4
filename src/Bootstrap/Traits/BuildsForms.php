<?php

namespace MarvinLabs\Html\Bootstrap\Traits;

use Illuminate\Contracts\Support\Htmlable;
use MarvinLabs\Html\Bootstrap\Contracts\FormState;
use MarvinLabs\Html\Bootstrap\Elements\File;
use MarvinLabs\Html\Bootstrap\Elements\FormGroup;
use MarvinLabs\Html\Bootstrap\Elements\Input;
use MarvinLabs\Html\Bootstrap\Elements\Select;
use MarvinLabs\Html\Bootstrap\Elements\TextArea;
use RuntimeException;
use Spatie\Html\Elements\Form;

/**
 * @target \MarvinLabs\Html\Bootstrap\Bootstrap
 */
trait BuildsForms
{
    /** @var Form */
    private $currentForm;

    /** @var \MarvinLabs\Html\Bootstrap\Contracts\FormState */
    private $formState;

    /**
     * Open a form
     *
     * @param string $method  The method to target for form action
     * @param string $action  The form action
     * @param array  $options A set of options for the form
     *
     * Valid options are:
     *
     *   - files  => boolean    Does the form accept files
     *   - inline => boolean    Shall we render an inline form (Bootstrap specific)
     *   - model  => mixed      The model to bind to the form
     *
     * @return \Illuminate\Contracts\Support\Htmlable
     * @throws \Exception When trying to open a form before closing the previous one
     */
    public function openForm($method, $action, array $options = []): Htmlable
    {
        // Initialize the form state
        if ($this->formState !== null || $this->currentForm !== null)
        {
            throw new RuntimeException('You cannot open another form before closing the previous one');
        }
        $this->formState = app()->make(FormState::class);
        $this->formState->setModel($options['model'] ?? null);

        // Create a form element with sane defaults
        $this->currentForm = Form::create();

        // Handle form method consequences (token / hidden method field)
        //
        // If Laravel needs to spoof the form's method, we'll append a hidden
        // field containing the actual method
        //
        // On any other method that get, the form needs a CSRF token
        $method = strtoupper($method);

        if (in_array($method, ['DELETE', 'PATCH', 'PUT'], true))
        {
            $this->currentForm = $this->currentForm->addChild($this->hidden('_method', $method));
            $method = 'POST';
        }

        if ($method !== 'GET')
        {
            $this->currentForm = $this->currentForm->addChild($this->token());
        }

        return $this->currentForm
            ->method($method)->action($action)
            ->addClassIf($options['inline'] ?? false, 'form-inline')
            ->open();
    }

    /**
     * Close a form previously open with openForm()
     *
     * @return \Illuminate\Contracts\Support\Htmlable
     */
    public function closeForm(): Htmlable
    {
        $out = $this->currentForm->close();

        $this->currentForm = null;
        $this->formState = null;

        return $out;
    }

    /**
     * @param \Spatie\Html\BaseElement $control
     * @param string|null              $label
     * @param string|null              $helpText
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\FormGroup
     */
    public function formGroup($control, $label = null, $helpText = null): FormGroup
    {
        $element = new FormGroup($this->formState, $control, $label);

        return $element->helpText($helpText);
    }

    /**
     * @param string|null $type
     * @param string|null $name
     * @param string|null $value
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Input
     */
    public function input($type = null, $name = null, $value = null): Input
    {
        $value = $this->getFieldValue($name, $value);
        $element = new Input($this->formState);

        return $element
            ->typeIf($type, $type)
            ->nameIf($name, $name)
            ->idIf($name, field_name_to_id($name))
            ->valueIf($value !== null, $value);
    }

    /**
     * @param string|null $name
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\File
     */
    public function file($name = null): File
    {
        $element = new File($this->formState);

        return $element
            ->nameIf($name, $name)
            ->idIf($name, field_name_to_id($name));
    }

    /**
     * @param string|null $name
     * @param string|null $value
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Textarea
     */
    public function textarea($name = null, $value = null): Textarea
    {
        $value = $this->getFieldValue($name, $value);
        $element = new TextArea($this->formState);

        return $element
            ->nameIf($name, $name)
            ->idIf($name, field_name_to_id($name))
            ->valueIf($value !== null, $value);
    }

    /**
     * @param string|null          $name
     * @param array|iterable       $options
     * @param string|iterable|null $value
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Select
     */
    public function select($name = null, $options = [], $value = null): Select
    {
        $value = $this->getFieldValue($name, $value);
        $element = new Select($this->formState);

        return $element
            ->nameIf($name, $name)
            ->idIf($name, field_name_to_id($name))
            ->options($options)
            ->valueIf($value !== null, $value);
    }

    /**
     * @param string|null $name
     * @param string|null $value
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Input
     */
    public function text($name = null, $value = null): Input
    {
        return $this->input('text', $name, $value);
    }

    /**
     * @param string|null $name
     * @param string|null $value
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Input
     */
    public function email($name = null, $value = null): Input
    {
        return $this->input('email', $name, $value);
    }

    /**
     * @param string|null $name
     * @param string|null $value
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Input
     */
    public function hidden($name = null, $value = null): Input
    {
        $value = $this->getFieldValue($name, $value);
        $element = new Input($this->formState);

        return $element
            ->type('hidden')
            ->nameIf($name, $name)
            ->valueIf($value !== null, $value);
    }

    /**
     * CSRF token hidden field
     *
     * @return \MarvinLabs\Html\Bootstrap\Elements\Input
     */
    public function token(): Input
    {
        return $this->hidden('_token', $this->request->session()->token());
    }

    public function submit($text)
    {
        return $this->html->button($text);
    }

    private function getFieldValue($name, $default)
    {
        return $this->formState !== null
            ? $this->formState->getFieldValue($name, $default)
            : $default;
    }
}