<?php

class Nvncbl_MenuBuilderPro_ConfigureController extends Mage_Adminhtml_Controller_Action {

	public function indexAction() {

		$this->_title($this->__('Menu Setup'));

		$this->loadLayout();
		$this->renderLayout();
	}

	public function editAction() {

		$this->_title($this->__('MenuBuilderPro'))
			 ->_title($this->__('Edit Menu'));

		$menu_id = Mage::app()->getRequest()->getParam('menu_id');
		$menuLabel = Mage::app()->getRequest()->getParam('menuLabel');

		$hiddenMenu = Mage::app()->getRequest()->getParam('hiddenMenu');
		if( $hiddenMenu ){

			$menu = Mage::getModel('menubuilderpro/menu');

			if( $menu_id ){
				$menu->load( $menu_id );
			}

			$menu->setLabel( $menuLabel );
			$menu->setTree( str_replace( '[null]', '[]', $hiddenMenu ) );

			try {
				$menu->save();
				if( !$menu_id ){
					Mage::getSingleton('adminhtml/session')->addSuccess( 'Menu successfully created' );
					session_write_close();
					$this->_redirect( '*/*/' );
				} else {
					Mage::getSingleton('adminhtml/session')->addSuccess( 'Menu successfully saved' );
				}
			} catch( Exception $e ){
				Mage::getSingleton('adminhtml/session')->addError( 'Failed to save menu: '. $e->getMessage() );
			}
		}

		$this->loadLayout();
		$this->renderLayout();

	}

	public function deleteAction(){

		$menu_id = Mage::app()->getRequest()->getParam('menu_id');
		$menu = Mage::getModel('menubuilderpro/menu')->load( $menu_id );
		if( !$menu ){
			Mage::getSingleton('adminhtml/session')->addError( 'Menu no longer exists' );
			session_write_close();
			$this->_redirect( '*/*/' );
			return;
		}

		try {
			$menu->delete();

			Mage::getSingleton('adminhtml/session')->addSuccess( 'Menu "'. $menu->getLabel() .'" deleted' );
			session_write_close();
			$this->_redirect( '*/*/' );
		} catch( Exception $e ){
			Mage::getSingleton('adminhtml/session')->addError( 'Failed to delete menu: '. $e->getMessage() );
		}

	}

}