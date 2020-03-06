<?php
/**
 * SAML Extension for Magento2.
 *
 * @package     Pitbulk_SAML2
 * @copyright   Copyright (c) 2019 Sixto Martin Garcia (http://saml.info)
 * @license     Commercial
 */

namespace Pitbulk\SAML2\Model\Config\Source;

class AbstractArrayInterface implements \Magento\Framework\Option\ArrayInterface
{
    public $values = [];

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];

        foreach ($this->values as $value) {
            $option['value'] = $value;
            $option['label'] = $value;
            $options[] = $option;
        }

        return $options;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $options = [];

        foreach ($this->values as $value) {
            $options[$value] = $value;
        }

        return $options;
    }
}
