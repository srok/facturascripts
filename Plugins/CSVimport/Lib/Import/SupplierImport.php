<?php
/**
 * Copyright (C) 2019-2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\Import;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\CuentaBancoProveedor;
use FacturaScripts\Dinamic\Model\Proveedor;

/**
 * Description of SupplierImport
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class SupplierImport extends CsvImporClass
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
        } elseif ($csv->titles[0] === 'cifnif' && $csv->titles[1] === 'codagente') {
            return static::TYPE_FACTURASCRIPTS;
        } elseif ($csv->titles[0] === 'codproveedor' && $csv->titles[1] === 'nombre') {
            return static::TYPE_FACTURASCRIPTS_2017;
        } elseif ($csv->titles[0] === 'Código' && \in_array('N.I.F.', $csv->titles)) {
            return static::TYPE_FACTUSOL;
        } elseif ($csv->titles[0] === 'Cód' && $csv->titles[1] === 'Nombre') {
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
        return 'suppliers';
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
        foreach ($csv->data as $row) {
            /// find supplier
            $supplier = new Proveedor();
            $code = $row['Código'] ?? $row['Cód'];
            if (empty($code) || ($supplier->loadFromCode($code) && $mode === static::INSERT_MODE)) {
                continue;
            }

            /// save new supplier
            $supplier->codproveedor = $code;
            $supplier->cifnif = $row['N.I.F.'];
            $supplier->nombre = substr($row['Nombre comercial'] ?? $row['Nombre'], 0, 100);
            $supplier->telefono1 = $row['Teléfono'];

            /// optional fields
            if (isset($row['E-mail'])) {
                $supplier->email = $row['E-mail'];
            }

            if (isset($row['Fax'])) {
                $supplier->fax = $row['Fax'];
            }

            if (isset($row['Móvil'])) {
                $supplier->telefono2 = $row['Móvil'];
            }

            if (isset($row['Nombre fiscal'])) {
                $supplier->razonsocial = $row['Nombre fiscal'];
            }

            if (!$supplier->save()) {
                continue;
            }

            $num++;
            foreach ($supplier->getAdresses() as $address) {
                $address->direccion = $row['Domicilio'] ?? $row['Dirección'];
                $address->codpostal = $row['Cód. Postal'] ?? $row['C.P.'];
                $address->ciudad = $row['Población'];
                $address->provincia = $row['Provincia'];
                $address->save();
                break;
            }

            if (isset($row['IBAN del banco']) && isset($row['SWIFT del banco'])) {
                static::saveBankAccount($supplier, $row['Banco'], $row['IBAN del banco'], $row['SWIFT del banco']);
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
        foreach ($csv->data as $row) {
            /// find supplier
            $supplier = new Proveedor();
            if (empty($row['codproveedor']) || ($supplier->loadFromCode($row['codproveedor']) && $mode === static::INSERT_MODE)) {
                continue;
            }

            /// save new supplier
            $supplier->loadFromData($row, ['codcliente', 'codpago', 'codserie', 'idcontacto']);
            if ($supplier->save()) {
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
        foreach ($csv->data as $row) {
            /// find supplier
            $supplier = new Proveedor();
            if (empty($row['codproveedor']) || ($supplier->loadFromCode($row['codproveedor']) && $mode === static::INSERT_MODE)) {
                continue;
            }

            /// save new supplier
            $supplier->loadFromData($row, ['codcliente', 'codpago', 'codserie', 'idcontacto']);
            if (!$supplier->save()) {
                continue;
            }

            $num++;
            foreach ($supplier->getAdresses() as $address) {
                $address->direccion = $row['direccion'];
                $address->codpostal = $row['codpostal'];
                $address->ciudad = $row['ciudad'];
                $address->provincia = $row['provincia'];
                $address->codpais = $row['pais'];
                $address->save();
                break;
            }

            if (!empty($row['iban']) || !empty($row['swift'])) {
                static::saveBankAccount($supplier, 'Bank', $row['iban'], $row['swift']);
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
     * @param Proveedor $supplier
     * @param string    $bankName
     * @param string    $iban
     * @param string    $swift
     */
    protected static function saveBankAccount($supplier, $bankName, $iban, $swift)
    {
        /// Find supplier bank accounts
        $bankAccountModel = new CuentaBancoProveedor();
        $where = [new DataBaseWhere('codproveedor', $supplier->codproveedor)];
        foreach ($bankAccountModel->all($where) as $bank) {
            $bank->descripcion = $bankName;
            $bank->iban = $iban;
            $bank->swift = $swift;
            $bank->save();
            return;
        }

        /// No previous bank accounts? Then create a new one
        $newBank = new CuentaBancoProveedor();
        $newBank->codproveedor = $supplier->codproveedor;
        $newBank->iban = $iban;
        $newBank->swift = $swift;
        $newBank->save();
    }
}
