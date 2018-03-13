<?php
/**
 * 2007-2018 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
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
 * @copyright 2007-2018 PrestaShop SA
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */


namespace Foo\Repository;

use Doctrine\DBAL\Connection;

class ProductRepository
{
    /**
     * @var Connection the Database connection.
     */
    private $connection;

    /**
     * @var string the Database prefix.
     */
    private $databasePrefix;

    public function __construct(Connection $connection, $databasePrefix)
    {
        $this->connection = $connection;
        $this->databasePrefix = $databasePrefix;
    }

    /**
     * @param int $langId the lang id
     * @return array the list of products
     */
    public function findAllbyLangId(int $langId)
    {
        $prefix = $this->databasePrefix;
        $productTable = "${prefix}product";
        $productLangTable = "${prefix}product_lang";

        $query = "SELECT p.* FROM ${productTable} p LEFT JOIN ${productLangTable} pl ON (p.`id_product` = pl.`id_product`) WHERE pl.`id_lang` = :langId";
        $statement = $this->connection->prepare($query);
        $statement->bindValue('langId', $langId);
        $statement->execute();
        
        return $statement->fetchAll(\PDO::FETCH_OBJ);
    }
}