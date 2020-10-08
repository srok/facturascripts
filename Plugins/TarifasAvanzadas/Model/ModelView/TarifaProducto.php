<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Model\ModelView;

use FacturaScripts\Core\Model\Base\ModelView;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of TarifaProducto
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 * 
 * @property string $codbarras
 * @property string $codtarifa
 * @property float  $coste
 * @property string $descripcion
 * @property int    $idatributovalor1
 * @property int    $idatributovalor2
 * @property int    $idatributovalor3
 * @property int    $idatributovalor4
 * @property int    $idproducto
 * @property int    $idvariante
 * @property float  $margen
 * @property float  $precio
 * @property string $referencia
 * @property float  $stockfis
 */
class TarifaProducto extends ModelView
{

    /**
     *
     * @var Tarifa[]
     */
    private static $rates = [];

    /**
     * 
     * @param array $data
     */
    public function __construct($data = [])
    {
        parent::__construct($data);
        $this->setMasterModel(new Producto());

        /// needed dependency
        new Variante();
    }

    /**
     * 
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $name === 'preciotarifa' ? $this->priceInRate() : parent::__get($name);
    }

    /**
     * 
     * @return Tarifa
     */
    public function getRate()
    {
        if (isset(self::$rates[$this->codtarifa])) {
            return self::$rates[$this->codtarifa];
        }

        $rate = new Tarifa();
        if ($rate->loadFromCode($this->codtarifa)) {
            self::$rates[$this->codtarifa] = $rate;
        }

        return $rate;
    }

    /**
     * 
     * @return Variante
     */
    public function getVariant()
    {
        $variant = new Variante();
        $variant->codbarras = $this->codbarras;
        $variant->coste = $this->coste;
        $variant->idatributovalor1 = $this->idatributovalor1;
        $variant->idatributovalor2 = $this->idatributovalor2;
        $variant->idatributovalor3 = $this->idatributovalor3;
        $variant->idatributovalor4 = $this->idatributovalor4;
        $variant->idproducto = $this->idproducto;
        $variant->idvariante = $this->idvariante;
        $variant->margen = $this->margen;
        $variant->precio = $this->precio;
        $variant->referencia = $this->referencia;
        $variant->stockfis = $this->stockfis;

        return $variant;
    }

    /**
     * 
     * @return float
     */
    public function priceInRate()
    {
        $variant = $this->getVariant();
        $product = $variant->getProducto();
        return $this->getRate()->applyTo($variant, $product);
    }

    /**
     * 
     * @return mixed
     */
    public function primaryColumnValue()
    {
        return $this->idproducto;
    }

    /**
     * 
     * @return array
     */
    protected function getFields(): array
    {
        return [
            'codbarras' => 'variantes.codbarras',
            'codtarifa' => 'tarifas.codtarifa',
            'coste' => 'variantes.coste',
            'descripcion' => 'productos.descripcion',
            'idatributovalor1' => 'variantes.idatributovalor1',
            'idatributovalor2' => 'variantes.idatributovalor2',
            'idatributovalor3' => 'variantes.idatributovalor3',
            'idatributovalor4' => 'variantes.idatributovalor4',
            'idproducto' => 'productos.idproducto',
            'idvariante' => 'variantes.idvariante',
            'margen' => 'variantes.margen',
            'precio' => 'variantes.precio',
            'referencia' => 'variantes.referencia',
            'stockfis' => 'variantes.stockfis'
        ];
    }

    /**
     * 
     * @return string
     */
    protected function getSQLFrom(): string
    {
        return 'tarifas, variantes LEFT JOIN productos'
            . ' ON variantes.idproducto = productos.idproducto';
    }

    /**
     * 
     * @return array
     */
    protected function getTables(): array
    {
        return ['productos', 'tarifas', 'variantes'];
    }
}
