<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

require_once __DIR__.'/vendor/autoload.php';

use Foo\Repository\ProductRepository;

class Ps_Test extends Module
{
    public function __construct()
    {
        $this->name = 'ps_test';
        $this->version = '1.0.0';
        $this->author = 'Mickaël Andrieu';
        parent::__construct();
        $this->displayName = 'Tester';
        $this->description = 'Module to demonstrate new customization system with PrestaShop 1.7';
        $this->ps_versions_compliancy = [
            'min' => '1.7.2.0',
            'max' => _PS_VERSION_,
        ];
    }

    /**
     * Module installation.
     *
     * @return bool Success of the installation
     */
    public function install()
    {
        return parent::install() && $this->registerHook('displayDashboardToolbarIcons');
    }

    /**
     * Uninstall the module.
     *
     * @return bool Success of the uninstallation
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Make products export in XML.
     *
     * @param $params array
     */
    public function hookDisplayDashboardToolbarIcons($params)
    {
        if ($this->isSymfonyContext() && $params['route'] === 'admin_product_catalog') {
            $products = $this->getProducts(1);
            $productsXml = $this->serializeProducts($products);
            $filepath = _PS_ROOT_DIR_.'/products.xml';

            $this->writeFile($productsXml, $filepath);

            return $this->get('twig')->render('@PrestaShop/Foo/download_link.twig',[
                'filepath' => _PS_BASE_URL_.'/products.xml',
            ]);
        }
    }

    /**
     * Get product list from database by lang.
     *
     * @param int $langId
     * @return array the list of products.
     */
    public function getProducts(int $langId)
    {
        return $this->get('product_repository')->findAllByLangId($langId);
    }

    /**
     * Serialize a list of products.
     *
     * @param array $products
     * @return string the XML output
     */
    public function serializeProducts(array $products)
    {
        $productsXml = $this->get('serializer')->serialize($products, 'xml', [
            'xml_root_node_name' => 'products',
            'xml_format_output' => true,
        ]);

        return $productsXml;
    }

    /**
     * Write file into a specific location.
     *
     * @param string $fileContent
     * @param string $filepath
     * @throws IOException if the file cannot be written to
     * @return bool true if the file has been created
     */
    public function writeFile(string $fileContent, string $filepath)
    {
        $this->get('filesystem')->dumpFile($filepath, $fileContent);
    }
}
