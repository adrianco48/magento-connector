<?php

class Brera_MagentoConnector_Model_Resource_Product_Queue_Item extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('brera_magentoconnector/product_queue', 'product_queue_id');
    }

    /**
     * @param int[] $productIds
     * @param string $action
     */
    public function saveProductIds(array $productIds, $action)
    {
        $dataToInsert = [];
        foreach ($productIds as $productId) {
            $dataToInsert[] = [
                'product_id' => $productId,
                'action' => $action,
            ];
        }
        $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $dataToInsert);
    }


    /**
     * @param Brera_MagentoConnector_Model_Product_Queue_Item $object
     * @return $this
     */
    public function save(Brera_MagentoConnector_Model_Product_Queue_Item $object)
    {
        if ($object->isDeleted()) {
            return $this->delete($object);
        }

        $this->_serializeFields($object);
        $this->_beforeSave($object);
        $this->_checkUnique($object);
        $bind = $this->_prepareDataForSave($object);
        if ($this->_isPkAutoIncrement) {
            unset($bind[$this->getIdFieldName()]);
        }
        $this->_getWriteAdapter()->insertOnDuplicate($this->getMainTable(), $bind);

        $object->setId($this->_getWriteAdapter()->lastInsertId($this->getMainTable()));

        if ($this->_useIsObjectNew) {
            $object->isObjectNew(false);
        }

        $this->unserializeFields($object);
        $this->_afterSave($object);

        return $this;
    }
}
