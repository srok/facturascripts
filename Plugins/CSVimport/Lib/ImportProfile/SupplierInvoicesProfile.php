<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

/**
 * Description of SupplierInvoicesProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SupplierInvoicesProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [];
    }

    /**
     * 
     * @param array $item
     *
     * @return bool
     */
    protected function importItem(array $item): bool
    {
        return false;
    }
}
