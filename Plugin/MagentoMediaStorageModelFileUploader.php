<?php

namespace Ingenico\Payment\Plugin;

class MagentoMediaStorageModelFileUploader
{
    /**
     * Intercept original function and allow HTML file types
     */
    public function aroundCheckAllowedExtension(\Magento\MediaStorage\Model\File\Uploader $subject, callable $proceed, $extension)
    {
        if ($extension === 'html') {
            return true;
        }
        
        return $proceed;
    }
}
