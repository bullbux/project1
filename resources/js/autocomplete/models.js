<?php
    $str = '';
    $Content->models->getAllModels();
    if($Content->models->models){
        $count = count($Content->models->models) - 1;
        $index = 0;       
        foreach($Content->models->models as $model){
            if($count == $index)
                $delm = '';
            else
                 $delm = ', ';
            $str .= "'" . $model . "'" . $delm;
        }
    }
?>

var model = [<?php echo $str; ?>];