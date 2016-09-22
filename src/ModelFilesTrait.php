<?php

namespace Bicycle\FilesManager;

use Illuminate\Container\Container;

/**
 * ModelFilesTrait should be used in your Eloquent model.
 *
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
trait ModelFilesTrait
{
    /**
     * @var array[]
     */
    private $filesAttributesConfig = null;

    /**
     * @var File\File[]
     */
    private $filesInstances = [];

    /**
     * This method must be overrided for defining files attributes of this model.
     * 
     * @return array Each key of this method is the name of attribute.
     * Each value must be either a context name or context config or context object.
     * 
     * Examples:
     * ```php
     *  return [
     *      'logo' => 'product-logo', // means using 'product-logo' context for 'logo' attribute
     *      'photo' => [ // current config will be used for 'photo' attribute
     *          'formats' => [
     *              'thumbnail' => 'image/thumb: width = 200, height = 300',
     *          ],
     * 
     *          'validate' => [
     *              'types' => 'image/jpeg, image/png',
     *              'extensions' => ['jpg', 'jpeg', 'png'],
     *              // ...
     *          ],
     *      ],
     *  ];
     * ```
     */
    abstract public function filesAttributes();

    /**
     * Boots this trait. Adds event listeners.
     */
    public static function bootModelFilesTrait()
    {
        static::saving(function ($model) {
            $model->saveFileAttributes();
        });
        static::deleted(function ($model) {
            $model->deleteFileAttributes();
        });
    }

    /**
     * Initializes files attributes config.
     */
    private function initFilesAttributesConfig()
    {
        if ($this->filesAttributesConfig !== null) {
            return;
        }
        $this->filesAttributesConfig = $this->filesAttributes();
    }

    /**
     * @param string $attribute
     * @return File\File
     */
    public function getFileAttributeValue($attribute)
    {
        /* @var $this ModelFilesTrait|\Illuminate\Database\Eloquent\Model */
        if (!isset($this->filesInstances[$attribute])) {
            $context = $this->getFileAttributeContext($attribute);
            $data = $this->getAttributeValue($attribute);
            $storage = $context->storage(false);
            $source = $data
                ? $context->getSourceFactory()->storedFile($data, $storage)
                : $context->getSourceFactory()->emptyFile($storage);
            $this->filesInstances[$attribute] = $this->createFileAttributeInstance($context, $source);
        }
        return $this->filesInstances[$attribute];
    }

    /**
     * @param string $attribute
     * @param mixed $value
     */
    public function setFileAttributeValue($attribute, $value)
    {
        $this->getFileAttributeValue($attribute)->setData($value);
    }

    /**
     * @param string $attribute
     * @return boolean
     */
    public function hasFileAttribute($attribute)
    {
        if (isset($this->filesInstances[$attribute])) {
            return true;
        }
        $this->initFilesAttributesConfig();
        return isset($this->filesAttributesConfig[$attribute]);
    }

    /**
     * @param string $attribute
     * @return Contracts\Context
     * @throws Exceptions\FileAttributeNotDefinedException
     */
    public function getFileAttributeContext($attribute)
    {
        $this->initFilesAttributesConfig();
        if (!isset($this->filesAttributesConfig[$attribute])) {
            throw new Exceptions\FileAttributeNotDefinedException(get_class($this), $attribute);
        }
        $config = $this->filesAttributesConfig[$attribute];

        $manager = $this->getFilesContextManager();
        if (is_string($config)) {
            return $manager->context($config);
        } else {
            return $manager->createContext($this->compileFileContextName($attribute), $config);
        }
    }

    /**
     * @param string $attribute
     * @return string
     */
    protected function compileFileContextName($attribute)
    {
        $reflection = new \ReflectionClass($this);
        $class = $reflection->getMethod('filesAttributes')->getDeclaringClass()->getName();
        return "$class@$attribute";
    }

    /**
     * @param Contracts\Context $context
     * @param Contracts\FileSource $source
     * @return \Bicycle\FilesManager\FilesManager\File\File
     */
    public function createFileAttributeInstance($context, $source)
    {
        return new File\File($context, $source);
    }

    /**
     * @return Manager
     */
    public function getFilesContextManager()
    {
        $container = Container::getInstance();
        $manager = $container['filesmanager'];
        if (!$manager instanceof Manager) {
            throw new Exceptions\InvalidConfigException('Files manager was not found. May be you should add "' . FilesManagerServiceProvider::class . '" in your app providers.');
        }
        return $manager;
    }

    /**
     * @inheritdoc
     */
    public function getAttribute($key)
    {
        if ($this->hasFileAttribute($key)) {
            return $this->getFileAttributeValue($key);
        }
        return parent::getAttribute($key);
    }

    /**
     * @inheritdoc
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasFileAttribute($key)) {
            $this->setFileAttributeValue($key, $value);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * @inheritdoc
     */
    public function attributesToArray()
    {
        $result = parent::attributesToArray();
        $this->initFilesAttributesConfig();
        foreach (array_keys($this->filesAttributesConfig) as $attribute) {
            if (isset($result[$attribute]) || array_key_exists($attribute, $result)) {
                $result[$attribute] = $this->getFileAttributeValue($attribute)->jsonSerialize();
            }
        }
        return $result;
    }

    /**
     * @return boolean
     */
    protected function isDeleteOldFiles()
    {
        return !property_exists($this, 'deleteOldFiles') || $this->deleteOldFiles;
    }

    /**
     * Saves all initialized file attributes.
     */
    public function saveFileAttributes()
    {
        foreach ($this->filesInstances as $attribute => $file) {
            $file->save([
                'deleteOld' => (bool) $this->isDeleteOldFiles(),
            ]);
            $this->attributes[$attribute] = $file->relativePath();
        }
    }

    /**
     * Deletes all files for all defined file attributes.
     * @param array $options
     */
    public function deleteFileAttributes(array $options = [])
    {
        if (!$this->isDeleteOldFiles()) {
            return;
        }

        $this->initFilesAttributesConfig();
        foreach (array_keys($this->filesAttributesConfig) as $attribute) {
            $this->getFileAttributeValue($attribute)->delete($options);
        }
    }
}
