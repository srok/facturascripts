<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Atributo;
use FacturaScripts\Dinamic\Model\AtributoValor;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\Producto;
use FacturaScripts\Dinamic\Model\Variante;

/**
 * Description of ProductImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class ProductImport extends CsvImporClass
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
        } elseif ($csv->titles[0] === 'actualizado' && $csv->titles[1] === 'bloqueado') {
            return static::TYPE_FACTURASCRIPTS;
        } elseif ($csv->titles[0] === 'referencia' && $csv->titles[1] === 'codfamilia') {
            return static::TYPE_FACTURASCRIPTS_2017;
        } elseif ($csv->titles[0] === 'Código' && $csv->titles[1] === 'Descripción') {
            return static::TYPE_FACTUSOL;
        }

        return static::TYPE_NONE;
    }

    /**
     * 
     * @param string $attName
     * @param string $attValue
     *
     * @return int
     */
    protected static function getIdatributo($attName, $attValue)
    {
        $atributo = new Atributo();
        if (!$atributo->loadFromCode($attName)) {
            $atributo->codatributo = $attName;
            $atributo->nombre = $attName;
            $atributo->save();
        }

        $atValor = new AtributoValor();
        $where = [
            new DataBaseWhere('codatributo', $attName),
            new DataBaseWhere('valor', $attValue)
        ];
        if (!$atValor->loadFromCode('', $where)) {
            $atValor->codatributo = $attName;
            $atValor->valor = $attValue;
            $atValor->save();
        }

        return $atValor->primaryColumnValue();
    }

    /**
     * 
     * @param string $prefix
     *
     * @return string
     */
    protected static function getNewReference($prefix)
    {
        $variant = new Variante();
        for ($num = 1; $num < 100; $num++) {
            $ref = $prefix . $num;
            $where = [new DataBaseWhere('referencia', $ref)];
            if (!$variant->loadFromCode('', $where)) {
                return $ref;
            }
        }

        return $prefix . \mt_rand(101, 9999);
    }

    /**
     * 
     * @return string
     */
    protected static function getProfile()
    {
        return 'products';
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
        $lastID = 0;
        $utils = static::toolBox()->utils();
        foreach ($csv->data as $row) {
            /// Is this a variant?
            if (!empty($lastID) && empty($row['Código']) && empty($row['Descripción']) && !empty($row['Referencia'])) {
                static::saveFactusolVariant($lastID, $row);
                continue;
            }

            $product = new Producto();
            $ref = empty($row['Referencia']) ? $row['Código'] : $row['Referencia'];
            $where = [new DataBaseWhere('referencia', $utils->noHtml($ref))];
            if (empty($ref) || ($product->loadFromCode('', $where) && $mode === static::INSERT_MODE)) {
                /// product found
                continue;
            }

            $product->descripcion = $row['Descripción'];
            $product->precio = static::getFloat($row['Venta']);
            $product->referencia = $ref;
            $product->stockfis = (int) $row['Stock()'];
            if (!$product->save()) {
                continue;
            }

            $num++;
            $lastID = $product->primaryColumnValue();
            foreach ($product->getVariants() as $variant) {
                $variant->coste = static::getFloat($row['Costo']);
                $variant->save();
                break;
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
    protected static function importCSVfs(string $filePath, string $mode): int
    {
        $csv = static::getCsv($filePath);

        $num = 0;
        $utils = static::toolBox()->utils();
        foreach ($csv->data as $row) {
            $product = new Producto();
            $where = [new DataBaseWhere('referencia', $utils->noHtml($row['referencia']))];
            if (empty($row['referencia']) || ($product->loadFromCode('', $where) && $mode === static::INSERT_MODE)) {
                /// product found
                continue;
            }

            $product->loadFromData($row, ['codfabricante', 'codfamilia', 'idproducto']);
            if ($product->save()) {
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

        $taxModel = new Impuesto();
        $taxes = $taxModel->all();

        $num = 0;
        $utils = static::toolBox()->utils();
        foreach ($csv->data as $row) {
            $product = new Producto();
            $where = [new DataBaseWhere('referencia', $utils->noHtml($row['referencia']))];
            if (empty($row['referencia']) || ($product->loadFromCode('', $where) && $mode === static::INSERT_MODE)) {
                /// product found
                continue;
            }

            $product->loadFromData($row, ['codfabricante', 'codfamilia']);

            /// set tax
            foreach ($taxes as $tax) {
                if ($utils->floatcmp(static::getFloat($row['iva']), $tax->iva)) {
                    $product->codimpuesto = $tax->codimpuesto;
                    break;
                }
            }

            if ($product->save()) {
                $num++;
                foreach ($product->getVariants() as $variant) {
                    $variant->codbarras = $row['codbarras'];
                    $variant->coste = static::getFloat($row['coste']);
                    $variant->precio = static::getFloat($row['pvp']);
                    $variant->save();
                    break;
                }
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
            case static::TYPE_FACTUSOL:
                return static::importCSVfactusol($filePath, $mode);

            case static::TYPE_FACTURASCRIPTS:
                return static::importCSVfs($filePath, $mode);

            case static::TYPE_FACTURASCRIPTS_2017:
                return static::importCSVfs2017($filePath, $mode);

            default:
                static::toolBox()->i18nLog()->error('file-not-supported-advanced');
                return 0;
        }
    }

    /**
     * 
     * @param int   $idproduct
     * @param array $line
     */
    protected static function saveFactusolVariant($idproduct, $line)
    {
        if ($line['Referencia'] == 'Talla' && $line['Prov.'] == 'Color') {
            return;
        }

        $product = new Producto();
        if (!$product->loadFromCode($idproduct)) {
            return;
        }

        $variant = new Variante();
        $variant->coste = static::getFloat($line['Costo']);
        $variant->idatributovalor1 = static::getIdatributo('Talla', $line['Referencia']);
        $variant->idatributovalor2 = static::getIdatributo('Color', $line['Prov.']);
        $variant->idproducto = $idproduct;
        $variant->precio = static::getFloat($line['Venta']);
        $variant->referencia = static::getNewReference($product->referencia . '-');
        $variant->stockfis = static::getFloat($line['Stock()']);
        $variant->save();
    }
}
