<?php
namespace FacturaScripts\Plugins\Afip\Model;

use FacturaScripts\Core\Model\SecuenciaDocumento as SecuenciaDocumentoCore;

class SecuenciaDocumento extends SecuenciaDocumentoCore{

	 public function clear()
    {
      	parent::clear();
       	$this->inicio = 1;
        $this->longnumero = 8;
        $this->numero = 1;
        $this->patron = '{SERIE}-{PVENTA}-{0NUM}';
        $this->usarhuecos = false;


    }
 

}