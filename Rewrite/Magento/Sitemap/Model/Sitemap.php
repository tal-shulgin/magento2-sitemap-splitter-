<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mageit\Sitemap\Rewrite\Magento\Sitemap\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sitemap\Model\Sitemap as SitemapAlias;

class Sitemap extends SitemapAlias
{
    protected string $originalFilename = '';

    /**
     * Gets the Original Filename.
     *
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return $this->originalFilename;
    }

    /**
     * Sets the Original Filename.
     *
     * @param string $originalFilename
     *
     * @return Sitemap
     */
    public function setOriginalFilename(string $originalFilename): Sitemap
    {
        $this->originalFilename = $originalFilename;
        return $this;
    }

    /**
     * Resets Sitemap Filename to the original.
     *
     * @return void
     */
    protected function resetSitemapFile()
    {
        $this->setSitemapFilename($this->getOriginalFilename());
    }

    /**
     * Generate Custom Sitemap each procces item in seperate file.
     *
     * @param $name
     *
     * @return Sitemap|$this
     * @throws LocalizedException
     */
    public function generateCustomSitemap($name): Sitemap|static
    {
        $this->sitemapResetCounters();
        if (!is_array($this->_sitemapItems[$name])) {
            $item = $this->_sitemapItems[$name];
            $this->sitemapItemProcess($name, $item);
        } else {
            foreach ($this->_sitemapItems[$name] as $item) {
                $this->sitemapItemProcess($name, $item);
            }
        }

        return $this;
    }

    /**
     * Reset all counters.
     *
     * @return void
     */
    public function sitemapResetCounters(): void
    {
        $this->_lineCount        = 0;
        $this->_fileSize         = 0;
        $this->_sitemapIncrement = 0;
    }

    /**
     * Sitemap Item Process.
     *
     * @param $name
     * @param $item
     *
     * @return void
     * @throws LocalizedException
     */
    public function sitemapItemProcess($name, $item): void
    {
        $xml = $this->_getSitemapRow(
            $item->getUrl(),
            $item->getUpdatedAt(),
            $item->getChangeFrequency(),
            $item->getPriority(),
            $item->getImages()
        );

        if ($this->_isSplitRequired($xml) && $this->_sitemapIncrement > 0) {
            $this->_finalizeSitemap();
        }

        if (!$this->_fileSize) {
            $sitemapName = str_replace('.xml', '', $this->getSitemapFilename());
            $sitemapName = str_replace("-{$name}", '', $sitemapName);
            $sitemapName .= "-{$name}.xml";

            $this->setSitemapFilename($sitemapName);
            $this->_createSitemap();
        }

        $this->_writeSitemapRow($xml);
        // Increase counters
        $this->_lineCount++;
        $this->_fileSize += strlen($xml);
    }

    /**
     * Generate XML file
     *
     * @see http://www.sitemaps.org/protocol.html
     *
     * @return $this
     * @throws LocalizedException
     */
    public function generateXml()
    {
        $this->_initSitemapItems();
        $this->setOriginalFilename($this->getSitemapFilename());
        $indexRow = [];

        foreach ($this->_sitemapItems as $key => $items) {
            $this->generateCustomSitemap($key);

            if ($this->_sitemapIncrement == 1) {
                // In case when only one increment file was created use it as default sitemap
                $sitemapPath = $this->getSitemapPath() !== null ? rtrim($this->getSitemapPath(), '/') : '';
                $path = $sitemapPath . '/' . $this->_getCurrentSitemapFilename($this->_sitemapIncrement);
                $destination = $sitemapPath . '/' . $this->getSitemapFilename();

                $this->_directory->renameFile($path, $destination);
            }

            if ($this->_sitemapIncrement > 1) {
                for ($i = 1; $i <= $this->_sitemapIncrement; $i++) {
                    $indexRow[] = $this->_getCurrentSitemapFilename($i);
                }
            } else {
                $indexRow[] = $this->getSitemapFilename();
            }

            $this->_finalizeSitemap();
            $this->resetSitemapFile();
        }

        $this->_createSitemap($this->getSitemapFilename(), self::TYPE_INDEX);
        foreach ($indexRow as $sitemapName) {
            $xml = $this->_getSitemapIndexRow($sitemapName, $this->_getCurrentDateTime());
            $this->_writeSitemapRow($xml);
        }

        $this->_finalizeSitemap(self::TYPE_INDEX);
        $this->setSitemapType(self::TYPE_INDEX);
        $this->setSitemapTime($this->_dateModel->gmtDate('Y-m-d H:i:s'));
        $this->save();

        return $this;
    }
}
