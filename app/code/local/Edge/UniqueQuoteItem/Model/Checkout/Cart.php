<?php

class Edge_UniqueQuoteItem_Model_Checkout_Cart extends Mage_Checkout_Model_Cart
{
    public function updateItems($data)
    {
        Mage::dispatchEvent('checkout_cart_update_items_before', array('cart'=>$this, 'info'=>$data));

        foreach ($data as $itemId => $itemInfo) {
            $item = $this->getQuote()->getItemById($itemId);
            if (!$item) {
                continue;
            }

            $similarItems = Mage::helper('uniquequoteitem')->getSimilarItems($item);

            if (!empty($itemInfo['remove']) || (isset($itemInfo['qty']) && $itemInfo['qty']=='0')) {
                foreach ($similarItems as $similarItem) {
                    $this->removeItem($similarItem->getItemId());
                }
                continue;
            }

            $userQty = isset($itemInfo['qty']) ? (float) $itemInfo['qty'] : false;
            $quoteQty = sizeof($similarItems);
            $qtyChange = $userQty - $quoteQty;

            if ($qtyChange > 0) {
                $product = Mage::getModel('catalog/product')
                    ->setStoreId(Mage::app()->getStore()->getId())
                    ->load($item->getProductId());

                for ($i=0; $i<$qtyChange; $i++) {
                    $this->addProduct($product, $item->getBuyRequest());
                }
            }
            elseif ($qtyChange < 0) {
                $x=0;
                for ($i=0; $i>$qtyChange; $i--) {
                    $this->removeItem($similarItems[$x]->getItemId());
                    $x++;
                }
            }
        }

        Mage::dispatchEvent('checkout_cart_update_items_after', array('cart'=>$this, 'info'=>$data));
        return $this;
    }
}