<?php

namespace Solbeg\FilesManager\Validation;

use Solbeg\FilesManager\Contracts;
use Solbeg\FilesManager\Helpers;

/**
 * MimeTypeValidator validates file MIME types.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class MimeTypeValidator extends AbstractValidator implements Contracts\Validators\MimeTypeValidator
{
    use DefaultMessageTrait;

    /**
     * @var array|null
     */
    private $types = null;

    /**
     * @inheritdoc
     */
    protected function defaultConfigProperty()
    {
        return 'types';
    }

    /**
     * @inheritdoc
     */
    protected function defaultMessage()
    {
        $types = $this->getTypes();
        return $this->trans()->trans('filesmanager::validation.mime-types', [
            'types' => is_array($types) ? implode(', ', $types) : '',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validate(Contracts\FileSource $source)
    {
        $types = $this->getTypes();
        if (!is_array($types)) {
            return null;
        }

        $sourceType = $source->mimeType();
        foreach ($types as $type) {
            if (Helpers\Config::matchWildcards($sourceType, $type, false)) {
                return null;
            }
        }
        return $this->errorMessage();
    }

    /**
     * @return array|null
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param mxied $types
     */
    public function setTypes($types)
    {
        if (is_scalar($types)) {
            $types = Helpers\Config::explode(',', $types, true);
        }
        $this->types = $types ?: null;
    }
}
