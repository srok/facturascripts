<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\CuentaBancoCliente;

/**
 * Description of CustomersProfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CustomersProfile extends ProfileClass
{

    /**
     * 
     * @return array
     */
    public function getDataFields(): array
    {
        return [
            'clientes.codcliente' => ['title' => 'customer-code'],
            'clientes.nombre' => ['title' => 'name'],
            'clientes.razonsocial' => ['title' => 'business-name'],
            'clientes.cifnif' => ['title' => 'cifnif'],
            'clientes.telefono1' => ['title' => 'phone'],
            'clientes.telefono2' => ['title' => 'phone2'],
            'clientes.fax' => ['title' => 'fax'],
            'clientes.email' => ['title' => 'email'],
            'clientes.web' => ['title' => 'web'],
            'clientes.codsubcuenta' => ['title' => 'subaccount'],
            'contactos.direccion' => ['title' => 'address'],
            'contactos.apartado' => ['title' => 'post-office-box'],
            'contactos.codpostal' => ['title' => 'zip-code'],
            'contactos.ciudad' => ['title' => 'city'],
            'contactos.provincia' => ['title' => 'province'],
            'cuentasbcocli.descripcion' => ['title' => 'bank'],
            'cuentasbcocli.iban' => ['title' => 'iban'],
            'cuentasbcocli.swift' => ['title' => 'swift']
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
        $where = [];
        if (isset($item['clientes.codcliente']) && !empty($item['clientes.codcliente'])) {
            $where[] = new DataBaseWhere('codcliente', $item['clientes.codcliente']);
        } elseif (isset($item['clientes.nombre']) && !empty($item['clientes.nombre'])) {
            $where[] = new DataBaseWhere('nombre', $item['clientes.nombre']);
        } elseif (isset($item['clientes.cifnif']) && !empty($item['clientes.cifnif'])) {
            $where[] = new DataBaseWhere('cifnif', $item['clientes.cifnif']);
        }

        if (empty($where)) {
            return false;
        }

        $customer = new Cliente();
        if ($customer->loadFromCode('', $where) && $this->mode === static::INSERT_MODE) {
            return false;
        }

        $this->setModelValues($customer, $item, 'clientes.');
        if ($customer->save()) {
            $this->importAddress($customer, $item);
            $this->importBankAccount($customer, $item);
            return true;
        }

        return false;
    }

    /**
     * 
     * @param Cliente $customer
     * @param array   $item
     *
     * @return bool
     */
    private function importAddress($customer, $item): bool
    {
        foreach ($customer->getAdresses() as $address) {
            $this->setModelValues($address, $item, 'contactos.');
            return $address->save();
        }

        return false;
    }

    /**
     * 
     * @param Cliente $customer
     * @param array   $item
     *
     * @return bool
     */
    private function importBankAccount($customer, $item): bool
    {
        $description = $item['cuentasbcocli.descripcion'] ?? '';
        $iban = $item['cuentasbcocli.iban'] ?? '';
        $swift = $item['cuentasbcocli.swift'] ?? '';
        if (empty($iban) && empty($swift)) {
            return true;
        }

        $bankAccountModel = new CuentaBancoCliente();
        $where = [new DataBaseWhere('codcliente', $customer->codcliente)];
        foreach ($bankAccountModel->all($where) as $bank) {
            $bank->descripcion = $description;
            $bank->iban = $iban;
            $bank->swift = $swift;
            return $bank->save();
        }

        /// new bank account
        $newBankAccount = new CuentaBancoCliente();
        $newBankAccount->codcliente = $customer->codcliente;
        $newBankAccount->descripcion = $description;
        $newBankAccount->iban = $iban;
        $newBankAccount->swift = $swift;
        return $newBankAccount->save();
    }
}
