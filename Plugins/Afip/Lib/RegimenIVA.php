<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2017 Carlos Garcia Gomez <carlos@facturascripts.com>
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
 * 
 * 
 * 
 */
namespace FacturaScripts\Plugins\Afip\Lib;

use FacturaScripts\Core\Lib\RegimenIVA as Iva;

/**
 * Responsabilidades frente al IVA para Argentina
 *
 * @author Srok Desarrollos <srok@srok.com.ar>
 */
class RegimenIVA extends Iva
{
    
 const TAX_SYSTEM_RI = 'IVA Responsable Inscripto';
 const TAX_SYSTEM_NR = 'IVA no Responsable';
 const TAX_SYSTEM_E = 'IVA exento';
 const TAX_SYSTEM_RM = 'Responsable Monotributo';
 const TAX_SYSTEM_MS = 'Monotributista Social';
 const TAX_SYSTEM_CF = 'A CONSUMIDOR FINAL';

 const TAX_SYSTEM_EXEMPT = 'Exento';
 const TAX_SYSTEM_GENERAL = 'General';
 const TAX_SYSTEM_SURCHARGE = 'Recargo';

    /**
     * Returns all the available options
     *
     * @return array
     */
    public static function all()
    {
        return [
            self::TAX_SYSTEM_EXEMPT => 'Exento',
            self::TAX_SYSTEM_GENERAL => 'General',
            self::TAX_SYSTEM_SURCHARGE => 'Recargo de equivalencia',
            
            self::TAX_SYSTEM_RI =>'IVA Responsable Inscripto' ,
            self::TAX_SYSTEM_NR =>'IVA no Responsable' ,
            self::TAX_SYSTEM_E =>'IVA exento',
            self::TAX_SYSTEM_RM =>'Responsable Monotributo' ,
            self::TAX_SYSTEM_MS =>'Monotributista Social' ,
            self::TAX_SYSTEM_CF =>'A CONSUMIDOR FINAL' 
        ];
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public static function defaultValue()
    {
        return self::TAX_SYSTEM_GENERAL;
    }
    /*
    Devuelve la letra del comprobante, según los sujetos que intervienen
     */
    public static function defaultSerie($vendedor, $cliente)
    {
        if ($vendedor == self::TAX_SYSTEM_GENERAL){
            if ($cliente == self::TAX_SYSTEM_GENERAL) {
                return 'A';
            }
            return 'B';
        }
        return 'C';
    }
}
