<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Familia;

/**
 * Description of FamilyImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class FamilyImport extends CsvImporClass
{

    /**
     * 
     * @param string $filePath
     *
     * @return int
     */
    protected static function getFileType(string $filePath): int
    {
        $csv = static::getCsv($filePath);

        if (\count($csv->titles) < 2) {
            return static::TYPE_NONE;
        } elseif ($csv->titles[0] === 'codfamilia' && $csv->titles[1] === 'descripcion') {
            return static::TYPE_FACTURASCRIPTS_2017;
        } elseif ($csv->titles[0] === 'Código' && $csv->titles[1] === 'Descripción') {
            return static::TYPE_FACTUSOL;
        }

        return static::TYPE_NONE;
    }

    /**
     * 
     * @return string
     */
    protected static function getProfile()
    {
        return 'families';
    }

    /**
     * 
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importCSVfactusol(string $filePath, string $mode): int
    {
        $csv = static::getCsv($filePath);

        $num = 0;
        $utils = static::toolBox()->utils();
        foreach ($csv->data as $row) {
            $family = new Familia();
            $code = $row['Código'];
            $where = [new DataBaseWhere('codfamilia', $utils->noHtml($code))];
            if (empty($code) || ($family->loadFromCode('', $where) && $mode === static::INSERT_MODE)) {
                /// family found
                continue;
            }

            /// save new family
            $family->codfamilia = $code;
            $family->descripcion = $row['Descripción'];
            if ($family->save()) {
                $num++;
            }
        }

        return $num;
    }

    /**
     * 
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importCSVfs2017(string $filePath, string $mode): int
    {
        $csv = static::getCsv($filePath);

        $num = 0;
        $utils = static::toolBox()->utils();
        foreach ($csv->data as $row) {
            $family = new Familia();
            $where = [new DataBaseWhere('codfamilia', $utils->noHtml($row['codfamilia']))];
            if (empty($row['codfamilia']) || ($family->loadFromCode('', $where) && $mode === static::INSERT_MODE)) {
                /// family found
                continue;
            }

            $family->loadFromData($row);
            if ($family->save()) {
                $num++;
            }
        }

        return $num;
    }

    /**
     * 
     * @param int    $type
     * @param string $filePath
     * @param string $mode
     *
     * @return int
     */
    protected static function importType($type, $filePath, $mode): int
    {
        switch ($type) {
            case static::TYPE_FACTURASCRIPTS_2017:
                return static::importCSVfs2017($filePath, $mode);

            case static::TYPE_FACTUSOL:
                return static::importCSVfactusol($filePath, $mode);

            default:
                static::toolBox()->i18nLog()->error('file-not-supported-advanced');
                return 0;
        }
    }
}
