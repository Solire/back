<?php
/**
 * Formulaire de connexion à l'admin
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;
use Solire\Lib\Mail;

/**
 * Formulaire de connexion à l'admin
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Sign extends Main
{
    /**
     * Empêche la redirection en cas de non connexion
     *
     * @var boolean
     */
    protected $noRedirect = true;

    /**
     * Toujours exécuté avant chaque action
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        $this->view->unsetMain();
    }

    /**
     * Affichage du formulaire de connexion
     *
     * @return void
     */
    public function startAction()
    {
        $this->view->action = 'back/' . $this->appConfig->get('general', 'page-default');

        if ($this->utilisateur->isConnected()) {
            $this->simpleRedirect(
                'back/' . $this->appConfig->get('general', 'page-default'),
                true
            );
        }
    }

    /**
     * Action de demande de nouveau mot de passe
     *
     * @return void
     */
    public function asknewpasswordAction()
    {
        $this->view->emailSent = false;
        $this->view->error = false;

        if (isset($_POST['log']) && is_string($_POST['log'])) {
            $cle = $this->utilisateur->genKey($_POST['log']);

            if ($cle !== false) {
                $email = new Mail('newpassword');
                $email->url     = 'back/sign/newpassword.html?e=' . $_POST['log'] . '&amp;c=' . $cle;
                $email->to      = $_POST['log'];
                $email->from    = 'noreply@' . $_SERVER['SERVER_NAME'];
                $email->subject = 'Générer un nouveau mot de passe';
                $email->setMainUse();
                $email->send();

                $this->view->emailSent = true;
            } else {
                $this->view->error = true;
            }
        }
    }

    /**
     * Génération d'un nouveau mot de passe
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
            $mdp = $this->utilisateur->newPassword($_GET['c'], $_GET['e']);
            if ($mdp !== false) {
                $this->view->mdp = $mdp;
            }
        }
    }

    /**
     * Déconnexion de l'utilisateur
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
