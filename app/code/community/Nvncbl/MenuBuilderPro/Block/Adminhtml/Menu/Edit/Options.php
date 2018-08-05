<?php

class Nvncbl_MenuBuilderPro_Block_Adminhtml_Menu_Edit_Options extends Mage_Core_Block_Template {

	protected $_availableCategories = array();
	protected $_availableLinkCategories = array();
	protected $_availableLinkCmsPages = array();
	protected $_availableStaticblocks = array();

	public function getSubcategoryOptions(){
		$this->_availableCategories = array();
		$root_categories = Mage::getModel('adminhtml/system_config_source_category')->toOptionArray();
		foreach( $root_categories as $cat_id => $name ){
			$this->_assemble_cats( $cat_id );
		}
		return $this->_availableCategories;
	}

	protected function _assemble_cats( $cat_id ){
		if( !$cat_id ){ return; }
		$category = Mage::getModel('catalog/category')->load( $cat_id );
		if( $category->hasChildren() ){
			if( $cat_id != '1' ){
				$this->_availableCategories[ $cat_id ] = $category->getName();
			}
			foreach( $category->getChildrenCategories() as $subcategory ){
				$this->_assemble_cats( $subcategory->getId() );
			}
		}
	}

	public function getLinkSubcategoryOptions(){
		$this->_availableCategories = array();
		$root_categories = Mage::getModel('adminhtml/system_config_source_category')->toOptionArray();
		foreach( $root_categories as $cat_id => $name ){
			$this->_assemble_link_cats( $cat_id );
		}
		return $this->_availableLinkCategories;
	}

	protected function _assemble_link_cats( $cat_id, $level = '' ){
		if( !$cat_id ){ return; }
		$category = Mage::getModel('catalog/category')->load( $cat_id );
		if( $category->getLevel() > '1' ){
			$this->_availableLinkCategories[ $cat_id ] = $level . $category->getName();
		}
		foreach( $category->getChildrenCategories() as $subcategory ){
			$this->_assemble_link_cats( $subcategory->getId(), $level .'--' );
		}
	}

	public function getLinkCmsPageOptions(){
		$cms_pages = Mage::getResourceModel('cms/page_collection');
		foreach( $cms_pages as $cms_page ){
			$this->_availableLinkCmsPages[ $cms_page->getPageId() ] = $cms_page->getTitle() .' ('. $cms_page->getIdentifier() .')';
		}
		return $this->_availableLinkCmsPages;
	}

	public function getStaticblockOptions(){
		$this->_availableStaticblocks = array();
		$static_blocks = Mage::getResourceModel('cms/block_collection');
		foreach( $static_blocks as $static_block ){
			$this->_availableStaticblocks[ (string) $static_block->getId() ] = $static_block->getTitle();
		}
		return $this->_availableStaticblocks;
	}

}