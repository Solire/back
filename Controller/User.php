<?php
/**
 * Gestion du profile utilisateur
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use ZxcvbnPhp\Zxcvbn;
use Solire\Lib\FrontController;

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

        /** Test password complexity */
        $zxcvbn = new Zxcvbn();
        $strength = $zxcvbn->passwordStrength($_POST['new_password'], [$this->utilisateur->login]);

        if (count($errors) == 0 && $strength['score'] < 2) {
            $errors[] = 'Votre nouveau mot de passe n\'est pas assez sécurisé';
        }

        //Si aucune erreur on essaie de modifier le mot de passe
        if (count($errors) == 0) {
            $query = 'SELECT pass '
                   . 'FROM utilisateur '
                   . 'WHERE id = ' . $this->utilisateur->id . ' ';

            $oldPassHash   = $this->db->query($query)->fetchColumn();
            $oldPassFilled = $_POST['old_password'];

            if (password_verify($oldPassFilled, $oldPassHash) === true) {

                $newPass = $this->utilisateur->prepareMdp($_POST['new_password']);

                $query = 'UPDATE utilisateur SET '
                    . ' pass = ' . $this->db->quote($newPass) . ' '
                    . 'WHERE `id` = ' . $this->utilisateur->id . ' ';

                if ($this->db->exec($query)) {
                    $response['status'] = true;
                }
            } else {
                $errors[] = 'Mot de passe actuel incorrect';
            }
        }

        if ($response['status']) {
            $this->utilisateur->disconnect();
            $jsonResponse = [
                'status'      => 'success',
                'text'        => 'Votre mot de passe a été mis à jour',
                'after'       => array(
                    'modules/helper/noty',
                    'modules/render/aftersavepassword',
                )
            ];
        } else {
            $jsonResponse = [
                'status'      => 'error',
                'text'        => implode('<br />', $errors),
                'after'       => array(
                    'modules/helper/noty',
                    'modules/render/aftersavepassword',
                )
            ];
        }

        echo json_encode($jsonResponse);
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
