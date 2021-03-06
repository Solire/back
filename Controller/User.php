<?php

namespace Solire\Back\Controller;

use Doctrine\DBAL\DriverManager;
use Exception;
use PDO;
use Solire\Lib\Mail;
use Solire\Lib\Registry;
use Solire\Lib\Session;
use ZxcvbnPhp\Zxcvbn;

/**
 * Gestion du profile utilisateur.
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
     * Affichage du formulaire d'édition du profile.
     *
     * @return void
     */
    public function startAction()
    {
        $this->view->breadCrumbs[] = [
            'title' => 'Mon profil',
            'url' => '',
        ];
    }

    /**
     * Change le mot de passe de l'utilisateur.
     *
     * @return void
     */
    public function changePasswordAction()
    {
        $this->view->enable(false);

        $errors = [];

        $response = [
            'status' => false,
        ];

        /* Nouveau mot de passe et sa confirmation différent */
        if ($_POST['new_password'] != $_POST['new_password_c']) {
            $errors[] = 'Le nouveau mot de passe et sa confirmation sont différents';
        }

        /* Test longueur password */
        if (count($errors) == 0 && strlen($_POST['new_password']) < 8) {
            $errors[] = 'Votre nouveau mot de passe doit contenir au moins 8 caractères';
        }

        /* Test password complexity */
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
                'after' => [
                    'modules/helper/noty',
                    'modules/render/aftersavepassword',
                ],
            ];
        } else {
            $jsonResponse = [
                'status' => 'error',
                'text' => implode('<br />', $errors),
                'after' => [
                    'modules/helper/noty',
                    'modules/render/aftersavepassword',
                ],
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
     *  @return void
     */
    public function sendmailAction()
    {
        $this->view->enable(false);

        /**
         * Toujours le même message, même si l'adresse n'est pas en bdd,
         * pour des raisons de sécurité
         */
        $jsonResponse = [
            'status' => 'success',
            'title' => 'Confirmation d\'envoi de mail',
            'content' => 'Un email a été envoyé pour que l\'utilisateur génère un mot de passe',
            'closebuttontxt' => 'Fermer',
            'after' => [
                'modules/helper/message',
            ],
        ];

        $idClient = intval($_GET['id']);
        $query = 'SELECT u.* '
               . 'FROM utilisateur u '
               . 'WHERE u.id = ' . $idClient . ' '
        ;
        $clientData = $this->db->query($query)->fetch(PDO::FETCH_ASSOC);

        if (empty($clientData)) {
            $jsonResponse = [
                'status' => 'error',
                'title' => 'Une erreur est survenue',
                'content' => 'Identifiant d\'utilisateur inconnu',
                'closebuttontxt' => 'Fermer',
                'after' => [
                    'modules/helper/message',
                ],
            ];
        } elseif (empty($clientData['actif'])) {
            $jsonResponse = [
                'status' => 'error',
                'title' => 'Utilisateur non actif',
                'content' => 'L\'utilisateur n\'est pas encore actif. '
                           . 'Merci de le rendre actif.',
                'after' => [
                    'modules/helper/message',
                ],
            ];
        } else {
            $cle = $this->utilisateur->genKey($clientData['email']);

            if ($cle !== false) {
                $from = Registry::get('envconfig')->get('email', 'noreply');

                if (empty($from)) {
                    throw new Exception(
                        'Email d\'expéditeur non défini. '
                        . 'A définir dans le fichier de config "email.noreply"'
                    );
                }

                $email = new Mail('createpassword');
                $email->url = 'back/sign/newpassword.html?e=' . $clientData['email'] . '&amp;c=' . $cle;
                $email->to = $clientData['email'];
                $email->from = $from;
                $email->subject = 'Générer votre mot de passe';
                $email->setMainUse();
                $email->send();

                $this->userLogger->addInfo(
                    'Envoi de mail pour info de connexion',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                    ]
                );
            } else {
                $this->userLogger->addError(
                    'Envoi de mail pour info de connexion échoué',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                        'error' => 'Erreur lors de la génération de la clé',
                    ]
                );
            }
        }

        exit(json_encode($jsonResponse));
    }
}
