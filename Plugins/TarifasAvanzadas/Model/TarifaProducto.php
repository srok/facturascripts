<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of TarifaProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TarifaProducto extends Base\ModelClass
{

    use Base\ModelTrait;

    /**
     *
     * @var string
     */
    public $codtarifa;

    /**
     *
     * @var float
     */
    public $pvp;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var string
     */
    public $referencia;

    public function clear()
    {
        parent::clear();
        $this->pvp = 0.0;
    }

    /**
     * 
     * @return Variante
     */
    public function getVariant()
    {
        $variant = new Variante();
        $where = [new DataBaseWhere('referencia', $this->referencia)];
        $variant->loadFromCode('', $where);
        return $variant;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @param float $price
     */
    public function setPriceWithTax($price)
    {
        $newPrice = (100 * $price) / (100 + $this->getVariant()->getProducto()->getTax()->iva);
        $this->pvp = \round($newPrice, Producto::ROUND_DECIMALS);
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'articulostarifas';
    }
}
