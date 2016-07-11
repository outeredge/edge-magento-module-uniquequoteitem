<?php

class Edge_UniqueQuoteItem_Model_Sales_Quote extends Mage_Sales_Model_Quote
{
    protected function _addCatalogProduct(Mage_Catalog_Model_Product $product, $qty = 1)
    {
        $item = Mage::getModel('sales/quote_item');
        $item->setQuote($this);
        if (Mage::app()->getStore()->isAdmin()) {
            $item->setStoreId($this->getStore()->getId());
        }
        else {
            $item->setStoreId(Mage::app()->getStore()->getId());
        }

        $item->setOptions($product->getCustomOptions())
            ->setProduct($product);

        $this->addItem($item);
        return $item;
    }

    public function addProductAdvanced(Mage_Catalog_Model_Product $product, $request = null, $processMode = null)
    {
        if ($request === null) {
            $request = 1;
        }
        if (is_numeric($request)) {
            $request = new Varien_Object(array('qty'=>$request));
        }
        if (!($request instanceof Varien_Object)) {
            Mage::throwException(Mage::helper('sales')->__('Invalid request for adding product to quote.'));
        }

        switch ($product->getTypeId()) {
            case 'simple':
            case 'virtual':
            case 'downloadable':
            case 'configurable':
            case 'bundle':
                $qty = $request->getQty();
                if ($qty > 1) {
                    $request->setQty(1);
                    for($i=1; $i<$qty; $i++) {
                        $lineProduct = clone $product;
                        $lineRequest = clone $request;
                        $this->addProductAdvanced($lineProduct, $lineRequest, $processMode);
                    }
                }
                break;

            case 'grouped':
                $superGroup = $request->getSuperGroup();
                foreach ($superGroup as $productId=>$qty) {
                    if ($qty > 1) {
                        for($i=1; $i<$qty; $i++) {
                            $customSuperGroup = $request->getSuperGroup();
                            foreach ($customSuperGroup as $customProductId => $customQty) {
                                $customSuperGroup[$customProductId] = ($customProductId === $productId) ? 1 : 0;
                            }
                            $lineProduct = clone $product;
                            $lineRequest = clone $request;
                            $lineRequest->setSuperGroup($customSuperGroup);
                            $this->addProductAdvanced($lineProduct, $lineRequest, $processMode);
                        }
                    }
                    $superGroup[$productId] = ($qty > 0) ? 1 : 0;
                }
                $request->setSuperGroup($superGroup);
                break;
        }

        $cartCandidates = $product->getTypeInstance(true)
            ->prepareForCartAdvanced($request, $product, $processMode);

        /**
         * Error message
         */
        if (is_string($cartCandidates)) {
            return $cartCandidates;
        }

        /**
         * If prepare process return one object
         */
        if (!is_array($cartCandidates)) {
            $cartCandidates = array($cartCandidates);
        }

        $parentItem = null;
        $errors = array();
        $items = array();
        foreach ($cartCandidates as $candidate) {
            // Child items can be sticked together only within their parent
            $stickWithinParent = $candidate->getParentProductId() ? $parentItem : null;
            $candidate->setStickWithinParent($stickWithinParent);
            $item = $this->_addCatalogProduct($candidate, $candidate->getCartQty());
            if($request->getResetCount() && !$stickWithinParent && $item->getId() === $request->getId()) {
                $item->setData('qty', 0);
            }
            $items[] = $item;

            /**
             * As parent item we should always use the item of first added product
             */
            if (!$parentItem) {
                $parentItem = $item;
            }
            if ($parentItem && $candidate->getParentProductId()) {
                $item->setParentItem($parentItem);
            }

            /**
             * We specify qty after we know about parent (for stock)
             */
            $item->addQty($candidate->getCartQty());

            // collect errors instead of throwing first one
            if ($item->getHasError()) {
                $message = $item->getMessage();
                if (!in_array($message, $errors)) { // filter duplicate messages
                    $errors[] = $message;
                }
            }
        }
        if (!empty($errors)) {
            Mage::throwException(implode("\n", $errors));
        }

        Mage::dispatchEvent('sales_quote_product_add_after', array('items' => $items));

        return $item;
    }

    /**
     * Prevents items from being merged into 1 when customer login occurs
     * whilst guest has same item in quote
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return \Edge_UniqueQuoteItem_Model_Sales_Quote
     */
    public function merge(Mage_Sales_Model_Quote $quote)
    {
        Mage::dispatchEvent(
            $this->_eventPrefix . '_merge_before',
            array(
                 $this->_eventObject=>$this,
                 'source'=>$quote
            )
        );

        foreach ($quote->getAllVisibleItems() as $item) {
            $newItem = clone $item;
            $this->addItem($newItem);
            if ($item->getHasChildren()) {
                foreach ($item->getChildren() as $child) {
                    $newChild = clone $child;
                    $newChild->setParentItem($newItem);
                    $this->addItem($newChild);
                }
            }
        }

        /**
         * Init shipping and billing address if quote is new
         */
        if (!$this->getId()) {
            $this->getShippingAddress();
            $this->getBillingAddress();
        }

        if ($quote->getCouponCode()) {
            $this->setCouponCode($quote->getCouponCode());
        }

        Mage::dispatchEvent(
            $this->_eventPrefix . '_merge_after',
            array(
                 $this->_eventObject=>$this,
                 'source'=>$quote
            )
        );

        return $this;
    }
}
