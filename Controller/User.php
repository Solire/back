<?php

namespace Solire\Back\Controller;

use Doctrine\DBAL\DriverManager;
use PDO;
use Solire\Lib\Hook;
use Solire\Lib\Mail;
use Solire\Lib\Registry;
use Solire\Lib\Session;
use Solire\Lib\Security\Util\SecureRandom;
use ZxcvbnPhp\Zxcvbn;

/**
 * Gestion du profile utilisateur
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class User extends Datatable
{

    public function start()
    {
        parent::start();
        $this->requireJs->addModule('modules/page/users');
    }

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
        if (count($errors) == 0 && strlen($_POST['new_password']) < 8) {
            $errors[] = 'Votre nouveau mot de passe doit contenir au moins 8 caractères';
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

            $oldPassHash = $this->db->query($query)->fetchColumn();
            $oldPassFilled = $_POST['old_password'];

            if (password_verify($oldPassFilled, $oldPassHash) === true) {

                $newPass = Session::prepareMdp($_POST['new_password']);

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
                'status' => 'success',
                'text' => 'Votre mot de passe a été mis à jour',
                'after' => array(
                    'modules/helper/noty',
                    'modules/render/aftersavepassword',
                )
            ];
        } else {
            $jsonResponse = [
                'status' => 'error',
                'text' => implode('<br />', $errors),
                'after' => array(
                    'modules/helper/noty',
                    'modules/render/aftersavepassword',
                )
            ];
        }

        echo json_encode($jsonResponse);
    }

    public function formAction()
    {
        $table = 'utilisateur';

        $doctrineConnection = DriverManager::getConnection([
            'pdo' => $this->db,
        ]);
        $doctrineConnection
            ->getDatabasePlatform()
            ->registerDoctrineTypeMapping('enum', 'string')
        ;

        $this->view->civilites = [
            'M.',
            'Mme',
        ];

        $this->view->niveaux = [
            'editeur',
            'administrateur',
            'super administrateur',
        ];

        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $query = 'SELECT * FROM utilisateur WHERE id = ' . $_GET['id'];
            $data = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);
        } else {
            $columns = $doctrineConnection->getSchemaManager()->listTableColumns($table);
            $data = [];
            foreach ($columns as $column) {
                $data[$column->getName()] = '';
            }
        }

        $this->view->data = $data;
    }

    /**
     *
     *
     *  @return void
     */
    public function sendmailAction()
    {
        if (!property_exists($this, 'from')) {
            $this->from = 'contact@solire.fr';
        }

        $this->view->enable(false);

        $idClient = intval($_GET['id']);
        $clientData = $this->db->query('
            SELECT utilisateur.*
            FROM utilisateur
            WHERE utilisateur.id = ' . $idClient)->fetch();
        $genPass = new SecureRandom();
        $password = $genPass->generate(
            8,
            SecureRandom::RANDOM_ALPHALOWER | SecureRandom::RANDOM_ALPHAUPPER | SecureRandom::RANDOM_NUMERIC
        );

        $mail = new Mail('utilisateur_identifiant');
        $mail->setMainUse();
        $mail->to = $clientData['email'];
        $mail->from = $this->from;
        $mail->subject = 'Informations de connexion à l\'outil d\'administration'
                . ' de votre site';

        $mail->urlAcces      = Registry::get('basehref') . 'back/';
        $mail->urlFrontAcces = Registry::get('basehref');

        $clientData['pass'] = $password;
        $mail->clientData = $clientData;
        $mail->send();

        $passwordCrypt = Session::prepareMdp($password);
        $values = array(
            'pass' => $passwordCrypt,
        );
        $this->db->update('utilisateur', $values, 'id = ' . $idClient);

        $hook = new Hook();
        $hook->setSubdirName('Back');
        $hook->dataRaw  = $clientData;
        $hook->exec('UserSendmail');

        if (isset($_POST['confirm']) && $_POST['confirm']) {
            echo json_encode([
                'status'         => 'success',
                'title'          => 'Confirmation d\'envoi de mail',
                'content'        => 'Un email a été envoyé avec un nouveau mot de passe',
                'closebuttontxt' => 'Fermer',
                'after'          => [
                    'modules/helper/message',
                ],
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'text' => 'Un email a été envoyé avec un nouveau mot de passe',
                'after' => [
                    'modules/helper/noty',
                ],
            ]);
        }


    }

}
