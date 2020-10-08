<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Stock;

/**
 * Description of ProductsProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProductsProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [
            'productos.referencia' => ['title' => 'reference'],
            'productos.descripcion' => ['title' => 'description'],
            'productos.observaciones' => ['title' => 'observations'],
            'variantes.codbarras' => ['title' => 'barcode'],
            'variantes.precio' => ['title' => 'price'],
            'variantes.coste' => ['title' => 'cost-price'],
            'stocks.cantidad' => ['title' => 'stock']
        ];
    }

    /**
     * 
     * @param array $item
     *
     * @return bool
     */
    protected function importItem(array $item): bool
    {
        $where = [];
        if (isset($item['productos.referencia']) && !empty($item['productos.referencia'])) {
            $where[] = new DataBaseWhere('referencia', $item['productos.referencia']);
        } elseif (isset($item['productos.descripcion']) && !empty($item['productos.descripcion'])) {
            $where[] = new DataBaseWhere('descripcion', $item['productos.descripcion']);
        }

        if (empty($where)) {
            return false;
        }

        $product = new Producto();
        if ($product->loadFromCode('', $where) && $this->mode === static::INSERT_MODE) {
            return false;
        }

        $this->setModelValues($product, $item, 'productos.');

        /// empty reference?
        if (empty($product->referencia)) {
            $product->referencia = $product->newCode('referencia');
        }

        if ($product->save()) {
            $this->importVariant($product, $item);
            $this->importStock($product, $item);
            return true;
        }

        return false;
    }

    /**
     * 
     * @param Producto $product
     * @param array    $item
     *
     * @return bool
     */
    protected function importStock($product, $item): bool
    {
        if (empty($item['stocks.cantidad'])) {
            return true;
        }

        /// find stock
        $stockModel = new Stock();
        $where = [new DataBaseWhere('referencia', $product->referencia)];
        if ($stockModel->loadFromCode('', $where)) {
            $this->setModelValues($stockModel, $item, 'stocks.');
            return $stockModel->save();
        }

        /// new stock
        $newStock = new Stock();
        $newStock->codalmacen = $this->toolBox()->appSettings()->get('default', 'codalmacen');
        $newStock->idproducto = $product->idproducto;
        $newStock->referencia = $product->referencia;
        $this->setModelValues($newStock, $item, 'stocks.');
        return $newStock->save();
    }

    /**
     * 
     * @param Producto $product
     * @param array    $item
     *
     * @return bool
     */
    protected function importVariant($product, $item): bool
    {
        foreach ($product->getVariants() as $variant) {
            $this->setModelValues($variant, $item, 'variantes.');
            return $variant->save();
        }

        return false;
    }
}
