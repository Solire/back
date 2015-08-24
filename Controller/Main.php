<?php
/**
 * Controleur principal du back
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Monolog\Logger;
use Solire\Lib\Monolog\Handler\PDOHandler;
use Solire\Lib\Session;
use Solire\Lib\Path;
use Solire\Lib\FrontController;
use Solire\Lib\Model\FileManager;
use Solire\Lib\Model\GabaritManager;
use Solire\Lib\Hook;
use Solire\Lib\Security\AntiBruteforce\AntiBruteforce;
use Solire\Conf\Loader as ConfLoader;
use Solire\Lib\Loader\RequireJs;

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
    public $utilisateur;

    /**
     * Api en cours
     *
     * @var array
     */
    public $api;

    /**
     * Manager des requetes liées aux pages
     *
     * @var \Solire\Lib\Model\GabaritManager
     */
    public $gabaritManager = null;

    /**
     * Manager fichiers
     *
     * @var \Solire\Lib\Model\FileManager
     */
    public $fileManager = null;

    /**
     * Logger relatif aux actions de l'utilisateur
     *
     * @var Logger
     */
    public $userLogger = null;

    /**
     * Chargeur de script pour requireJS
     *
     * @var \Solire\Lib\RequireJs
     */
    protected $requireJs = null;

    /**
     * Always execute before other method in controller
     *
     * @return void
     * @hook back/ start Ajouter facilement des traitements au start du back
     */
    public function start()
    {
        /* Antibruteforce */
        $securityConfigPath = FrontController::search('config/security.yml');
        $securityConfig = ConfLoader::load($securityConfigPath);
        $antiBruteforce = new AntiBruteforce($securityConfig->antibruteforce, $_SERVER['REMOTE_ADDR']);

        if ($antiBruteforce->isBlocking()) {
            header('HTTP/1.0 429 Too Many Requests');
            /*
             * On garde en session le temps restant pour s'en servir
             */
            $_SESSION['so_fail2ban'] = array(
                'remainingTime' => $antiBruteforce->unblockRemainingTime()
            );
            FrontController::run('Error', 'error429Fail2ban');
            die;
        }

        parent::start();

        /*
         * Système de log en BDD
         */
        $userLogger = new Logger('backUser');
        $userLogger->pushHandler(new PDOHandler($this->db));
        $this->userLogger = $userLogger;

        /*
         * Utilisateur connecté ?
         */
        $this->utilisateur = new Session('back');

        if (isset($_POST['log']) && isset($_POST['pwd'])
            && !empty($_POST['log']) && !empty($_POST['pwd'])
        ) {
            try {
                $this->utilisateur->connect(
                    $_POST['log'],
                    $_POST['pwd']
                );
            } catch (\Exception $exc) {
                $login = filter_var($_POST['log'], FILTER_SANITIZE_STRING);
                $this->userLogger->addError(
                    'Connexion échouée',
                    [
                        'user' => [
                            'login' => $login,
                        ]
                    ]
                );
                throw $exc;
            }

            $this->userLogger->addInfo(
                'Connexion réussie',
                [
                    'user' => [
                        'id'    => $this->utilisateur->id,
                        'login' => $this->utilisateur->login,
                    ]
                ]
            );

            $message = 'Connexion réussie, vous allez être redirigé';

            exit(json_encode(array('success' => true, 'message' => $message)));
        }

        if (!$this->utilisateur->isConnected()
            && (!isset($this->noRedirect) || $this->noRedirect === false)
        ) {
            $this->simpleRedirect('back/sign/start.html', true);
        }

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

        $this->loadRessources();

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

        $className = FrontController::searchClass('Model\GabaritManager');
        if ($className !== false) {
            $this->gabaritManager = new $className();
        } else {
            $this->gabaritManager = new GabaritManager();
        }

        $this->fileManager = new FileManager();

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
        $this->view->requireJs = $this->requireJs;
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
            'label' => '<i class="fa fa-home"></i>'
                    . ' '
                    . $this->view->site,
        );

        /* On indique que l'on est dans une autre api **/
        if ($this->api['id'] != 1) {
            $this->view->breadCrumbs[] = array(
                    'label' => $this->api['label'],
            );
        }

        $this->view->appConfig = $this->appConfig;

        /*
         * On recupere la configuration du module pages (Menu + liste)
         */
        $completConfig = [];
        $path = FrontController::search(
            'config/page-' . BACK_ID_API . '.cfg.php'
        );
        if ($path !== false) {
            include $path;
            $completConfig = $config;
        } else {
            $path = FrontController::search('config/page.cfg.php');
            if ($path !== false) {
                include $path;
                $completConfig = $config;
            }
        }

//        $completConfig = [];
//        $appList = \Solire\Lib\FrontController::getAppDirs();
//        unset($config);
//        foreach ($appList as $app) {
//           /**
//            * On recupere la configuration du module pages (Menu + liste)
//            *  En cherchant si une configuration a été définie pour l'api courante
//            * Sinon on récupère le fichier de configuration générale
//            */
//            $path = new Path(
//                $app['dir'] . Path::DS . 'back/config/page-' . BACK_ID_API . '.cfg.php',
//                Path::SILENT
//            );
//            if ($path->get() == false) {
//                $path = new Path(
//                    $app['dir'] . Path::DS . 'back/config/page.cfg.php',
//                    Path::SILENT
//                );
//            }
//
//            if ($path->get() == false) {
//                continue;
//            }
//            include $path->get();
//
//            if (!isset($config)) {
//                $exc = new \Exception('fichier de config erroné [' . $path->get() . ']');
//                throw $exc;
//            }
//
//            $completConfig = $completConfig + $config;
//
//            unset($config, $key, $value);
//        }

        $this->configPageModule = $completConfig;
        unset($path, $config);
        $this->view->menuPage = [];
        foreach ($this->configPageModule as $configPage) {
            $this->view->menuPage[] = [
                'label' => $configPage['label'],
                'display' => $configPage['display'],
            ];
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

        $hook = new Hook();
        $hook->setSubdirName('back');

        $hook->controller = $this;

        $hook->exec('start');
    }

    /**
     * Fonction éxécutée après l'execution de la fonction relative à la page en cours
     *
     * @return void
     * @hook back/ shutdown Avant l'inclusion de la vue
     */
    public function shutdown()
    {
        parent::shutdown();

        $hook = new Hook();
        $hook->setSubdirName('Back');

        $hook->controller = $this;

        $hook->exec('Shutdown');
    }

    protected function loadRessources()
    {
        /* Chargement de requireJS */
        $requireInitPath = $this->javascript->getPath('back/js/init.js');
        $this->javascript->addLibrary(
            'back/bower_components/requirejs/require.js',
            array('data-main' => $requireInitPath)
        );

        $this->requireJs = new RequireJs(FrontController::$publicDirs);

        /* Jquery */
        $this->requireJs->addLibrary(
            'back/bower_components/jquery/dist/jquery.min.js',
            array('name' => 'jquery')
        );

        /* Sortable */
        $this->requireJs->addLibrary('back/bower_components/Sortable/Sortable.js',
            array(
                'name' => 'sortable',
            )
        );

        /* Jquery cookie */
        $this->requireJs->addLibrary('back/bower_components/jquery.cookie/jquery.cookie.js',
            array(
                'name' => 'jqueryCookie',
                'deps' => array(
                    'jquery',
                )
            )
        );

        /* Bootstrap */
        $this->requireJs->addLibrary('back/bower_components/bootstrap/dist/js/bootstrap.min.js',
            array(
                'name' => 'bootstrap',
                'deps' => array(
                    'jquery',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/bootstrap/dist/css/bootstrap.min.css');

        /* Bootstrap meteriel design */
        $this->requireJs->addLibrary('back/bower_components/bootstrap-material-design/dist/js/ripples.min.js',
            array(
                'name' => 'ripples',
                'deps' => array(
                    'bootstrap',
                )
            )
        );

        $this->requireJs->addLibrary('back/bower_components/bootstrap-material-design/dist/js/material.min.js',
            array(
                'name' => 'material',
                'deps' => array(
                    'bootstrap',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/bootstrap-material-design/dist/css/roboto.min.css');
        $this->css->addLibrary('back/bower_components/bootstrap-material-design/dist/css/material.min.css');
        $this->css->addLibrary('back/bower_components/bootstrap-material-design/dist/css/ripples.min.css');

//        $this->css->addLibrary('back/bower_components/bootstrap/dist/css/bootstrap-theme.min.css');
//        $this->css->addLibrary('back/css/bootstrap-theme/bootstrap.min.css');

        /* Bootstrap datepicker */
        $this->requireJs->addLibrary('back/bower_components/bootstrap-datepicker/js/bootstrap-datepicker.js',
            array(
                'name' => 'bootstrapDatepicker',
                'deps' => array(
                    'bootstrap',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/bootstrap-datepicker/css/datepicker3.css');

        // Fichier de traduction FR du datepicker
        $this->requireJs->addLibrary('back/bower_components/bootstrap-datepicker/js/locales/bootstrap-datepicker.fr.js',
            array(
                'name' => 'bootstrapDatepickerFr',
                'deps' => array(
                    'bootstrapDatepicker',
                )
            )
        );

        /* Bootstrap autocomplete */
        $this->requireJs->addLibrary('back/bower_components/select2/dist/js/select2.full.min.js',
            array(
                'name' => 'autocomplete',
                'deps' => array(
                    'jquery',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/select2/dist/css/select2.min.css');

        /* Bootstrap typeahead */
        $this->requireJs->addLibrary('back/bower_components/typeahead.js/dist/typeahead.jquery.min.js',
            array(
                'name' => 'typeahead',
                'deps' => array(
                    'jquery',
                )
            )
        );

        /* Datatables */
        $this->requireJs->addLibrary('back/bower_components/datatables/media/js/jquery.dataTables.min.js',
            array(
                'name' => 'datatables',
                'deps' => array(
                    'jquery',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/datatables/media/css/jquery.dataTables.min.css');

        /* Plupload */
        $this->requireJs->addLibrary('back/bower_components/plupload/js/plupload.full.min.js',
            array(
                'name' => 'plupload'
            )
        );
        $this->requireJs->addLibrary('back/bower_components/jquery-pluploader/dist/jquery.pluploader.min.js',
            array(
                'name' => 'jqueryPluploader',
                'deps' => array(
                    'plupload',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/datatables/media/css/jquery.dataTables.min.css');

        /* Noty */
        $this->requireJs->addLibrary('back/bower_components/noty/js/noty/packaged/jquery.noty.packaged.min.js',
            array(
                'name' => 'noty',
                'deps' => array(
                    'jquery',
                )
            )
        );

        /* SoModal */
        $this->requireJs->addLibrary('back/bower_components/jquery.transit/jquery.transit.js',
            array('name' => 'jqueryTransit')
        );

        $this->requireJs->addLibrary('back/bower_components/jquery-somodal/dist/js/jquery.somodal.min.js',
            array(
                'name' => 'jquerySoModal',
                'deps' => array(
                    'jquery',
                    'jqueryTransit',
                )
            )
        );

        $this->css->addLibrary('back/bower_components/jquery-somodal/dist/css/jquery.somodal.min.css');

        /* JSTREE */
        $this->requireJs->addLibrary('back/bower_components/jstree/dist/jstree.min.js',
            array('name' => 'jsTree')
        );

        // Thème par défaut de jstree
//        $this->css->addLibrary('back/bower_components/jstree/dist/themes/default/style.min.css');

        // Thème Bootstrap de jstree
        $this->css->addLibrary('back/bower_components/jstree-bootstrap-theme/dist/themes/proton/style.min.css');

        /* tinyMCE */
        $this->requireJs->addLibrary('back/bower_components/tinymce/tinymce.min.js',
            array('name' => 'tinyMCE_source')
        );

        $this->requireJs->addLibrary('back/bower_components/bower-tinymce-amd/tinyMCE.js',
            array('name' => 'tinyMCE')
        );

        /* Jquery Form controle */
        $this->requireJs->addLibrary('back/bower_components/jquery-controle/jquery.controle.min.js',
            array(
                'name' => 'jqueryControle',
                'deps' => array(
                    'jquery',
                )
            )
        );


        /* Modules Solire */
        $requireJsModules = array(
            'modules/page/liste',
            'modules/page/affichegabarit',
            'modules/page/listefichiers',
            'modules/page/signin',
//            'modules/page/form',
            'modules/helper/dialog',
            'modules/helper/wysiwyg',
            'modules/helper/datepicker',
            'modules/helper/search',
            'modules/helper/autocomplete',
            'modules/helper/autocompleteFile',
            'modules/helper/autocompleteJoin',
            'modules/helper/confirm',
            'modules/helper/noty',
            'modules/helper/datatable',
            'modules/helper/ajaxform',
            'modules/helper/uploader',
            'modules/helper/sortable',
            'modules/page/upload',
            'modules/helper/ajaxcall',
            'modules/helper/zoom',
            'modules/render/visible',
            'modules/render/delete',
            'modules/render/beforeloadpage',
            'modules/render/aftersavepage',
            'modules/page/block',
            'modules/page/apichange',
        );

        $this->requireJs->addModules($requireJsModules);

        /* font-awesome */
        $this->css->addLibrary('back/bower_components/font-awesome/css/font-awesome.min.css');

        /* Librairies Solire */
        $this->css->addLibrary('back/css/style.css');


        /* Reste des librairies à nettoyer */

//        $this->javascript->addLibrary('back/js/main.js');
//        $this->javascript->addLibrary('back/js/jquery/jquery.cookie.js');
//        $this->javascript->addLibrary('back/js/jquery/sticky.js');
//        $this->javascript->addLibrary('back/js/jquery/jquery.livequery.min.js');
//        $this->javascript->addLibrary('back/js/jquery/jquery.stickyPanel.min.js');
//        $this->javascript->addLibrary('back/js/newstyle.js');
//        $this->css->addLibrary('back/css/sticky.css');
    }
}
