<?php

namespace Solire\Back\Datatable;

/**
 * Description of BoardDatatable
 *
 * @author shin
 */
class Translate extends \Solire\Lib\Datatable\Datatable
{

    public function start() {
        parent::start();
        $api = $this->db->query("SELECT name FROM gab_api WHERE id = " . BACK_ID_API)->fetchColumn();
        $this->config["table"]["title"] .= ' <img width="16" src="app/back/img/back/api/' . strtolower($api) . '.png" />';
        $suf = $this->db->query("SELECT suf FROM version WHERE id = " . BACK_ID_VERSION)->fetchColumn();
        $this->config["table"]["title"] .= ' <img src="app/back/img/flags/all/16/' . strtolower($suf) . '.png" />';
    }

}

