<?php

class Nvncbl_Square_Model_Cron {

	public function renewAccessTokens(){

		$magento_scope = 'default';
		$magento_scope_id = 0;

		$value = Mage::getStoreConfig( 'payment/nvncbl_square/personal_access_token', 0 );
		if( $value ){
			$raw_access_token = explode( '___', $value );
			$access_token = $raw_access_token[0];
			$this->renewAccessToken( $access_token, $magento_scope, $magento_scope_id );
		}

		foreach (Mage::app()->getWebsites() as $website) {

			$magento_scope = 'websites';
			$magento_scope_id = $website->getId();

			$value = $website->getConfig( 'payment/nvncbl_square/personal_access_token' );
			if( $value ){
				$raw_access_token = explode( '___', $value );
				$access_token = $raw_access_token[0];
				$this->renewAccessToken( $access_token, $magento_scope, $magento_scope_id );
			}

			foreach ($website->getGroups() as $group) {
				$stores = $group->getStores();
				foreach ($stores as $store) {

					$magento_scope = 'stores';
					$magento_scope_id = $store->getId();

					$value = Mage::getStoreConfig( 'payment/nvncbl_square/personal_access_token', $magento_scope_id );
					if( $value ){
						$raw_access_token = explode( '___', $value );
						$access_token = $raw_access_token[0];
						$this->renewAccessToken( $access_token, $magento_scope, $magento_scope_id );
					}

				}
			}

		}

	}

	public function renewAccessToken( $access_token, $magento_scope, $magento_scope_id ){

		if( $access_token ){

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, 'https://nvncbl.com/tmp/square_oauth_renew.php?access_token='. $access_token .'&referrer_url='. urlencode( Mage::getUrl() ) );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			$raw_response = curl_exec( $ch );
			curl_close($ch);

			$response = json_decode( $raw_response );

			if (property_exists($response, 'access_token')) {
				Mage::getConfig()->saveConfig( 'payment/nvncbl_square/personal_access_token', $response->access_token .'___'. $response->expires_at, $magento_scope, $magento_scope_id );
				Mage::log( 'Square successfully authorized' );
			} else {
				Mage::log( 'Code exchange failed!' );
			}

		}

	}

}