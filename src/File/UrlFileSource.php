<?php

namespace Solbeg\FilesManager\File;

use Solbeg\FilesManager\Exceptions\FileSystemException;
use Solbeg\FilesManager\Exceptions\InvalidConfigException;
use Solbeg\FilesManager\Helpers\File as FileHelper;

use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/**
 * @author Alexey Sejnov <alexey.sejnov@solbeg.com>
 */
class UrlFileSource extends AbstractFileSource
{
    use Traits\NotSupported;
    use Traits\WithoutFormatting;
    use Traits\WithoutRelativePath;

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
            return (bool) preg_match('#^HTTP/[\d\.]+\s+2\d\d\s+#i', $header);
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
        return new ContentStreams\Path($path);
    }

    /**
     * @inheritdoc
     */
    protected function originBasename()
    {
        return FileHelper::basename($this->urlWithoutParams());
    }

    /**
     * @inheritdoc
     */
    protected function originSize()
    {
        if ($this->isLocal) {
            return (new SymfonyFile($this->localPath()))->getSize();
        }

        $header = $this->fetchUrlHeader('Content-Length');
        if ($header !== null) {
            return $header;
        }

        $contents = $this->contents();
        try {
            return mb_strlen($contents->contents(), '8bit');
        } finally {
            $contents->close();
        }
    }

    /**
     * @inheritdoc
     */
    protected function originLastModified()
    {
        if ($this->isLocal) {
            return (new SymfonyFile($this->localPath()))->getMTime();
        }

        $header = $this->fetchUrlHeader('Last-Modified');
        if ($header !== null) {
            $header = strtotime($header) ?: null;
        }
        return $header ?: time();
    }

    /**
     * @inheritdoc
     */
    protected function originMimeType()
    {
        if ($this->isLocal) {
            return (new SymfonyFile($this->localPath()))->getMimeType() ?: null;
        }

        $result = (string) $this->fetchUrlHeader('Content-Type');
        if (false !== $pos = strrpos($result, ';')) {
            $result = substr($result, 0, $pos);
        }
        return trim($result) ?: null;
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
