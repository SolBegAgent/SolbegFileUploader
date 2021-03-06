<?php

namespace Solbeg\FilesManager\Validation;

use Intervention\Image\ImageManager;
use Solbeg\FilesManager\Contracts;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * ImageSizeValidator
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class ImageSizeValidator extends AbstractValidator
{
    use DefaultMessageTrait;

    /**
     * @var integer|null (in pixels)
     */
    protected $minWidth;

    /**
     * @var integer|null (in pixels)
     */
    protected $maxWidth;

    /**
     * @var integer|null (in pixels)
     */
    protected $minHeight;

    /**
     * @var integer|null (in pixels)
     */
    protected $maxHeight;

    /**
     * A ratio constraint should be represented as width divided by height.
     * This can be specified either by a statement like 3/2 or a float like 1.5
     * 
     * @var string
     */
    protected $ratio;

    /**
     * @var float
     */
    protected $ratioPrecision = 1e-5;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @var boolean
     */
    protected $skipOnError = true;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @param ImageManager $image
     * @inheritdoc
     */
    public function __construct(ImageManager $image, Contracts\Context $context, TranslatorInterface $translator, $config = null)
    {
        foreach (['width', 'height'] as $attribute) {
            if (isset($config[$attribute])) {
                $config["min_$attribute"] = $config["max_$attribute"] = $config[$attribute];
                unset($config[$attribute]);
            }
        }

        $this->imageManager = $image;
        parent::__construct($context, $translator, $config);
    }

    /**
     * @inheritdoc
     */
    protected function defaultConfigProperty()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    protected function defaultMessage()
    {
        return $this->trans()->trans('filesmanager::validation.not-image');
    }

    /**
     * @param string $attribute
     * @param string $fileName
     * @param integer $fileSize
     * @param integer $fileWidth
     * @param integer $fileHeight
     * @return string
     */
    protected function sizeMessage($attribute, $fileName, $fileSize, $fileWidth, $fileHeight)
    {
        $underscored = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $attribute));
        $hyphened = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1-', $attribute));

        foreach ([$attribute, $underscored, $hyphened] as $transformed) {
            if (isset($this->messages[$transformed])) {
                return $this->messages[$transformed];
            }
        }

        return $this->trans()->trans("filesmanager::validation.$hyphened", [
            'file' => $fileName,
            'fileSize' => $fileSize,
            'fileWidth' => $fileWidth,
            'fileHeight' => $fileHeight,
            'requiredSize' => $this->{$attribute},
        ]);
    }

    /**
     * @inheritdoc
     */
    public function validate(Contracts\FileSource $source)
    {
        try {
            list($width, $height) = $this->fetchFileSizes($source);
        } catch (\Exception $ex) {
            return $this->errorMessage();
        }

        $fileName = $source->basename();
        $messages = [];

        foreach (['minWidth', 'maxWidth'] as $attribute) {
            $messages[] = $this->validateSize($attribute, $fileName, $width, $width, $height);
        }
        foreach (['minHeight', 'maxHeight'] as $attribute) {
            $messages[] = $this->validateSize($attribute, $fileName, $height, $width, $height);
        }

        $messages[] = $this->validateRatio($fileName, $width, $height);

        return implode(' ', array_filter($messages)) ?: null;
    }

    /**
     * @param string $attribute 'minWidth'|'maxWidth'|'minHeight'|'maxHeight'
     * @param string $fileName
     * @param integer $fileSize
     * @param integer $fileWidth
     * @param integer $fileHeight
     * @return string|null
     */
    protected function validateSize($attribute, $fileName, $fileSize, $fileWidth, $fileHeight)
    {
        $requiredSize = $this->{$attribute};
        if ($requiredSize === null) {
            return null;
        } elseif (strncmp($attribute, 'min', 3) === 0) {
            return $fileSize < $requiredSize ? $this->sizeMessage($attribute, $fileName, $fileSize, $fileWidth, $fileHeight) : null;
        } elseif (strncmp($attribute, 'max', 3) === 0) {
            return $fileSize > $requiredSize ? $this->sizeMessage($attribute, $fileName, $fileSize, $fileWidth, $fileHeight) : null;
        }
    }

    /**
     * @param string $fileName
     * @param integer $fileWidth
     * @param integer $fileHeight
     * @return string|null
     */
    protected function validateRatio($fileName, $fileWidth, $fileHeight)
    {
        $ratio = trim($this->ratio);
        if ($ratio === '') {
            return null;
        }

        list($numerator, $denominator) = array_replace(
            [1, 1], array_filter(sscanf($ratio, '%f/%d'))
        );
        if (!$numerator || !$denominator || !$fileWidth || !$fileHeight) {
            return null;
        }

        if (abs($fileWidth / $fileHeight - $numerator / $denominator) < $this->ratioPrecision) {
            return null;
        }
        return $this->sizeMessage('ratio', $fileName, "$fileWidth/$fileHeight", $fileWidth, $fileHeight);
    }

    /**
     * @param \Solbeg\FilesManager\Contracts\FileSource $source
     * @return array [width, height]
     */
    protected function fetchFileSizes(Contracts\FileSource $source)
    {
        if (method_exists($source, 'image')) {
            $image = $source->image();
            /* @var $image \Intervention\Image\Image */
            return [(int) $image->width(), (int) $image->height()];
        }

        $contents = $source->contents();
        try {
            $image = $this->imageManager()->make($contents->stream());
            return [(int) $image->width(), (int) $image->height()];
        } finally {
            $contents->close();
        }
    }

    /**
     * @return ImageManager
     */
    public function imageManager()
    {
        return $this->imageManager;
    }
}
