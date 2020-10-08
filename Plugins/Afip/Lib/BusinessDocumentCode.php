<?php

namespace FacturaScripts\Plugins\Afip\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\BusinessDocumentCode as BusinessDocumentCodeCore;
use FacturaScripts\Dinamic\Model\SecuenciaDocumento;

/**
 * Description of BusinessDocumentCode
 *
 * @author Carlos García Gómez      <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul    <juanjoseprieto88@gmail.com>
 */
class BusinessDocumentCode extends BusinessDocumentCodeCore{


	 /**
     * Generates a new identifier for humans from a document.
     *
     * @param BusinessDocument $document
     * @param bool             $newNumber
     */
     public static function getNewCode(&$document, $newNumber = true, $altpattern = false)
     {
        


        $sequence = static::getSequence($document);

        if ($newNumber) {
            $document->numero = static::getNewNumber($sequence, $document);
        }
        $patron = $sequence->patron;
        
        if($altpattern){
            $patron = $sequence->patron2;
        }

        $docTags = [
            '{EJE}' => $document->codejercicio,
            '{EJE2}' => \substr($document->codejercicio, -2),
            '{SERIE}' => $document->codserie,
            '{0SERIE}' => \str_pad($document->codserie, 2, '0', \STR_PAD_LEFT),
            '{NUM}' => $document->numero,
            '{PVENTA}' => \str_pad($document->codpv, 4, '0', \STR_PAD_LEFT),
            '{0NUM}' => \str_pad($document->numero, $sequence->longnumero, '0', \STR_PAD_LEFT)
        ];

        if(static::isSalesDocument( $document )){
            $docTags['{NUM2}'] = $document->numero2;
            $docTags['{0NUM2}'] = \str_pad($document->numero2, $sequence->longnumero, '0', \STR_PAD_LEFT);

        }else{ //PurchaseDocument
            $docTags['{NUMPROV}'] = $document->numproveedor;
            $docTags['{0NUMPROV}'] = \str_pad($document->numproveedor, $sequence->longnumero, '0', \STR_PAD_LEFT);
        }

        $document->codigo = \strtr($patron, $docTags);
        
    }

     /**
     * Finds sequence for this document.
     * 
     * @param BusinessDocument $document
     *
     * @return SecuenciaDocumento
     */
     protected static function getSequence(&$document)
     {

        $selectedSequence = new SecuenciaDocumento();

        /// find sequence for this document and serie
        $sequence = new SecuenciaDocumento();
        $where = [
            new DataBaseWhere('codserie', $document->codserie),
            new DataBaseWhere('idempresa', $document->idempresa),
        
            new DataBaseWhere('tipodoc', $document->modelClassName()),
        ];

        if( self::isSalesDocument( $document ) ){
             $where[] = new DataBaseWhere('puntoventa', $document->codpv);
        }

        foreach ($sequence->all($where) as $seq) {
            if (empty($seq->codejercicio)) {
                /// sequence for all exercises
                $selectedSequence = $seq;
            } elseif ($seq->codejercicio == $document->codejercicio) {
                /// sequence for this exercise
                return $seq;
            }
        }

        if(!$selectedSequence->idsecuencia){
            die('ERROR: Document without a sequence.');
        }
        // /// sequence not found? Then create
        // if (!$selectedSequence->exists()) {
        //     $selectedSequence->codejercicio = $document->codejercicio;
        //     $selectedSequence->codserie = $document->codserie;
        //     $selectedSequence->idempresa = $document->idempresa;
        //     $selectedSequence->tipodoc = $document->modelClassName();
        //     $selectedSequence->usarhuecos = ('FacturaCliente' === $document->modelClassName());
        //     $selectedSequence->save();
        // }

        return $selectedSequence;
    }

    public static function isSalesDocument( &$document ){

        return is_subclass_of($document, '\FacturaScripts\Core\Model\Base\SalesDocument');
    }

    // public static function getSequencePublic(&$document){
    //     return self::getSequence($document);
    // }
}
