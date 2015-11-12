<?php

class Edge_UniqueQuoteItem_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getSimilarItems($item)
    {
        $similarItems = array();
        $keyString = $this->getItemKeyString($item);

        $quote = Mage::getSingleton('checkout/session')->getQuote();
        foreach ($quote->getAllVisibleItems() as $quoteItem) {
            if ($this->getItemKeyString($quoteItem) === $keyString) {
                $similarItems[] = $quoteItem;
            }
        }
        return $similarItems;
    }

    public function getItemKeyString($item)
    {
        $buyRequest = $item->getBuyRequest();

        $key = array(
            $item->getProductType(),
            $item->getProductId(),
            $item->getStoreId(),
            $item->getSku(),
            $item->getPrice()
        );

        switch ($item->getProductType()) {
            case 'downloadable':
                if ($buyRequest->getLinks()) {
                    $key[] = http_build_query($buyRequest->getLinks());
                }
                break;
            case 'configurable':
                if ($buyRequest->getSuperAttribute()) {
                    $key[] = http_build_query($buyRequest->getSuperAttribute());
                }
                break;
            case 'bundle':
                if ($buyRequest->getBundleOption()) {
                    $key[] = http_build_query($buyRequest->getBundleOption());
                }
                if ($buyRequest->getBundleOptionQty()) {
                    $key[] = http_build_query($buyRequest->getBundleOptionQty());
                }
                break;
        }

        if ($buyRequest->getOptions()) {
            $key[] = http_build_query($buyRequest->getOptions());
        }

        $keyString = implode(':', $key);
        return $keyString;
    }
}