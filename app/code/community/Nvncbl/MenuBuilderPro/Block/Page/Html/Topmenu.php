<?php

class Nvncbl_MenuBuilderPro_Block_Page_Html_Topmenu extends Mage_Page_Block_Html_Topmenu {

	protected function _getMenuItemClasses(Varien_Data_Tree_Node $item)
	{
		$classes = parent::_getMenuItemClasses( $item );
		$classes[] = $item->getCss();
		return $classes;
	}

	protected function _getHtml(Varien_Data_Tree_Node $menuTree, $childrenWrapClass)
	{
		$html = '';

		$children = $menuTree->getChildren();
		$parentLevel = $menuTree->getLevel();
		$childLevel = is_null($parentLevel) ? 0 : $parentLevel + 1;

		$counter = 1;
		$childrenCount = $children->count();

		$parentPositionClass = $menuTree->getPositionClass();
		$itemPositionClassPrefix = $parentPositionClass ? $parentPositionClass . '-' : 'nav-';

		foreach ($children as $child) {

			$child->setLevel($childLevel);
			$child->setIsFirst($counter == 1);
			$child->setIsLast($counter == $childrenCount);
			$child->setPositionClass($itemPositionClassPrefix . $counter);

			$outermostClassCode = '';
			$outermostClass = $menuTree->getOutermostClass();

			if ($childLevel == 0 && $outermostClass) {
				$outermostClassCode = ' class="' . $outermostClass . '" ';
				$child->setClass($outermostClass);
			}

			$html .= '<li ' . $this->_getRenderedMenuItemAttributes($child) . '>';

			/* NVNCBL CUSTOM */
			$mbItemData = $child->getMbItemData();
			if( $mbItemData['item_type'] == 'customhtml' ){
				$html .= '<a>'. $mbItemData['custom_html'] .'</a>';
			} else if( $mbItemData['item_type'] == 'subcategories' ){
				$html .= $mbItemData['subcategories'];
			} else if( $mbItemData['item_type'] == 'staticblock' ){
				$static_block = Mage::getModel('cms/block')->load( $mbItemData['staticblock'] );
				$html .= '<a>'. Mage::app()->getLayout()->createBlock('cms/block')->setBlockId( $static_block->getIdentifier() )->toHtml() .'</a>';
			} else {
			/* END NVNCBL CUSTOM */
				$html .= '<a href="' . $child->getUrl() . '" ' . $outermostClassCode . '><span>'
					. $this->escapeHtml($child->getName()) . '</span></a>';

				if ($child->hasChildren()) {
					if (!empty($childrenWrapClass)) {
						$html .= '<div class="' . $childrenWrapClass . '">';
					}
					$html .= '<ul class="level' . $childLevel . '">';
					$html .= $this->_getHtml($child, $childrenWrapClass);
					$html .= '</ul>';

					if (!empty($childrenWrapClass)) {
						$html .= '</div>';
					}
				}
			/* NVNCBL CUSTOM */
			}
			/* END NVNCBL CUSTOM */

			$html .= '</li>';

			$counter++;
		}

		return $html;
	}

}