<?php

class IWD_All_Block_Jsinit extends Mage_Adminhtml_Block_Template
{
    /**
     * Print init JS script into body
     * @return string
     */
    protected function _toHtml()
    {
        $section = $this->getAction()->getRequest()->getParam('section', false);
        if ($section == 'iwdstore') {
            return parent::_toHtml();
        } else {
            return '';
        }
    }

    function urlGetContents($url)
    {
        $cUrl = curl_init();
        curl_setopt($cUrl, CURLOPT_URL, $url . '?today=' . date('Y-m-d-H'));
        curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($cUrl, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)");
        curl_setopt($cUrl, CURLOPT_TIMEOUT, 3);
        curl_setopt($cUrl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($cUrl,  CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($cUrl);
        if (curl_getinfo($cUrl, CURLINFO_HTTP_CODE) != 200) {
            curl_close($cUrl);
            return false;
        } else {
            return $content;
        }
    }

    public function getExtensions()
    {
        $data = array();
        try {
            $file = 'https://www.iwdagency.com/pub/media/featured_product.csv';
            $content = $this->urlGetContents($file);
            if ($content == false) {
                return "";
            }

            $path = Mage::getBaseDir('media') . DS . 'import' . DS . 'featured_product.csv';
            if (!file_exists(Mage::getBaseDir('media') . DS . 'import')) {
                mkdir(Mage::getBaseDir('media') . DS . 'import');
            }

            file_put_contents($path, $content);

            $csv = new Varien_File_Csv();
            $data = $csv->getData($path);

            @unlink($path);
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return $data;
    }
}
