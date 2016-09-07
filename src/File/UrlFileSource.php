<?php

namespace Bicycle\FilesManager\File;

use Bicycle\FilesManager\Contracts\FileSource as FileSourceInterface;
use Bicycle\FilesManager\Exceptions\FileSystemException;
use Bicycle\FilesManager\Exceptions\InvalidConfigException;
use Bicycle\FilesManager\Helpers\File as FileHelper;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class UrlFileSource implements FileSourceInterface
{
    use Traits\NotSupported, Traits\WithoutFormatting, Traits\WithoutRelativePath;

    /**
     * @var string
     */
    private $url;

    /**
     * @var boolean
     */
    private $isLocal;

    /**
     * @var string|null
     */
    private $urlWithoutParams;

    /**
     * @var array|null
     */
    private $headers;

    /**
     * @param string $url
     * @throws InvalidConfigException
     */
    public function __construct($url)
    {
        $this->url = $url;

        if (strncmp($url, '/', 1) === 0 && strncmp($url, '//', 2) !== 0) {
            $this->isLocal = true;
        } elseif (strncmp($url, 'http://', 7) === 0 || strncmp($url, 'https://', 8) === 0) {
            $this->isLocal = false;
        } else {
            throw new InvalidConfigException("Invalid url '$url', it must be a relative url in your public directory or an absolute url to resource by HTTP(s) protocol.");
        }
    }

    /**
     * @inheritdoc
     */
    protected function originExists()
    {
        if ($this->isLocal) {
            return (new SymfonyFile($this->localPath(), false))->isFile();
        } else {
            $header = $this->fetchUrlHeader(null);
            return preg_match('#^HTTP/[\d\.]+\s+2\d\d\s+#i', $header);
        }
    }

    /**
     * @inheritdoc
     */
    protected function originUrl()
    {
        return asset($this->url);
    }

    /**
     * @inheritdoc
     * @throws FileSystemException
     */
    protected function originContents()
    {
        $path = $this->isLocal ? $this->localPath() : $this->url;

        $contents = @file_get_contents($path);
        if ($contents === false || $contents === null) {
            throw new FileSystemException("Cannot read contents by path: '$path'.");
        }
        return $contents;
    }

    /**
     * @inheritdoc
     */
    protected function originName()
    {
        return FileHelper::filename($this->urlWithoutParams());
    }

    /**
     * @inheritdoc
     */
    protected function originSize()
    {
        if ($this->isLocal) {
            return (new SymfonyFile($this->localPath()))->getSize();
        }

        $result = $this->fetchUrlHeader('Content-Length');
        if ($result === null) {
            $result = mb_strlen($this->contents(), '8bit');
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    protected function originMimeType()
    {
        if ($this->isLocal) {
            return (new SymfonyFile($this->localPath()))->getMimeType() ?: null;
        }

        $result = $this->fetchUrlHeader('Content-Type') ?: null;
        if (false !== $pos = strrpos($result, ';')) {
            $result = substr($result, 0, $pos);
        }
        return trim($result);
    }

    /**
     * @ihneritdoc
     */
    protected function deleteOrigin()
    {
        // nothing to do
    }

    /**
     * @return string
     * @throws \BadMethodCallException
     */
    protected function localPath()
    {
        if (!$this->isLocal) {
            throw new \BadMethodCallException('The "' . __METHOD__ . '" method cannot be canned for non-local path.');
        }
        return public_path(ltrim($this->urlWithoutParams(), '\/'));
    }

    /**
     * @return string
     */
    protected function urlWithoutParams()
    {
        if ($this->urlWithoutParams !== null) {
            return $this->urlWithoutParams;
        }

        $url = $this->url;
        if (false !== $pos = mb_strrpos($url, '?', 0, 'UTF-8')) {
            $url = mb_substr($url, 0, $pos, 'UTF-8');
        } elseif (false !== $pos = mb_strrpos($url, '#', 0, 'UTF-8')) {
            $url = mb_substr($url, 0, $pos, 'UTF-8');
        }
        return $this->urlWithoutParams = urldecode($url);
    }

    /**
     * @return array
     * @throws \BadMethodCallException
     */
    protected function getHeaders()
    {
        if ($this->headers !== null) {
            return $this->headers;
        } elseif ($this->isLocal) {
            throw new \BadMethodCallException('The "' . __METHOD__ . '" method cannot be called for local paths.');
        }
        return $this->headers = @get_headers($this->url, true) ?: [];
    }

    /**
     * @param string|null $headerName
     * @return string|null
     * @throws \BadMethodCallException
     */
    protected function fetchUrlHeader($headerName)
    {
        $headers = array_reverse($this->getHeaders(), true);
        if ($headerName === null) {
            foreach ($headers as $key => $value) {
                if (is_int($key)) {
                    $result = $value;
                    break;
                }
            }
        } elseif (isset($headers[$headerName])) {
            $result = $headers[$headerName];
        } else {
            $lowered = strtolower($headerName);
            foreach ($headers as $name => $value) {
                if ($lowered === strtolower($name)) {
                    $result = $value;
                    break;
                }
            }
        }
        return isset($result)? (is_array($result) ? end($result) : $result) : null;
    }
}
