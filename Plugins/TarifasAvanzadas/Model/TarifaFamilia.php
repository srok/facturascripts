<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas\Model;

use FacturaScripts\Core\Model\Base;

/**
 * Description of TarifaFamilia
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class TarifaFamilia extends Base\ModelClass
{

    use Base\ModelTrait;

    const APPLY_COST = 'coste';
    const APPLY_PRICE = 'pvp';

    /**
     *
     * @var string
     */
    public $aplicar;

    /**
     *
     * @var string
     */
    public $codfamilia;

    /**
     *
     * @var string
     */
    public $codtarifa;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var bool
     */
    public $maxpvp;

    /**
     *
     * @var bool
     */
    public $mincoste;

    /**
     *
     * @var float
     */
    public $valorx;

    /**
     *
     * @var float
     */
    public $valory;

    /**
     * 
     * @param float $cost
     * @param float $price
     *
     * @return float
     */
    public function apply($cost, $price)
    {
        $finalPrice = 0.0;

        switch ($this->aplicar) {
            case self::APPLY_COST:
                $finalPrice += $cost + ($cost * $this->valorx / 100) + $this->valory;
                break;

            case self::APPLY_PRICE:
                $finalPrice += $price - ($price * $this->valorx / 100) - $this->valory;
                break;
        }

        if ($this->maxpvp && $finalPrice > $price) {
            return (float) $price;
        } elseif ($this->mincoste && $finalPrice < $cost) {
            return (float) $cost;
        }

        return $finalPrice > 0 ? $finalPrice : 0.0;
    }

    public function clear()
    {
        parent::clear();
        $this->aplicar = self::APPLY_PRICE;
        $this->maxpvp = false;
        $this->mincoste = false;
        $this->valorx = 0.0;
        $this->valory = 0.0;
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
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'tarifas_familias';
    }
}
