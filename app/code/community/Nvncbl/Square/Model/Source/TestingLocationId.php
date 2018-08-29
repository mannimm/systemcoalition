<?php

require_once 'Nvncbl/Square/autoload.php';

class Nvncbl_Square_Model_Source_TestingLocationId
{
	public function toOptionArray()
	{
		$access_token = 'sandbox-sq0atb-n5mhPAUKWnTHEAMzotnKUw';

		$output = array(
			array(
				'value' => '',
				'label' => ' -- None selected --'
			)
		);
		if( $access_token ){

			try {

				SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($access_token);

				$location_api = new \SquareConnect\Api\LocationsApi();

				$result = $location_api->listLocations();

				foreach( $result->getLocations() as $location ){
					$output[] = array(
						'value' => $location->getId(),
						'label' => $location->getName() //.' via token: '. $access_token
					);
				}
			} catch( Exception $e ){
				Mage::log( $e->getMessage() );
			}
		}

		return $output;
	}
}
