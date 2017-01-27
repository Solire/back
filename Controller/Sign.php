<?php
/**
 * Formulaire de connexion à l'admin.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Solire\Lib\Mail;
use Solire\Lib\Session;
use ZxcvbnPhp\Zxcvbn;

/**
 * Formulaire de connexion à l'admin.
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Sign extends Main
{
    /**
     * Empêche la redirection en cas de non connexion.
     *
     * @var bool
     */
    protected $noRedirect = true;

    /**
     * Toujours exécuté avant chaque action.
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        $this->view->unsetMain();
    }

    /**
     * Affichage du formulaire de connexion.
     *
     * @return void
     */
    public function startAction()
    {
        $this->seo->setTitle('Connexion');
        $this->view->action = 'back/' . $this->appConfig->get('general', 'page-default');

        if ($this->utilisateur->isConnected()) {
            $this->simpleRedirect(
                'back/' . $this->appConfig->get('general', 'page-default'),
                true
            );
        }
    }

    /**
     * Action de demande de nouveau mot de passe.
     *
     * @return void
     */
    public function asknewpasswordAction()
    {
        $this->view->enable(false);

        // Toujours le même message, même si l'adresse n'est pas en bdd, pour des raisons de sécurité
        $jsonResponse = [
            'status' => 'success',
            'text' => 'Pour obtenir votre nouveau mot de passe, veuillez vérifier votre compte email, '
                . 'un lien vous a été envoyé.',
            'after' => [
                'modules/helper/noty',
                'modules/render/afterforgotpassword',
            ],
        ];

        if (isset($_POST['log']) && is_string($_POST['log'])) {
            $cle = $this->utilisateur->genKey($_POST['log']);

            if ($cle !== false) {
                $from = Registry::get('envconfig')->get('email', 'noreply');

                if (empty($from)) {
                    throw new Exception('Email d\'expéditeur non défini. A définir dans le fichier de config "email.noreply"');
                }

                $email = new Mail('newpassword');
                $email->url = 'back/sign/newpassword.html?e=' . $_POST['log'] . '&amp;c=' . $cle;
                $email->to = $_POST['log'];
                $email->from = $from;
                $email->subject = 'Générer un nouveau mot de passe';
                $email->setMainUse();
                $email->send();

                $this->userLogger->addInfo(
                    'Demande de nouveau mot de passe',
                    [
                        'user' => [
                            'id' => $this->utilisateur->id,
                            'login' => $this->utilisateur->login,
                        ],
                    ]
                );
            } else {
                $this->userLogger->addError(
                    'Demande de nouveau mot de passe échoué',
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

    /**
     * Génération d'un nouveau mot de passe.
     *
     * @throws \Exception
     *
     * @return void
     */
    public function newpasswordAction()
    {
        if (isset($_GET['e'])
            && is_string($_GET['e'])
            && isset($_GET['c'])
            && is_string($_GET['c'])
        ) {
            $validKey = $this->utilisateur->checkKey($_GET['c'], $_GET['e']);
            if (!$validKey) {
                $this->simpleRedirect('back/', true);
            } else {
                $this->view->email = $_GET['e'];
                $this->view->cle = $_GET['c'];
            }
        } else {
            $this->simpleRedirect('back/', true);
        }
    }

    public function newpasswordsaveAction()
    {
        $this->view->enable(false);

        $response = [
            'status' => false,
        ];

        $errors = [];

        if (isset($_POST['email'])
            && is_string($_POST['email'])
            && isset($_POST['cle'])
            && is_string($_POST['cle'])
        ) {
            $validKey = $this->utilisateur->checkKey($_POST['cle'], $_POST['email']);
            $login = filter_var($_POST['email'], FILTER_SANITIZE_STRING);
            if (!$validKey) {
                $this->userLogger->addError(
                    'Nouveau mot de passe échoué (invalide clé) / sauvegarde',
                    [
                        'user' => [
                            'email' => $login,
                        ],
                    ]
                );

                throw new \Exception('Une erreur est survenu.');
            } else {
                /* Nouveau mot de passe et sa confirmation différent */
                if ($_POST['new_password'] != $_POST['new_password_c']) {
                    $errors[] = 'Le nouveau mot de passe et sa confirmation sont différents';
                }

                /* Test longueur password */
                if (count($errors) == 0 && strlen($_POST['new_password']) < 6) {
                    $errors[] = 'Votre nouveau mot de passe doit contenir au moins 6 caractères';
                }

                /* Test password complexity */
                $zxcvbn = new Zxcvbn();
                $strength = $zxcvbn->passwordStrength($_POST['new_password'], [$login]);

                if (count($errors) == 0 && $strength['score'] < 2) {
                    $errors[] = 'Votre nouveau mot de passe n\'est pas assez sécurisé';
                }

                if (count($errors) == 0) {
                    $newPass = Session::prepareMdp($_POST['new_password']);

                    $query = 'UPDATE utilisateur SET '
                        . ' pass = ' . $this->db->quote($newPass) . ' '
                        . ', cle = NULL'
                        . ', date_cle = NULL'
                        . ' WHERE `email` = ' . $this->db->quote($login);

                    if ($this->db->exec($query)) {
                        $response['status'] = true;
                    }
                }
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

    /**
     * Déconnexion de l'utilisateur.
     *
     * @return void
     */
    public function signoutAction()
    {
        $this->view->enable(false);

        $this->utilisateur->disconnect();
        $this->simpleRedirect('back/sign/start.html', true);
    }
}
