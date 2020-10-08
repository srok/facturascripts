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
use FacturaScripts\Dinamic\Model\LineaNotaCreditoCliente as DinLineaNotaCreditoCliente;
use FacturaScripts\Dinamic\Model\LiquidacionComision as DinLiquidacionComision;
use FacturaScripts\Dinamic\Model\ReciboCliente as DinReciboCliente;
use FacturaScripts\Core\Model\Base;

/**
 * Customer's NC.
 *
 * @author Srok <srok@srok.com.ar>
 */
class NotaCreditoCliente extends Base\SalesDocument
{

    use Base\ModelTrait;
    use Base\InvoiceTrait;

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
     * @return DinLineaNotaCreditoCliente[]
     */
    public function getLines()
    {
        $lineaModel = new DinLineaNotaCreditoCliente();
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
     * @return DinLineaNotaCreditoCliente
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idnotacredito'])
    {
        $newLine = new DinLineaNotaCreditoCliente();
        $newLine->idnotacredito = $this->idnotacredito;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = $this->getStatus()->actualizastock;
        $newLine->loadFromData($data, $exclude);
        return $newLine;
    }

    /**
     * Returns all invoice's receipts.
     *
     * @return DinReciboCliente[]
     */
    public function getReceipts()
    {
        $receipt = new DinReciboCliente();
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
        return 'notascreditocli';
    }

    /**
     * 
     * @return bool
     */
    public function test()
    {
        if (!parent::test()) {
            return false;
        }

        if ($this->codserie != $this->previousData['codserie']) {
            /// prevent check date if serie is changed
            return true;
        }

        /// prevent form using old dates
        $numColumn = \strtolower(\FS_DB_TYPE) == 'postgresql' ? 'CAST(numero as integer)' : 'CAST(numero as unsigned)';
        $whereOld = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codserie', $this->codserie),
            new DataBaseWhere($numColumn, (int) $this->numero, '<')
        ];
        foreach ($this->all($whereOld, ['fecha' => 'DESC'], 0, 1) as $old) {
            if (\strtotime($old->fecha) > \strtotime($this->fecha)) {
                $this->toolBox()->i18nLog()->error('invalid-date-there-are-invoices-after', ['%date%' => $this->fecha]);
                return false;
            }
        }

        /// prevent the use of too new dates
        $whereNew = [
            new DataBaseWhere('codejercicio', $this->codejercicio),
            new DataBaseWhere('codserie', $this->codserie),
            new DataBaseWhere($numColumn, (int) $this->numero, '>')
        ];
        foreach ($this->all($whereNew, ['fecha' => 'ASC'], 0, 1) as $old) {
            if (\strtotime($old->fecha) < \strtotime($this->fecha)) {
                $this->toolBox()->i18nLog()->error('invalid-date-there-are-invoices-before', ['%date%' => $this->fecha]);
                return false;
            }
        }

        return true;
    }
}
