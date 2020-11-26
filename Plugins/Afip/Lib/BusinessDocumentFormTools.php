<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2018-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
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
namespace FacturaScripts\Plugins\Afip\Lib;

use FacturaScripts\Core\Base\Utils;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Model\Base\BusinessDocumentLine;
use FacturaScripts\Core\Lib\BusinessDocumentFormTools as CoreBusinessDocumentFormTools;
use FacturaScripts\Core\Base\ToolBox;

/**
 * Description of BusinessDocumentFormTools
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class BusinessDocumentFormTools extends CoreBusinessDocumentFormTools
{

 public function validateLines($lines){

    $result = ['status' => false , 'error' => 'unknown-line-error'];

    if( count( $lines ) < 1){
      $result['error'] = $this->toolBox()->i18n()->trans('no-lines');
      return $result;
  }

  foreach ($lines as $key => $line) {
    if( $line['cantidad'] < 1 ){
        $result['error'] = $this->toolBox()->i18n()->trans('negative-zero-lines').' #'.($key + 1);
        return $result;
    }
    if( $line['pvptotal'] <= 0 ){
        $result['error'] = $this->toolBox()->i18n()->trans('negative-zero-pvptotal').' #'.($key + 1);
        return $result;
    }
}
$result['status'] = true;
return $result;

}


/**
     *
     * @return ToolBox
     */
private function toolBox()
{
    return new ToolBox();
}
}
