<?php

namespace MageTest\MagentoExtension\Fixture;
use MageTest\MagentoExtension\Fixture;

/**
 * Product fixtures functionality provider
 *
 * @package MagentoExtension
 */
class Product implements FixtureInterface
{
    private $modelFactory = null;
    private $model;
    private $attributes;
    private $defaultAttributes;

    /**
     * @param $productModelFactory \Closure optional
     */
    public function __construct($productModelFactory = null)
    {
        $this->modelFactory = $productModelFactory ?: $this->defaultModelFactory();
    }

    /**
     * Create a product fixture using the given attributes map
     *
     * @param $attributes array product attributes map using 'label' => 'value' format
     *
     * @return int
     */
    public function create(array $attributes)
    {
        $modelFactory = $this->modelFactory;
        $this->model = $modelFactory();

        $id = $this->model->getIdBySku($attributes['sku']);
        if ($id) {
            $this->model->load($id);
        }

        $this->validateAttributes(array_keys($attributes));

        \Mage::app()->setCurrentStore(\Mage_Core_Model_App::ADMIN_STORE_ID);
        $this->model->setData($this->mergeAttributes($attributes))->save();
        \Mage::app()->setCurrentStore(\Mage_Core_Model_App::DISTRO_STORE_ID);

        return $this->model->getId();
    }

    function mergeAttributes($attributes)
    {
        return array_merge($this->getDefaultAttributes(), $this->model->getData(), $attributes);
    }

    function validateAttributes($attributes)
    {
        foreach ($attributes as $attribute) {
            if (!$this->attributeExists($attribute)) {
                throw new \RuntimeException("$attribute is not yet defined as an attribute of Product");
            }
        }
    }

    function attributeExists($attribute)
    {
        return in_array($attribute, array_keys($this->getDefaultAttributes()));
    }

    /**
     * Delete the requested fixture from Magento DB
     *
     * @param $identifier int object identifier
     *
     * @return null
     */
    public function delete($identifier) 
    {
        $modelFactory = $this->modelFactory;
        $model = $modelFactory();

        $model->load($identifier);
        $model->delete();
    }


    /**
     * retrieve default product model factory
     *
     * @return \Closure
     */
    public function defaultModelFactory()
    {
        return function () {
            return \Mage::getModel('catalog/product');
        };
    }

    protected function getDefaultAttributes()
    {
        if ($this->defaultAttributes) {
            return $this->defaultAttributes;
        }
        return $this->defaultAttributes = array(
            'sku' => '',
            'attribute_set_id' => $this->retrieveDefaultAttributeSetId(),
            'name' => 'product name',
            'weight' => 2,
            'visibility'=> \Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
            'status' => \Mage_Catalog_Model_Product_Status::STATUS_ENABLED,
            'price' => 100,
            'description' => 'Product description',
            'short_description' => 'Product short description',
            'tax_class_id' => 1,
            'type_id' => \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE,
            'stock_data' => array( 'is_in_stock' => 1, 'qty' => 99999 )
        );
    }

    protected function retrieveDefaultAttributeSetId()
    {
        return $this->model->getResource()
            ->getEntityType()
            ->getDefaultAttributeSetId();
    }
}