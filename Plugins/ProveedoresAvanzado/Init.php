<?php

namespace FacturaScripts\Plugins\ProveedoresAvanzado;

use FacturaScripts\Core\Base\InitClass;

/**
 * Description of Init
 *
 * @author Srok
 */

class Init extends InitClass
{

    public function init()
    {

        $this->loadExtension(new Extension\Controller\EditProveedor());
    }

    public function update()
    {

    }


}
