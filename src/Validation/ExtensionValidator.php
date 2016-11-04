<?php

namespace Solbeg\FilesManager\Validation;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Helpers;

/**
 * ExtensionValidator validates file extensions.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ExtensionValidator extends AbstractValidator implements Contracts\Validators\ExtensionValidator
{
    use DefaultMessageTrait;

    /**
     * @var boolean
     */
    protected $caseSensitive = false;

    /**
     * @var array|null
     */
    private $extensions = null;

    /**
     * @inheritdoc
     */
    protected function defaultConfigProperty()
    {
        return 'extensions';
    }

    /**
     * @inheritdoc
     */
    protected function defaultMessage()
    {
        $extensions = $this->getExtensions();
        return $this->trans()->trans('filesmanager::validation.extensions', [
            'extensions' => is_array($extensions) ? implode(', ', $extensions) : '',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validate(Contracts\FileSource $source)
    {
        $extensions = $this->getExtensions();
        if (!is_array($extensions)) {
            return null;
        } elseif ($this->caseSensitive) {
            return in_array($source->extension(), $extensions, false) ? null : $this->errorMessage();
        }

        $sourceExtenstion = mb_strtolower($source->extension(), 'UTF-8');
        foreach ($extensions as $extension) {
            if ($sourceExtenstion == mb_strtolower($extension, 'UTF-8')) {
                return null;
            }
        }
        return $this->errorMessage();
    }

    /**
     * @return array|null
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * @param mxied $extensions
     */
    public function setExtensions($extensions)
    {
        if (is_scalar($extensions)) {
            $extensions = Helpers\Config::explode(',', $extensions);
        }
        $this->extensions = $extensions ?: null;
    }
}
