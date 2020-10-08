<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\AttachedFile;

/**
 * Description of CSVfile
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
class CSVfile extends Base\ModelClass
{

    use Base\ModelTrait;

    const INSERT_MODE = 'insert';
    const UPDATE_MODE = 'update';

    /**
     *
     * @var string
     */
    public $date;

    /**
     *
     * @var int
     */
    public $id;

    /**
     *
     * @var int
     */
    public $idfile;

    /**
     *
     * @var string
     */
    public $mode;

    /**
     *
     * @var string
     */
    public $name;

    /**
     *
     * @var bool
     */
    public $noutf8file;

    /**
     *
     * @var string
     */
    public $options;

    /**
     *
     * @var string
     */
    public $path;

    /**
     *
     * @var string
     */
    public $profile;

    /**
     *
     * @var int
     */
    public $size;

    public function clear()
    {
        parent::clear();
        $this->date = \date(self::DATE_STYLE);
        $this->mode = self::INSERT_MODE;
        $this->noutf8file = false;
        $this->profile = 'customers';
        $this->size = 0;
    }

    /**
     * Remove the model data from the database.
     *
     * @return bool
     */
    public function delete()
    {
        $attachedFile = $this->getAttachedFile();
        if ($attachedFile->delete() && parent::delete()) {
            return true;
        }

        return false;
    }

    /**
     * Return the attached file to this build.
     *
     * @return AttachedFile
     */
    public function getAttachedFile()
    {
        $attachedFile = new AttachedFile();
        if ($attachedFile->loadFromCode($this->idfile)) {
            return $attachedFile;
        }

        return null;
    }

    public function getProfile()
    {
        $options = empty($this->options) ? [] : \json_decode($this->options, true);
        $profileClass = $this->getProfileClass();
        $profile = new $profileClass($this->path, $options, $this->mode, $this->noutf8file);
        return $profile;
    }

    /**
     * 
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'id';
    }

    /**
     * 
     * @param array $values
     *
     * @return bool
     */
    public function setOptions(array $values): bool
    {
        $this->options = \json_encode($values);
        return $this->save();
    }

    /**
     * 
     * @return string
     */
    public static function tableName(): string
    {
        return 'csv_files';
    }

    /**
     * Returns True if there is no errors on properties values.
     *
     * @return bool
     */
    public function test()
    {
        if (empty($this->idfile)) {
            /// is a csv file?
            $filePath = FS_FOLDER . DIRECTORY_SEPARATOR . 'MyFiles' . DIRECTORY_SEPARATOR . $this->path;
            $mime = \mime_content_type($filePath);
            if (!\in_array($mime, ['text/csv', 'text/plain', 'text/x-Algol68', 'application/octet-stream'])) {
                $this->toolBox()->i18nLog()->warning('only-csv-files');
                \unlink($filePath);
                return false;
            }

            $attachedFile = new AttachedFile();
            $attachedFile->path = $this->path;
            if (!$attachedFile->save()) {
                \unlink($filePath);
                return false;
            }

            $this->idfile = $attachedFile->idfile;
            $this->name = $attachedFile->filename;
            $this->path = $attachedFile->path;
            $this->size = $attachedFile->size;
        }

        return parent::test();
    }

    /**
     * 
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'ListAttachedFile?activetab=List'): string
    {
        return parent::url($type, $list);
    }

    /**
     * 
     * @return string
     */
    protected function getProfileClass()
    {
        switch ($this->profile) {
            case 'customer-invoices':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\CustomerInvoicesProfile';

            case 'families':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\FamiliesProfile';

            case 'manufacturers':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\ManufacturersProfile';

            case 'products':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\ProductsProfile';

            case 'supplier-invoices':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\SupplierInvoicesProfile';

            case 'supplier-products':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\SupplierProductsProfile';

            case 'suppliers':
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\SuppliersProfile';

            default:
                return '\FacturaScripts\Dinamic\Lib\ImportProfile\CustomersProfile';
        }
    }
}
