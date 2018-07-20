<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Experius\WysiwygDownloads\Model\Wysiwyg\Images;

use Magento\Cms\Helper\Wysiwyg\Images;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Wysiwyg Images model
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * @api
 * @since 100.0.2
 */
class Storage extends \Magento\Cms\Model\Wysiwyg\Images\Storage
{

    const BASE_IMAGE_EXTENSIONS = [ 'jpeg', 'jpg', 'png', 'gif' ];
    const EXTENSION_PATTERN = '/(.*)\.([a-z0-9]+)$/';

    /**
     * @Override 
     * Upload new file
     * We are rewriting this function completely because it doesn't account for non images
     * So therefore shouldnt resize files that are pdf's etc
     * Other modules use the wysiwig where base Magento calls this class instead of a general file class
     * That way modules like MagePlaza's Blog can benefit from extra files too
     *
     * @param string $targetPath Target directory
     * @param string $type Type of storage, e.g. image, media etc.
     * @return array File info Array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadFile($targetPath, $type = null)
    {
        /** @var \Magento\MediaStorage\Model\File\Uploader $uploader */
        $uploader = $this->_uploaderFactory->create(['fileId' => 'image']);
        $allowed = $this->getAllowedExtensions($type);
        if ($allowed) {
            $uploader->setAllowedExtensions($allowed);
        }
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);
        $result = $uploader->save($targetPath);

        if (!$result) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t upload the file right now.'));
        }

        // create thumbnail if is image
        $ext = $this->getExtensionFromName($result['name']);
        if(in_array($ext, self::BASE_IMAGE_EXTENSIONS))
            $this->resizeFile($targetPath . '/' . $uploader->getUploadedFileName(), true);

        $result['cookie'] = [
            'name' => $this->getSession()->getName(),
            'value' => $this->getSession()->getSessionId(),
            'lifetime' => $this->getSession()->getCookieLifetime(),
            'path' => $this->getSession()->getCookiePath(),
            'domain' => $this->getSession()->getCookieDomain(),
        ];

        return $result;
    }

    /** Gets an extension string based on the given filename
     * @return string the extension
     */
    public function getExtensionFromName(string $name)
    {
        return preg_replace(self::EXTENSION_PATTERN, '$2', $name);
    }

}
