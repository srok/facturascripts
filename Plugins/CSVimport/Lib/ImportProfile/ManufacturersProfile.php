<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use FacturaScripts\Dinamic\Model\Fabricante;

/**
 * Description of ManufacturersProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ManufacturersProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [
            'codfabricante' => ['title' => 'code'],
            'nombre' => ['title' => 'name']
        ];
    }

    /**
     * 
     * @param array $item
     *
     * @return bool
     */
    protected function importItem(array $item): bool
    {
        if (!isset($item['codfabricante']) || empty($item['codfabricante'])) {
            return false;
        }

        $manufacturer = new Fabricante();
        if ($manufacturer->loadFromCode($item['codfabricante']) && $this->mode === static::INSERT_MODE) {
            return false;
        }

        $this->setModelValues($manufacturer, $item, '');
        return $manufacturer->save();
    }
}
