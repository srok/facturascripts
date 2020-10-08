<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use FacturaScripts\Dinamic\Model\Familia;

/**
 * Description of FamiliesProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class FamiliesProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [
            'codfamilia' => ['title' => 'code'],
            'descripcion' => ['title' => 'description']
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
        if (!isset($item['codfamilia']) || empty($item['codfamilia'])) {
            return false;
        }

        $family = new Familia();
        if ($family->loadFromCode($item['codfamilia']) && $this->mode === static::INSERT_MODE) {
            return false;
        }

        $this->setModelValues($family, $item, '');
        return $family->save();
    }
}
