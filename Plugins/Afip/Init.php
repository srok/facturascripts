<?php
namespace FacturaScripts\Plugins\Afip{
use FacturaScripts\Core\Base\InitClass;

class Init extends InitClass
{

    public function init()
    {
        $this->loadExtension(new Extension\Model\Cliente());
    }
    public function update()
    {
        
    }

}
}

namespace{
            if ( ! function_exists('mpr'))
            {
                function mpr($d, $echo = TRUE)
                {
                    if($echo)
                    {
                        echo '<pre>'.print_r($d, true).'</pre>';
                    }
                    else
                    {
                        return '<pre>'.print_r($d, true).'</pre>';
                    }
                }
            }

            if(!function_exists('changeDateFormat'))

            {

                function changeDateFormat($format = 'd-m-Y', $originalDate)

                {

                    return date($format, strtotime($originalDate));

                }

            }

            if ( ! function_exists('mprd'))
            {
                function mprd($d)
                {
                    mpr($d);
                    die;
                }
            }

            if ( ! function_exists('mvr'))
            {
                function mvr($d)
                {
                    echo '<pre>'.var_dump($d, true).'</pre>';
                }
            }

            if ( ! function_exists('mvrd'))
            {
                function mvrd($d)
                {
                    mvr($d);
                    die;
                }
            }
}
