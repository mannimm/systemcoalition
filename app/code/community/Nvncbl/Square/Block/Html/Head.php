<?php

class Nvncbl_Square_Block_Html_Head extends Mage_Page_Block_Html_Head
{
	protected function _separateOtherHtmlHeadElements(&$lines, $itemIf, $itemType, $itemParams, $itemName, $itemThe)
	{
		$params = $itemParams ? ' ' . $itemParams : '';
		$href   = $itemName;
		switch ($itemType) {
			case 'rss':
				$lines[$itemIf]['other'][] = sprintf('<link href="%s"%s rel="alternate" type="application/rss+xml" />',
					$href, $params
				);
				break;
			case 'link_rel':
				$lines[$itemIf]['other'][] = sprintf('<link%s href="%s" />', $params, $href);
				break;
			case 'external_js':
				$lines[$itemIf]['other'][] = sprintf('<script type="text/javascript" src="%s" %s></script>', $href, $params);
				break;
		}
	}
}
