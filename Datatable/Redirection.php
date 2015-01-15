<?php

namespace Solire\Back\Datatable;

/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Redirection extends \Solire\Lib\Datatable\Datatable
{

    public function start() {
        parent::start();
        $api = $this->db->query("SELECT name FROM gab_api WHERE id = " . BACK_ID_API)->fetchColumn();
        $this->config["table"]["title"] .= ' <img width="16" src="public/default/back/img/api/' . strtolower($api) . '.png" />';
        $suf = $this->db->query("SELECT suf FROM version WHERE id = " . BACK_ID_VERSION)->fetchColumn();
        $this->config["table"]["title"] .= ' <img src="public/default/back/img/flags/all/16/' . strtolower($suf) . '.png" />';
    }

}

