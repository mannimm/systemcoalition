<?php

require_once 'Nvncbl/Square/autoload.php';

class Nvncbl_Square_Block_Adminhtml_Catalog_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	protected $_filterVisibility = false;

	public function __construct()
	{
		parent::__construct();
		$this->setId('catalogGrid');
		$this->setDefaultSort('id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(false);
		$this->setVarNameFilter('catalog_filter');

	}

	protected function _getStore()
	{
		$storeId = (int) $this->getRequest()->getParam('store', 0);
		return Mage::app()->getStore($storeId);
	}

	protected function _prepareCollection()
	{
		$collection = Mage::getModel('nvncbl_square/syncer')->getCollection();

		$this->setCollection($collection);

		parent::_prepareCollection();

		return $this;
	}

	protected function _prepareColumns()
	{

		$this->addColumn('changes_label',
			array(
				'header'=> Mage::helper('catalog')->__('Changes Needed'),
				'index' => 'changes_label',
		));

		$this->addColumn('magento_id',
			array(
				'header'=> Mage::helper('catalog')->__('Magento<br />ID'),
				'index' => 'entity_id',
		));

		$this->addColumn('magento_price',
			array(
				'header'=> Mage::helper('catalog')->__('Magento<br />Price'),
				'index' => 'magento_price',
		));

		$this->addColumn('magento_name',
			array(
				'header'=> Mage::helper('catalog')->__('Magento<br />Name'),
				'index' => 'magento_name',
		));

		$this->addColumn('magento_description',
			array(
				'header'=> Mage::helper('catalog')->__('Magento<br />Description'),
				'index' => 'magento_description',
		));

		$this->addColumn('magento_qty',
			array(
				'header'=> Mage::helper('catalog')->__('Magento<br />Qty'),
				'index' => 'magento_qty',
		));

		$this->addColumn('sku',
			array(
				'header'=> Mage::helper('catalog')->__('SKU'),
				'index' => 'sku',
		));

		$this->addColumn('qty',
			array(
				'header'=> Mage::helper('catalog')->__('Square<br />Qty'),
				'index' => 'qty',
		));

		$this->addColumn('name',
			array(
				'header'=> Mage::helper('catalog')->__('Square<br />Name / Variation'),
				'index' => 'name',
		));

		$this->addColumn('description',
			array(
				'header'=> Mage::helper('catalog')->__('Square Description'),
				'index' => 'description',
		));

		$this->addColumn('price',
			array(
				'header'=> Mage::helper('catalog')->__('Square<br />Price'),
				'index' => 'price',
		));

		$this->addColumn('square_id',
			array(
				'header'=> Mage::helper('catalog')->__('Square<br />Item ID'),
				'index' => 'square_id',
		));
		$this->addColumn('square_variation_id',
			array(
				'header'=> Mage::helper('catalog')->__('Square<br />Variation ID'),
				'index' => 'square_variation_id',
		));

		return parent::_prepareColumns();
	}

	public function getGridUrl()
	{
		return $this->getUrl('*/*/*', array('_current'=>true));
	}

	public function getRowUrl($row)
	{
		return false;
	}

}
