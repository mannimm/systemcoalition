<?php

class Nvncbl_MenuBuilderPro_Block_Adminhtml_Menu_Grid extends Mage_Adminhtml_Block_Widget_Grid {

	//protected $_filterVisibility = false;

	public function __construct()
	{
		parent::__construct();
		$this->setId('mbGrid');
		$this->setDefaultSort('menu_id');
		$this->setDefaultDir('ASC');
		$this->setSaveParametersInSession(true);
		$this->setUseAjax(false);
		$this->setVarNameFilter('menu_filter');

	}

	protected function _getStore()
	{
		$storeId = (int) $this->getRequest()->getParam('store', 0);
		return Mage::app()->getStore($storeId);
	}

	protected function _prepareCollection()
	{
		$store = $this->_getStore();
		$collection = Mage::getModel('menubuilderpro/menu')->getCollection()
			->addFieldToSelect('*');

		$this->setCollection($collection);

		parent::_prepareCollection();

		return $this;
	}

	protected function _prepareColumns()
	{
		$this->addColumn('id',
			array(
				'header'=> Mage::helper('catalog')->__('ID'),
				'width' => '50px',
				'type'  => 'number',
				'index' => 'id',
		));
		$this->addColumn('label',
			array(
				'header'=> Mage::helper('catalog')->__('Label'),
				'index' => 'label',
		));

		/*$this->addColumn('status',
			array(
				'header'=> Mage::helper('catalog')->__('Status'),
				'width' => '70px',
				'index' => 'status',
				'type'  => 'options',
				'options' => Mage::getSingleton('catalog/product_status')->getOptionArray(),
		));*/

		$this->addColumn('action',
			array(
				'header'    => Mage::helper('catalog')->__('Action'),
				'width'     => '50px',
				'type'      => 'action',
				'getter'     => 'getId',
				'actions'   => array(
					array(
						'caption' => Mage::helper('catalog')->__('Edit'),
						'url'     => array(
							'base'=>'*/*/edit',
							'params' => array() //array('store'=>$this->getRequest()->getParam('store'))
						),
						'field'   => 'id'
					)
				),
				'filter'    => false,
				'sortable'  => false,
				'index'     => 'stores',
		));

		return parent::_prepareColumns();
	}

	public function getGridUrl()
	{
		return $this->getUrl('*/*/*', array('_current'=>true));
	}

	public function getRowUrl($row)
	{
		return $this->getUrl('*/*/edit', array(
			'menu_id' => $row->getId()
		) );
	}

}
