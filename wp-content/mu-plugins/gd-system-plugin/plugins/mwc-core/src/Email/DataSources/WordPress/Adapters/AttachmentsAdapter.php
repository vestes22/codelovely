<?php

namespace GoDaddy\WordPress\MWC\Core\Email\DataSources\WordPress\Adapters;

use GoDaddy\WordPress\MWC\Common\DataSources\Contracts\DataSourceAdapterContract;
use GoDaddy\WordPress\MWC\Core\Email\Exceptions\EmailAttachmentException;

class AttachmentsAdapter implements DataSourceAdapterContract
{
    /** @var string attachment file path */
    protected $source;

    /**
     * Constructor.
     *
     * @param string $filePath
     */
    public function __construct(string $filePath)
    {
        $this->source = $filePath;
    }

    /**
     * Converts from Data Source format.
     *
     * @return array
     * @throws EmailAttachmentException
     */
    public function convertFromSource() : array
    {
        if (! $this->isFileAccessible($this->source)) {
            throw new EmailAttachmentException('Unable to access attachment file.');
        }

        if ($data = $this->buildAttachmentData($this->source)) {
            return $data;
        }

        throw new EmailAttachmentException('Unable to read attachment file content.');
    }

    /**
     * Builds required attachment data.
     *
     * @param string $filePath
     * @return array|null
     */
    protected function buildAttachmentData(string $filePath)
    {
        $content = file_get_contents($filePath);
        $contentType = mime_content_type($filePath);

        if (false === $content || false === $contentType) {
            return null;
        }

        return [
            'name'        => wp_basename($filePath),
            'contentType' => $contentType,
            'content'     => base64_encode($content),
        ];
    }

    /**
     * Checks if the given file path belongs to an existing file and can be read.
     *
     * @param string $filePath
     * @return bool
     */
    protected function isFileAccessible(string $filePath) : bool
    {
        return file_exists($filePath) && is_readable($filePath);
    }

    /**
     * Converts to Data Source format.
     */
    public function convertToSource()
    {
        // does nothing
    }
}
