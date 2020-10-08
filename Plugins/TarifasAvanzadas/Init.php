<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\TarifasAvanzadas;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\InitClass;
use FacturaScripts\Plugins\TarifasAvanzadas\Model\TarifaFamilia;

/**
 * Description of Init
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class Init extends InitClass
{

    public function init()
    {
        $this->loadExtension(new Extension\Controller\EditCliente());
        $this->loadExtension(new Extension\Controller\EditGrupoClientes());
        $this->loadExtension(new Extension\Controller\EditProducto());
        $this->loadExtension(new Extension\Controller\EditTarifa());
        $this->loadExtension(new Extension\Controller\ListTarifa());
        $this->loadExtension(new Extension\Model\Base\SalesDocument());
    }

    public function update()
    {
        $this->migrate2017();
    }

    private function migrate2017()
    {
        $database = new DataBase();
        if (false === $database->tableExists('tarifasav')) {
            return;
        }

        $sql = "SELECT * FROM tarifasav WHERE madre IS NOT NULL;";
        foreach ($database->select($sql) as $row) {
            $tarFam = new TarifaFamilia();
            $where = [
                new DataBaseWhere('codfamilia', $row['codfamilia']),
                new DataBaseWhere('codtarifa', $row['madre'])
            ];
            if ($tarFam->loadFromCode('', $where)) {
                continue;
            }

            $tarFam->codfamilia = $row['codfamilia'];
            $tarFam->codtarifa = $row['madre'];

            if ($this->toolBox()->utils()->str2bool($row['margen'])) {
                $tarFam->aplicar = TarifaFamilia::APPLY_COST;
                $tarFam->valorx = (float) $row['incporcentual'];
                $tarFam->valory = (float) $row['inclineal'];
                $tarFam->save();
                continue;
            }

            $tarFam->aplicar = TarifaFamilia::APPLY_PRICE;
            $tarFam->valorx = 0 - (float) $row['incporcentual'];
            $tarFam->valory = 0 - (float) $row['inclineal'];
            $tarFam->save();
        }
    }
}
