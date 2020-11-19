<?php
namespace FacturaScripts\Plugins\Afip\Model;

use FacturaScripts\Core\Model\Base;

class PuntosVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $codpv;
    public $description;
    public $tipo;
    

    public static function primaryColumn()
    {
        return 'idpv';
    }

    public static function tableName()
    {
        return 'puntosventa';
    }
}
