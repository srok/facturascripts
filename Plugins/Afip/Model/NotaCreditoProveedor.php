<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\Afip\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\BusinessDocSubType;
use FacturaScripts\Dinamic\Lib\BusinessDocTypeOperation;
use FacturaScripts\Dinamic\Model\LineaNotaCreditoProveedor as DinLineaNotaCreditoProveedor;
use FacturaScripts\Dinamic\Model\LiquidacionComision as DinLiquidacionComision;
use FacturaScripts\Dinamic\Model\ReciboProveedor as DinReciboProveedor;
use FacturaScripts\Core\Model\Base;

/**
 *  NC Proveedor.
 *
 * @author Srok <srok@srok.com.ar>
 */
class NotaCreditoProveedor extends Base\PurchaseDocument
{

    use Base\ModelTrait;
   // use Base\InvoiceTrait;

    /**
     * Code business documen type operation
     *
     * @var string
     */
    public $codoperaciondoc;

    /**
     * Code business Documen sub type
     *
     * @var string
     */
    public $codsubtipodoc;

    /**
     *
     * @var int
     */
    public $idliquidacion;

    /**
     * This function is called when creating the model's table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert
     * default values.
     *
     * @return string
     */
    public function install()
    {
        /// needed dependencies
        new DinLiquidacionComision();

        return parent::install();
    }

    /**
     * Reset the values of all model properties.
     */
    public function clear()
    {
        parent::clear();
        $this->codoperaciondoc = BusinessDocTypeOperation::defaultValue();
        $this->codsubtipodoc = 'NC';
        $this->pagada = false;
    }

    /**
     * Returns the lines associated with the invoice.
     *
     * @return DinLineaNotaCreditoProveedor[]
     */
    public function getLines()
    {
        $lineaModel = new DinLineaNotaCreditoProveedor();
        $where = [new DataBaseWhere('idnotacredito', $this->idnotacredito)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];
        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     * @param array $exclude
     *
     * @return DinLineaNotaCreditoProveedor
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idnotacredito'])
    {
        $newLine = new DinLineaNotaCreditoProveedor();
        $newLine->idnotacredito = $this->idnotacredito;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->loadFromData($data, $exclude);
        return $newLine;
    }

    /**
     * Returns all invoice's receipts.
     *
     * @return DinReciboProveedor[]
     */
    public function getReceipts()
    {
        $receipt = new DinReciboProveedor();
        $where = [new DataBaseWhere('idnotacredito', $this->idnotacredito)];
        return $receipt->all($where, ['numero' => 'ASC', 'idrecibo' => 'ASC'], 0, 0);
    }

     /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
     public static function primaryColumn()
     {
        return 'idnotacredito';
    }


    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'notascreditoprov';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {

        if( isset($_POST['idestado']) && $_POST['idestado'] == 29){

            

            if(  !$this->codpv ){
              die('El punto de venta es obligatorio');

              return false;

          }
          if(  !$this->numproveedor ){
              die('El número de la nota de crédito es obligatorio');

              return false;

          }
      }

      return parent::test();
  }
}
