<?php

declare(strict_types=1);

namespace MarkShust\PolyshellPatch\Plugin;

use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Api\ImageProcessor;
use Magento\Framework\Api\Uploader;

/**
 * Enforce an allowlist of file extensions before ImageProcessor saves uploaded files.
 *
 * Mitigates PolyShell (APSB25-94): the core ImageProcessor never calls
 * setAllowedExtensions() on the Uploader, so any extension — including .php — is accepted.
 */
class ImageProcessorRestrictExtensions
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png'];

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @param Uploader $uploader
     */
    public function __construct(Uploader $uploader)
    {
        $this->uploader = $uploader;
    }

    /**
     * Before processImageContent, lock the uploader to image-only extensions.
     *
     * @param ImageProcessor $subject
     * @param string $entityType
     * @param ImageContentInterface $imageContent
     * @return null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeProcessImageContent(
        ImageProcessor $subject,
        $entityType,
        $imageContent
    ) {
        $this->uploader->setAllowedExtensions(self::ALLOWED_EXTENSIONS);
        return null;
    }
}
