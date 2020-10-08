<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Fabricante;

/**
 * Description of ManufacturerImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ManufacturerImport extends CsvImporClass
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
        } elseif ($csv->titles[0] === 'codfabricante' && $csv->titles[1] === 'nombre') {
            return static::TYPE_FACTURASCRIPTS_2017;
        }

        return static::TYPE_NONE;
    }

    /**
     * 
     * @return string
     */
    protected static function getProfile()
    {
        return 'manufacturers';
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
            $manufacturer = new Fabricante();
            $where = [new DataBaseWhere('codfabricante', $utils->noHtml($row['codfabricante']))];
            if (empty($row['codfabricante']) || ($manufacturer->loadFromCode('', $where) && $mode === static::INSERT_MODE)) {
                /// manufacturer found
                continue;
            }

            $manufacturer->loadFromData($row);
            if ($manufacturer->save()) {
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

            default:
                static::toolBox()->i18nLog()->error('file-not-supported-advanced');
                return 0;
        }
    }
}
