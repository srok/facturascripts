<?php
/**
 * Copyright (C) 2020 Carlos Garcia Gomez <carlos@facturascripts.com>
 */
namespace FacturaScripts\Plugins\CSVimport\Lib\ImportProfile;

use Exception;
use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\ModelClass;
use ParseCsv\Csv;

/**
 * Description of ProfileClass
 *
 * @author Carlos Garcia Gomez <carlos@facturascripts.com>
 */
abstract class ProfileClass
{

    const INSERT_MODE = 'insert';
    const MAX_VALUE_LEN = 30;
    const UPDATE_MODE = 'update';

    /**
     *
     * @var Csv
     */
    protected $csv;

    /**
     *
     * @var string
     */
    protected $mode;

    /**
     *
     * @var array
     */
    protected $options;

    abstract public function getDataFields();

    abstract protected function importItem(array $item);

    /**
     * 
     * @param string $path
     * @param array  $options
     * @param string $mode
     * @param bool   $noutf8file
     */
    public function __construct(string $path, array $options, string $mode, $noutf8file)
    {
        $this->csv = new Csv();
        if ($noutf8file) {
            $this->csv->encoding(null, 'UTF-8');
        }
        $this->csv->auto(\FS_FOLDER . DIRECTORY_SEPARATOR . $path);
        $this->mode = $mode;
        $this->options = $options;
    }

    /**
     * 
     * @return array
     */
    public function getRows(): array
    {
        $rows = [];
        foreach ($this->csv->titles as $title) {
            if (empty($title)) {
                continue;
            }

            $rows[] = [
                'title' => $title,
                'value1' => '',
                'value2' => '',
                'value3' => '',
                'use' => ''
            ];
        }

        $this->setValues($rows);
        return $rows;
    }

    /**
     * 
     * @return int
     */
    public function import(): int
    {
        $return = 0;

        /// get transformations
        $transformations = [];
        foreach ($this->getRows() as $row) {
            if (!empty($row['use'])) {
                $transformations[$row['use']] = $row['title'];
            }
        }

        /// start transaction
        $dataBase = new DataBase();
        $dataBase->beginTransaction();

        try {
            foreach ($this->csv->data as $line) {
                $item = [];
                foreach ($transformations as $key => $field) {
                    $item[$key] = $line[$field];
                }

                if ($this->importItem($item)) {
                    $return++;
                }
            }

            /// confirm data
            $dataBase->commit();
        } catch (Exception $exp) {
            $this->toolBox()->log()->error($exp->getMessage());
        } finally {
            if ($dataBase->inTransaction()) {
                $dataBase->rollback();
            }
        }

        return $return;
    }

    /**
     * 
     * @param string $text
     *
     * @return string
     */
    protected function getDate($text)
    {
        /// is an european date? (01/01/20)
        $parts = \explode('/', $text);
        if (!empty($parts) && \count($parts) === 3 && \strlen($parts[2]) === 2) {
            $newText = (int) $parts[2] > 70 ? $parts[0] . '-' . $parts[1] . '-19' . $parts[2] : $parts[0] . '-' . $parts[1] . '-20' . $parts[2];
            return \date(ModelClass::DATE_STYLE, \strtotime($newText));
        }

        return \date(ModelClass::DATE_STYLE, \strtotime($text));
    }

    /**
     * 
     * @param string $text
     *
     * @return string
     */
    protected function getValidEmail($text)
    {
        if (empty($text)) {
            return '';
        }

        foreach (\explode(' ', $text) as $part) {
            if (\filter_var($part, \FILTER_VALIDATE_EMAIL)) {
                return $part;
            }
        }

        return '';
    }

    /**
     * 
     * @param ModelClass $model
     * @param array      $values
     * @param string     $prefix
     */
    protected function setModelValues(&$model, $values, $prefix)
    {
        foreach ($model->getModelFields() as $key => $field) {
            if (!isset($values[$prefix . $key])) {
                continue;
            }

            switch ($field['type']) {
                case 'date':
                    $model->{$key} = $this->getDate($values[$prefix . $key]);
                    break;

                case 'double':
                case 'double precision':
                case 'float':
                    $model->{$key} = (float) \str_replace(',', '.', $values[$prefix . $key]);
                    break;

                default:
                    $model->{$key} = $values[$prefix . $key];
            }

            switch ($field['name']) {
                case 'email':
                    $model->{$key} = $this->getValidEmail($model->{$key});
                    break;
            }
        }
    }

    /**
     * 
     * @param array $rows
     */
    protected function setValues(array &$rows)
    {
        foreach ($this->csv->data as $num0 => $line) {
            $num = 1 + $num0;
            foreach ($rows as $key => $row) {
                if (!isset($row['value' . $num])) {
                    break;
                }

                $value = $line[$row['title']];
                if (\is_string($value) && \strlen($value) > static::MAX_VALUE_LEN) {
                    $rows[$key]['value' . $num] = \substr($value, 0, static::MAX_VALUE_LEN) . '...';
                    continue;
                }

                $rows[$key]['value' . $num] = $value;
            }
        }

        foreach (\array_keys($rows) as $key) {
            if (isset($this->options['field' . $key])) {
                $rows[$key]['use'] = $this->options['field' . $key];
            }
        }
    }

    /**
     * 
     * @return ToolBox
     */
    protected function toolBox()
    {
        return new ToolBox();
    }
}
