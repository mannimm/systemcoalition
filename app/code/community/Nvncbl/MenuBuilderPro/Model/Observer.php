<?php

class Nvncbl_MenuBuilderPro_Model_Observer {

	public function setTopMenu( Varien_Event_Observer $observer ){

		/* Load chosen menu */
		$active = Mage::getStoreConfig( 'menubuilderpro/topmenu/active' );
		$chosen_menu = Mage::getStoreConfig( 'menubuilderpro/topmenu/menu' );

		if( !$active || !$chosen_menu ){
			return false;
		}

		$this->setMenu( $observer->getEvent()->getMenu(), $chosen_menu );

	}

	public function setMenu( $menu, $chosen_menu ){

		/* Remove existing menu */
		foreach( $menu->getChildren() as $child ){
			$menu->removeChild( $child );
		}

		/* Add MenuBuilder menu */
		$mb_tree = Mage::helper('menubuilderpro')->getTree( $chosen_menu );
		$this->_addItems( $mb_tree['subitems'], $menu );

		return $this;

	}

	protected function _addItems( $mbItems, $menu ){

		foreach( $mbItems as $node_id => $mbItem ){

			if( $mbItem['item_type'] == 'subcategories' ){

				$categories = Mage::getResourceModel('catalog/category_collection')
					->addAttributeToSelect('*')
					->addFieldToFilter('parent_id', $mbItem['subcategories'] );
				$this->_addCategoriesToMenu( $categories, $menu );

			} else {

				$mbItemData['name'] = $mbItem['label'];
				$mbItemData['id'] = 'mb-node-'. $node_id;
				if( $mbItem['link_category'] ){
					$mbItemData['url'] = Mage::getModel('catalog/category')->load( $mbItem['link_category'] )->getUrl();
				} else if( $mbItem['link_cmspage'] ){
					$mbItemData['url'] = Mage::helper('cms/page')->getPageUrl( $mbItem['link_cmspage'] );
				} else {
					if( $mbItem['link_base_url'] == 1 ){
						$mbItemData['url'] = Mage::getBaseUrl() . $mbItem['link'];
					} else {
						$mbItemData['url'] = $mbItem['link'];

					}
				}

				if( $mbItem['link_target'] != '' ){
					$mbItemData['url'] .= "\" target=\"". $mbItem['link_target'] ."";
				}

				$mbItemData['css'] = $mbItem['css'];
				$mbItemData['mb_item_data'] = $mbItem;

				$mbItemNode = new Varien_Data_Tree_Node( $mbItemData, 'id', $menu->getTree(), $menu);

				$menu->addChild( $mbItemNode );

				// Add the item's subcategories
				$this->_addItems( $mbItem['subitems'], $mbItemNode );

			}

		}

	}

	protected function _addCategoriesToMenu($categories, $parentCategoryNode){
		foreach( $categories as $category ){
			if( !$category->getIsActive() ){
				continue;
			}

			$nodeId = 'category-node-' . $category->getId();

			$tree = $parentCategoryNode->getTree();
			$categoryData = array(
				'name' => $category->getName(),
				'id' => $nodeId,
				'url' => Mage::helper('catalog/category')->getCategoryUrl($category),
				'is_active' => $this->_isActiveMenuCategory($category)
			);
			$categoryNode = new Varien_Data_Tree_Node($categoryData, 'id', $tree, $parentCategoryNode);
			$parentCategoryNode->addChild($categoryNode);

			if (Mage::helper('catalog/category_flat')->isEnabled()) {
				$subcategories = (array)$category->getChildrenNodes();
			} else {
				$subcategories = $category->getChildren();
			}

			$this->_addCategoriesToMenu($subcategories, $categoryNode);
		}
	}

	/* from catalog observer */
	protected function _isActiveMenuCategory( $category ){
		$catalogLayer = Mage::getSingleton('catalog/layer');
		if (!$catalogLayer) {
			return false;
		}

		$currentCategory = $catalogLayer->getCurrentCategory();
		if (!$currentCategory) {
			return false;
		}

		$categoryPathIds = explode(',', $currentCategory->getPathInStore());
		return in_array($category->getId(), $categoryPathIds);
	}

}