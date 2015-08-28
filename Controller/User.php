<?php
/**
 * Gestion du profile utilisateur
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Solire\Lib\FrontController;
use Solire\Lib\Datatable\Datatable;

/**
 * Gestion du profile utilisateur
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class User extends Main
{

    /**
     * Affichage du formulaire d'édition du profile
     *
     * @return void
     */
    public function startAction()
    {
        $this->javascript->addLibrary('back/js/formgabarit.js');

        $this->view->breadCrumbs[] = array(
            'title' => 'Mon profil',
            'url' => '',
        );
    }

    /**
     * Change le mot de passe de l'utilisateur
     *
     * @return void
     */
    public function changePasswordAction()
    {
        $this->view->enable(false);

        $errors = array();

        $response = array(
            'status' => false
        );

        /** Nouveau mot de passe et sa confirmation différent */
        if ($_POST['new_password'] != $_POST['new_password_c']) {
            $errors[] = 'Le nouveau mot de passe et sa confirmation sont différents';
        }


         /** Test longueur password */
        if (count($errors) == 0 && strlen($_POST['new_password']) < 6) {
            $errors[] = 'Votre nouveau mot de passe doit contenir au moins 6 caractères';
        }

        //Si aucune erreur on essaie de modifier le mot de passe
        if (count($errors) == 0) {

            $query = 'SELECT pass '
                   . 'FROM utilisateur '
                   . 'WHERE id = ' . $this->utilisateur->id . ' ';
            $oldPass = $this->db->query($query)->fetchColumn();

            $oldSaisi = $this->utilisateur->prepareMdp($_POST['old_password']);


            $newPass = $this->utilisateur->prepareMdp($_POST['new_password']);

            $query = 'UPDATE utilisateur SET '
                   . ' pass = ' . $this->db->quote($newPass) . ' '
                   . 'WHERE `id` = ' . $this->utilisateur->id . ' ';

            if ($oldPass == $oldSaisi) {
                $response['status'] = true;
                $this->db->exec($query);
            } else {
                $errors[] = 'Mot de passe actuel incorrect';
            }
        }

        if ($response['status']) {
            $response['status'] = 'success';
            $response['message'] = 'Votre mot de passe a été mis à jour';
            $response['javascript'] = 'window.location.reload()';
        } else {
            $response['message'] = implode('<br />', $errors);
        }

        echo json_encode($response);
    }

    /**
     * Liste des utilisateurs
     *
     * @return void
     */
    public function listeAction()
    {
        $configPath = FrontController::search(
            'config/datatable/utilisateur.cfg.php'
        );

        if (!$configPath) {
            $this->pageNotFound();
        }

        $datatableClassName = 'Back\\Datatable\\Utilisateur';
        $datatableClassName = FrontController::searchClass(
            $datatableClassName
        );

        if ($datatableClassName === false) {
            $datatable = new Datatable(
                $_GET,
                $configPath,
                $this->db,
                'back/css/datatable/',
                'back/js/datatable/',
                'back/img/datatable/'
            );
        } else {
            $datatable = new $datatableClassName(
                $_GET,
                $configPath,
                $this->db,
                'back/css/datatable/',
                'back/js/datatable/',
                'back/img/datatable/'
            );
        }

        $datatable->setUtilisateur($this->utilisateur);
        $datatable->start();
        $datatable->setDefaultNbItems(
            $this->appConfig->get('board', 'nb-content-default')
        );

        if (isset($_GET['json']) || (isset($_GET['nomain'])
            && $_GET['nomain'] == 1)
        ) {
            echo $datatable->display();
            exit();
        }

        $this->view->datatableRender = $datatable->display();
    }
}
