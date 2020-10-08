<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2019 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Model\Base;


/**
 * Line of a customer invoice.
 *
 * @author Srok Desarrollos <srok@srok.com.ar>
 */
class LineaNotaCreditoCliente extends Base\SalesDocumentLine
{

    use Base\ModelTrait;

    /**
     * Invoice ID of this line.
     *
     * @var int
     */
    public $idnotacredito;

    /**
     * 
     * @return string
     */
    public function documentColumn()
    {
        return 'idnotacredito';
    }

    /**
     * 
     * @return FacturaCliente
     */
    public function getDocument()
    {
        $nc = new NotaCreditoCliente();
        $nc->loadFromCode($this->idnotacredito);
        return $nc;
    }

    /**
     * 
     * @return string
     */
    public function install()
    {
        /// needed dependency
        new NotaCreditoCliente();

        return parent::install();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName()
    {
        return 'lineasnotascreditocli';
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List')
    {
        if (null !== $this->idnotacredito) {
            return 'EditNotaCreditoCliente?code=' . $this->idnotacredito;
        }

        return parent::url($type, $list);
    }
}
