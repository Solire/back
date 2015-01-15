<?php

namespace Solire\Back\Datatable;

/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Redirection_mobile extends \Solire\Lib\Datatable\Datatable
{
/** @todo Changer le nom de Redirection_mobile pour qu'il respect la notation camel */
    public function start() {
        parent::start();
        $suf = $this->db->query("SELECT suf FROM version WHERE id = " . BACK_ID_VERSION)->fetchColumn();
        $this->config["table"]["title"] .= ' <img src="public/default/back/img/flags/all/16/' . strtolower($suf) . '.png" />';
    }

}

