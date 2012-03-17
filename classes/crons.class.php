<?php
/**
* Performs all the scheduled tasks / cron jobs
*
* @version 1.0
* @since 1.0
* @access public
* @extends  classIlluminz   Base controller class of framework
*
*/
class crons extends classIlluminz {

    /**
     * Expire property listing and having no output
     *
     * CMD: wget http://www.acapelladevelopment.com/apartments/crons/expirePropertyList > /dev/null
     * Scheduled Time: 00:00 (Mid night) Every day
     */
    function expirePropertyList(){
        $where = "status != " . PropertyStatusConsts::EXPIRED;
        $where .= " AND expiry_date > 0 AND DATEDIFF(expiry_date, '".date('Y-m-d')."') = 0";
        $this->Database->mysqlUpdate('properties', array('status'), array(PropertyStatusConsts::EXPIRED), $where);

        exit;
    }
}
?>