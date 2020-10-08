<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Tarifa as ParentModel;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of Tarifa
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Tarifa extends ParentModel
{

    /**
     *
     * @var TarifaFamilia[]
     */
    private $families = [];

    /**
     * 
     * @param Variante $variant
     * @param Producto $product
     *
     * @return float
     */
    public function applyTo($variant, $product)
    {
        /// find TarifaProducto for this reference
        $tarifaProd = new TarifaProducto();
        $where = [
            new DataBaseWhere('codtarifa', $this->codtarifa),
            new DataBaseWhere('referencia', $variant->referencia)
        ];
        if ($tarifaProd->loadFromCode('', $where)) {
            return $tarifaProd->pvp;
        }

        /// find TarifaFamilia for this family
        if (!empty($product->codfamilia) && $this->loadFamily($product->codfamilia)) {
            return $this->families[$product->codfamilia]->apply($variant->coste, $variant->precio);
        }

        return parent::applyTo($variant, $product);
    }

    /**
     * 
     * @return string
     */
    public function explain()
    {
        return $this->aplicar === self::APPLY_COST ?
            $this->toolBox()->i18n()->trans('formula-cost-price-alt', ['%x%' => $this->valorx, '%y%' => $this->valory]) :
            $this->toolBox()->i18n()->trans('formula-sale-price-alt', ['%x%' => $this->valorx, '%y%' => $this->valory]);
    }

    /**
     * 
     * @param Variante $variant
     * @param Producto $product
     *
     * @return string
     */
    public function explainTo($variant, $product)
    {
        /// find TarifaProducto for this reference
        $tarifaProd = new TarifaProducto();
        $where = [
            new DataBaseWhere('codtarifa', $this->codtarifa),
            new DataBaseWhere('referencia', $variant->referencia)
        ];
        if ($tarifaProd->loadFromCode('', $where)) {
            return $this->toolBox()->i18n()->trans('fixed-price');
        }

        /// find TarifaFamilia for this family
        if (!empty($product->codfamilia) && $this->loadFamily($product->codfamilia)) {
            return $this->families[$product->codfamilia]->explain();
        }

        return $this->explain();
    }

    /**
     * 
     * @param string $codfamilia
     *
     * @return bool
     */
    private function loadFamily($codfamilia): bool
    {
        if (isset($this->families[$codfamilia])) {
            return null !== $this->families[$codfamilia]->primaryColumnValue();
        }

        $this->families[$codfamilia] = new TarifaFamilia();
        $where = [
            new DataBaseWhere('codfamilia', $codfamilia),
            new DataBaseWhere('codtarifa', $this->codtarifa)
        ];
        return $this->families[$codfamilia]->loadFromCode('', $where);
    }
}
