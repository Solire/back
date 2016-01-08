<?php

/**
 * Contrôleur qui permet de gérer les redirections
 *
 * @author  Stéphane Monnot <smonnot@solire.fr>
 * @license CC by-nc        http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Doctrine\DBAL\DriverManager;
use PDO;
use Solire\Back\Controller\Datatable;

/**
 * Contrôleur qui permet de gérer les redirections
 *
 * @author  Stéphane Monnot <smonnot@solire.fr>
 * @license CC by-nc        http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Redirection extends Datatable
{
    public function start()
    {
        parent::start();
        $this->requireJs->addModule('modules/page/resources');
        $this->requireJs->addModule('modules/render/resources');
    }

    /**
     * Action permettant d'afficher le formulaire d'ajout de redirection
     *
     * @return void
     */
    public function formAction()
    {
        $table = 'redirection';

        $doctrineConnection = DriverManager::getConnection([
            'pdo' => $this->db,
        ]);

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $query = 'SELECT * FROM ' . $table . ' WHERE id = ' . $_GET['id'];
            $data  = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        } else {
            $columns = $doctrineConnection->getSchemaManager()->listTableColumns($table);
            $data    = [];
            foreach ($columns as $column) {
                $data[$column->getName()] = '';
            }
        }

        $this->view->data = $data;
    }

    public function deleteAction()
    {
        $this->view->enable(false);

        $query = 'DELETE FROM redirection WHERE id = ' . $_POST['id'];
        $this->db->exec($query);

        echo json_encode([
            'status' => 'success',
            'after'  => [
                'modules/helper/noty',
                'modules/render/resources',
            ],
            'text'   => 'La ressource a bien été supprimée',
            'debug'  => $_POST,
        ]);
    }

}
