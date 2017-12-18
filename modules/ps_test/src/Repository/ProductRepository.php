<?php

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

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
}
