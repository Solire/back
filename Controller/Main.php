<?php
/**
 * Controleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

/**
 * Controleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Main extends \Solire\Lib\Controller
{

    /**
     * Session en cours
     *
     * @var \Solire\Lib\Session
     */
    protected $utilisateur;

    /**
     * Api en cours
     *
     * @var array
     */
    protected $api;

    /**
     * Manager des requetes liées aux pages
     *
     * @var \Solire\Lib\Model\GabaritManager
     */
    protected $gabaritManager = null;

    /**
     * Manager fichiers
     *
     * @var \Solire\Lib\Model\FileManager
     */
    protected $fileManager = null;

    /**
     * Manager fichiers
     *
     * @var \Solire\Lib\Log
     */
    protected $log = null;

    /**
     * Always execute before other method in controller
     *
     * @return void
     * @hook back/ start Ajouter facilement des traitements au start du back
     */
    public function start()
    {
        parent::start();

        /**
         * Système de log en BDD
         */
        $this->log = new \Solire\Lib\Log($this->db, '', 0, 'back_log');

        /**
         * Utilisateur connecté ?
         */
        $this->utilisateur = new \Solire\Lib\Session('back');

        if (isset($_POST['log']) && isset($_POST['pwd'])
            && !empty($_POST['log']) && !empty($_POST['pwd'])
        ) {
            try {
                $this->utilisateur->connect(
                    $_POST['log'],
                    $_POST['pwd']
                );
            } catch (\Exception $exc) {
                $log = 'Identifiant : ' . $_POST['log'];
                $this->log->logThis('Connexion échouée', 0, $log);
                throw $exc;
            }

            $this->log->logThis('Connexion réussie', $this->utilisateur->id);

            $message = 'Connexion réussie, vous allez être redirigé';

            exit(json_encode(array('success' => true, 'message' => $message)));
        }

        if (!$this->utilisateur->isConnected()
            && (!isset($this->noRedirect) || $this->noRedirect === false)
        ) {
            $this->simpleRedirect('back/sign/start.html', true);
        }

        /**
         * Si l'utilisateur a juste le droit de prévisualisation du site
         *  = possibilité de voir le site sans tenir compte de la visibilité
         * Alors On le redirige vers le front
         */
//        if ($this->utilisateur->get('niveau') == 'voyeur') {
//            if ($_GET['controller'] . '/' . $_GET['action'] != 'back/sign/signout') {
//                $this->simpleRedirect('../', true);
//            }
//        }

        if (isset($_COOKIE['api'])) {
            $nameApi = $_COOKIE['api'];
        } else {
            $nameApi = 'main';
        }

        $query = 'SELECT id '
               . 'FROM gab_api '
               . 'WHERE name = ' . $this->db->quote($nameApi) . ' ';

        $idApi = $this->db->query($query)->fetch(\PDO::FETCH_COLUMN);

        if (intval($idApi) == 0) {
            $idApi = 1;
        }

        $query = 'SELECT * '
               . 'FROM gab_api '
               . 'WHERE id = ' . $idApi . ' ';
        $this->api = $this->db->query($query)->fetch(\PDO::FETCH_ASSOC);

        $query = 'SELECT * '
               . 'FROM gab_api ';
        $this->apis = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        if (!defined('BACK_ID_API')) {
            define('BACK_ID_API', $this->api['id']);
        }

        $this->javascript->addLibrary('back/js/jquery/jquery-1.8.0.min.js');
        $this->javascript->addLibrary('back/js/jquery/jquery-ui-1.8.23.custom.min.js');
        $this->javascript->addLibrary('back/js/main.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.cookie.js');
        $this->javascript->addLibrary('back/js/jquery/sticky.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.livequery.min.js');

        $this->javascript->addLibrary('back/js/jquery/jquery.stickyPanel.min.js');

        $this->javascript->addLibrary('back/js/newstyle.js');
        $this->css->addLibrary('back/css/jquery-ui-1.8.7.custom.css');

        $this->css->addLibrary('back/css/jquery-ui/custom-theme/jquery-ui-1.8.22.custom.css');

        /** Inclusion Bootstrap twitter */
        $this->javascript->addLibrary('back/js/bootstrap/bootstrap.min.js');
        $this->css->addLibrary('back/css/bootstrap/bootstrap.min.css');
        $this->css->addLibrary('back/css/bootstrap/bootstrap-responsive.min.css');

        /** font-awesome */
        $this->css->addLibrary('back/css/font-awesome/css/font-awesome.min.css');

        $this->css->addLibrary('back/css/newstyle-1.3.css');
        $this->css->addLibrary('back/css/sticky.css');

        $this->view->site = \Solire\Lib\Registry::get('project-name');

        if (isset($_GET['controller'])) {
            $this->view->controller = $_GET['controller'];
        } else {
            $this->view->controller = '';
        }

        if (isset($_GET['action'])) {
            $this->view->action = $_GET['action'];
        } else {
            $this->view->action = '';
        }

        $className = \Solire\Lib\FrontController::searchClass('Model\GabaritManager');
        if ($className !== false) {
            $this->gabaritManager = new $className();
        } else {
            $this->gabaritManager = new \Solire\Lib\Model\GabaritManager();
        }

        $this->fileManager = new \Solire\Lib\Model\FileManager();

        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.`id_api` = ' . $this->api['id'] . ' ';

        $this->versions = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );

        if (isset($_GET['id_version'])) {
            $id_version = $_GET['id_version'];
            $url = '/' . \Solire\Lib\Registry::get('baseroot');
            setcookie('id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_POST['id_version'])) {
            $id_version = $_POST['id_version'];
            $url = '/' . \Solire\Lib\Registry::get('baseroot');
            setcookie('back_id_version', $id_version, 0, $url);
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $id_version);
            }
        } elseif (isset($_COOKIE['back_id_version'])
            && isset($this->versions[$_COOKIE['back_id_version']])
        ) {
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', $_COOKIE['back_id_version']);
            }
        } else {
            if (!defined('BACK_ID_VERSION')) {
                define('BACK_ID_VERSION', 1);
            }
        }

        if (isset($_POST['log']) && isset($_POST['pwd'])
            && ($_POST['log'] == '' || $_POST['pwd'] == '')
        ) {
            $retour = array(
                'success' => false,
                'message' => 'Veuillez renseigner l\'identifiant et le mot de passe'
            );
            exit(json_encode($retour));
        }

        $this->view->utilisateur = $this->utilisateur;
        $this->view->apis = $this->apis;
        $this->view->api = $this->api;
        $this->view->javascript = $this->javascript;
        $this->view->css = $this->css;
        $this->view->mainVersions = $this->versions;
        $query = 'SELECT `version`.id, `version`.* '
               . 'FROM `version` '
               . 'WHERE `version`.id_api = ' . $this->api['id'] . ' ';
        $this->view->mainVersions = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );
        $this->view->breadCrumbs = array();
        $this->view->breadCrumbs[] = array(
            'label' => '<img src="app/back/img/gray_dark/home_12x12.png"> '
                    . $this->view->site,
        );

        /** On indique que l'on est dans une autre api **/
        if ($this->api['id'] != 1) {
            $this->view->breadCrumbs[] = array(
                    'label' => $this->api['label'],
            );
        }

        $this->view->appConfig = $this->appConfig;

        /**
         * On recupere la configuration du module pages (Menu + liste)
         */
        $path = \Solire\Lib\FrontController::search('config/page.cfg.php');
        $completConfig = array();
        $appList = \Solire\Lib\FrontController::getAppDirs();
        unset($config);
        foreach ($appList as $app) {
           /**
            * On recupere la configuration du module pages (Menu + liste)
            *  En cherchant si une configuration a été définie pour l'api courante
            * Sinon on récupère le fichier de configuration générale
            */
            $path = new \Solire\Lib\Path(
                $app['dir'] . DS . 'back/config/page-' . BACK_ID_API . '.cfg.php',
                \Solire\Lib\Path::SILENT
            );
            if ($path->get() == false) {
                $path = new \Solire\Lib\Path(
                    $app['dir'] . DS . 'back/config/page.cfg.php',
                    \Solire\Lib\Path::SILENT
                );
            }

            if ($path->get() == false) {
                continue;
            }
            include $path->get();

            if (!isset($config)) {
                $exc = new \Exception('fichier de config erroné [' . $path->get() . ']');
                throw $exc;
            }

            /**
             * équivalent à '$completConfig = $config + $completConfig;' ?
             */
//            foreach ($config as $key => $value) {
//                $completConfig[$key] = $value;
//            }

            $completConfig = $completConfig + $config;

            unset($config, $key, $value);
        }

        $this->configPageModule = $completConfig;
        unset($path, $config);
        $this->view->menuPage = array();
        foreach ($this->configPageModule as $configPage) {
            $this->view->menuPage[] = array(
                'label' => $configPage['label'],
                'display' => $configPage['display'],
            );
        }

        $query = 'SELECT gab_gabarit.id, gab_gabarit.* '
               . 'FROM gab_gabarit '
               . 'WHERE gab_gabarit.id_api = ' . $this->api['id'] . ' ';
        $this->gabarits = $this->db->query($query)->fetchAll(
            \PDO::FETCH_UNIQUE | \PDO::FETCH_ASSOC
        );

        $query = 'SELECT * '
               . 'FROM gab_page gp '
               . 'WHERE rewriting = "" '
               . ' AND gp.suppr = 0 '
               . ' AND id_api = ' . BACK_ID_API
               . ' AND id_version = ' . BACK_ID_VERSION;

        $this->view->pagesNonTraduites = $this->db->query($query)->fetchAll(
            \PDO::FETCH_ASSOC
        );

        $hook = new \Solire\Lib\Hook();
        $hook->setSubdirName('back');

        $hook->ctrl = $this;

        $hook->exec('start');
    }
}
