<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport;

use FacturaScripts\Core\Base\InitClass;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Init extends InitClass
{

    public function init()
    {
        $this->loadExtension(new Extension\Controller\ListAttachedFile());
        $this->loadExtension(new Extension\Controller\ListCliente());
        $this->loadExtension(new Extension\Controller\ListFabricante());
        $this->loadExtension(new Extension\Controller\ListFacturaCliente());
        $this->loadExtension(new Extension\Controller\ListFacturaProveedor());
        $this->loadExtension(new Extension\Controller\ListFamilia());
        $this->loadExtension(new Extension\Controller\ListProducto());
        $this->loadExtension(new Extension\Controller\ListProveedor());
    }

    public function update()
    {
        ;
    }
}
