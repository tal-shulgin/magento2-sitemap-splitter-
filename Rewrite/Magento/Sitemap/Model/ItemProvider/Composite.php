<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Mageit\Sitemap\Rewrite\Magento\Sitemap\Model\ItemProvider;

use Magento\Sitemap\Model\ItemProvider\Composite as Base;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;

class Composite extends Base
{
    /**
     * Item resolvers
     *
     * @var ItemProviderInterface[]
     */
    private array $itemProviders;

    /**
     * Composite constructor.
     *
     * @param ItemProviderInterface[] $itemProviders
     */
    public function __construct(array $itemProviders = [])
    {
        $this->itemProviders = $itemProviders;
    }

    /**
     * {@inheritdoc}
     */
    public function getItems($storeId): array
    {
        $items = [];

        foreach ($this->itemProviders as $resolver) {
            foreach ($resolver->getItems($storeId) as $item) {
                $items[$this->getClassName($resolver)][] = $item;
            }
        }

        return $items;
    }

    /**
     * @param $resolver
     *
     * @return string
     */
    protected function getClassName($resolver): string
    {
        preg_match('/[^\\\\]+$/', get_class($resolver), $matches);
        return strtolower($matches[0]);
    }
}
