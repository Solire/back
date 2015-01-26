<?php
/**
 * Controller des medias
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Solire\Lib\Path;

/**
 * Controller des medias
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Media extends Main
{
    /**
     * Page courante
     *
     * @var \Solire\Lib\Model\GabaritPage
     */
    private $page = null;

    /**
     * Table contenant les infos sur les fichiers
     *
     * @var string
     */
    protected $mediaTableName = 'media_fichier';

    /**
     * Toujours executé avant chaque action du controleur
     *
     * @return void
     */
    public function start()
    {
        parent::start();

        $upload = $this->mainConfig->get('upload');
        $this->upload_path     = $upload['path'];
        $this->upload_temp     = $upload['temp'];
        $this->upload_vignette = $upload['vignette'];
        $this->upload_apercu   = $upload['apercu'];
    }

    /**
     * Affichage du gestionnaire de fichiers
     *
     * @return void
     */
    public function startAction()
    {

        $this->fileDatatable();
        $this->javascript->addLibrary('back/js/jquery/jquery.hotkeys.js');
        $this->javascript->addLibrary('back/js/jstree/jquery.jstree.js');
        //$this->javascript->addLibrary('back/js/jquery/jquery.dataTables.min.js');
        $this->javascript->addLibrary('back/js/plupload/plupload.full.js');
        $this->javascript->addLibrary('back/js/plupload/jquery.pluploader.min.js');
        $this->javascript->addLibrary('back/js/listefichiers.js');
        $this->javascript->addLibrary('back/js/jquery/jquery.scroller-1.0.min.js');

        //$this->css->addLibrary('back/css/demo_table_jui.css');
        $this->css->addLibrary('back/css/jquery.scroller.css');

        $this->view->breadCrumbs[] = array(
            'label' => 'Gestion des fichiers',
            'url' => '',
        );
    }

    /**
     * Affiche la liste des fichiers
     *
     * @return void
     */
    public function listAction()
    {
        $this->view->unsetMain();
        $this->files = array();

        /** Permet plusieurs liste de fichier dans la meme page **/
        $this->view->idFilesList = null;
        if (isset($_REQUEST['id'])) {
            $this->view->idFilesList = '_' . $_REQUEST['id'];
        }

        $this->view->prefixFileUrl = null;
        if (isset($_REQUEST['prefix_url'])) {
            $this->view->prefixFileUrl = $_REQUEST['prefix_url'] . '/';
        }

        $id_gab_page = isset($_REQUEST['id_gab_page']) && $_REQUEST['id_gab_page'] ? $_REQUEST['id_gab_page'] : 0;

        if ($id_gab_page) {
            $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
            $orderby = isset($_REQUEST['orderby']['champ']) ? $_REQUEST['orderby']['champ'] : '';
            $sens = isset($_REQUEST['orderby']['sens']) ? $_REQUEST['orderby']['sens'] : '';

            $this->page = $this->gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $id_gab_page);

            $this->files = $this->fileManager->getList($this->page->getMeta('id'), 0, $search, $orderby, $sens);
        }

        $this->view->files = array();
        foreach ($this->files as $file) {
            $ext = strtolower(array_pop(explode('.', $file['rewriting'])));
            $prefixPath = $this->api['id'] == 1 ? '' : '..' . Path::DS;
            $file['path'] = $this->view->prefixFileUrl . $file['id_gab_page'] . Path::DS . $file['rewriting'];

            $serverpath = $this->upload_path . Path::DS . $file['id_gab_page']
                        . Path::DS . $file['rewriting'];

            if (!file_exists($serverpath)) {
                continue;
            }

            $file['class'] = 'hoverprevisu vignette';

            if (array_key_exists($ext, \Solire\Lib\Model\FileManager::$_extensions['image'])) {
                $file['path_mini']  = $this->view->prefixFileUrl
                                    . $file['id_gab_page'] . '/'
                                    . $this->upload_vignette . '/'
                                    . $file['rewriting'];

                $sizes = getimagesize($serverpath);
                $file['class'] .= '  img-polaroid';
                $file['width']  = $sizes[0];
                $file['height'] = $sizes[1];
            } else {
                $file['class']      = 'vignette';
                $file['path_mini']  = 'public/default/back/img/filetype/' . $ext . '.png';
            }
            $file['poids'] = \Solire\Lib\Tools::format_taille($file['taille']);

            $this->view->files[] = $file;
        }
    }

    /**
     * Génération du datatable des fichiers
     *
     * @return void
     */
    private function fileDatatable()
    {
        $configName = 'file';
        $gabarits = array();

        $configPath = \Solire\Lib\FrontController::search(
            'config/datatable/' . $configName . '.cfg.php'
        );

        $datatableClassName = '\\App\\Back\\Datatable\\File';

        $datatable = null;

        foreach (\Solire\Lib\FrontController::getAppDirs() as $appDir) {
            $datatableClassName = '\\' . $appDir['name']
                                . '\\Back'
                                . '\\Datatable'
                                . '\\' . $configName;
            if (class_exists($datatableClassName)) {
                $datatable = new $datatableClassName(
                    $_GET,
                    $configPath,
                    $this->db,
                    'back/css/datatable/',
                    'back/js/datatable/',
                    'back/img/datatable/'
                );

                break;
            }
        }

        if ($datatable == null) {
            $datatable = new \Solire\Lib\Datatable\Datatable(
                $_GET,
                $configPath,
                $this->db,
                'back/css/datatable/',
                'back/js/datatable/',
                'back/img/datatable/'
            );
        }

        $datatable->start();

        if (isset($_GET['json'])
            || isset($_GET['nomain']) && $_GET['nomain'] == 1
        ) {
            echo $datatable;
            exit();
        }

        $this->view->datatableRender = $datatable;
    }

    /**
     * Contenu de la popup listant les fichiers d'une page
     *
     * @return void
     */
    public function popuplistefichiersAction()
    {
        $this->listAction();
    }

    /**
     * Affiche la liste des dossiers d'un dossier de medias
     *
     * @return void
     */
    public function folderlistAction()
    {
        $this->view->unsetMain();
        $this->view->enable(false);

        $res = array();

        if ($_REQUEST['id'] === '') {
            $res[] = array(
                'attr' => array(
                    'id' => 'node_0',
                    'rel' => 'root'
                ),
                'data' => array(
                    'title' => 'Ressources'
                ),
                'state' => 'closed'
            );
        } elseif ($_REQUEST['id'] === '0') {
            $rubriques = $this->gabaritManager->getList(BACK_ID_VERSION, $this->api['id'], 0);
            $configPageModule = $this->configPageModule[$this->utilisateur->gabaritNiveau];
            $gabaritsListUser = $configPageModule['gabarits'];
            foreach ($rubriques as $rubrique) {
                /*
                 * On exclu les gabarits qui ne sont pas dans les droits
                 */
                if ($gabaritsListUser != '*') {
                    if (!in_array($rubrique->getMeta('id_gabarit'), $gabaritsListUser)) {
                        continue;
                    }
                }

                $title = '<div class="horizontal_scroller" '
                       . 'style="width:150px;height: 17px; cursor: pointer;">'
                       . '<div class="scrollingtext" style="left: 0px;">'
                       . $rubrique->getMeta('titre')
                       . '</div></div>';
                $res[] = array(
                    'attr' => array(
                        'id' => 'node_' . $rubrique->getMeta('id'),
                        'rel' => 'page',
                    ),
                    'data' => array(
                        'title' => $title,
                    ),
                    'state' => 'closed',
                );
            }
        } else {
            $sousRubriques = $this->gabaritManager->getList(BACK_ID_VERSION, $this->api['id'], $_REQUEST['id']);

            $configPageModule = $this->configPageModule[$this->utilisateur->gabaritNiveau];
            $gabaritsListUser = $configPageModule['gabarits'];

            foreach ($sousRubriques as $sousRubrique) {
                /** On exclu les gabarits qui ne sont pas dans les droits **/
                if ($gabaritsListUser != '*') {
                    if (!in_array($sousRubrique->getMeta('id_gabarit'), $gabaritsListUser)) {
                        continue;
                    }
                }

                $query = 'SELECT COUNT(*) '
                       . 'FROM `' . $this->mediaTableName . '` '
                       . 'WHERE `suppr` = 0 '
                       . 'AND `id_gab_page` = ' . $sousRubrique->getMeta('id');
                $nbre = $this->db->query($query)->fetchColumn();

                $title = $sousRubrique->getMeta('titre');
                if (mb_strlen($sousRubrique->getMeta('titre')) > 16) {
                    $title = mb_substr($title, 0, 16) . '&hellip;';
                }

                $tagTitle = '<div class="horizontal_scroller" '
                       . 'style="width:100px;height: 17px; cursor: pointer;">'
                       . '<div class="scrollingtext" style="left: 0px;">'
                       . $title
                       . ' (<i>' . $nbre . '</i>)</div></div>';

                $res[] = array(
                    'attr' => array(
                        'id' => 'node_' . $sousRubrique->getMeta('id'),
                        'rel' => 'page'
                    ),
                    'data' => array(
                        'title' => $tagTitle,
                        'attr' => array(
                            'title' => $sousRubrique->getMeta('titre')
                        )
                    ),
                    'state' => 'closed'
                );
            }
        }

        echo json_encode($res);
    }

    /**
     * Action d'upload d'un fichier (js utilisé côté client : plupload)
     *
     * @return void
     */
    public function uploadAction()
    {
        /** Permet plusieurs liste de fichier dans la meme page **/
        $this->view->idFilesList = null;
        if (isset($_REQUEST['id'])) {
            $this->view->idFilesList = '_' . $_REQUEST['id'];
        }

        $this->view->prefixFileUrl = null;
        if (isset($_REQUEST['prefix_url'])) {
            $this->view->prefixFileUrl = $_REQUEST['prefix_url'] . '/';
        }

        $id_gab_page = 0;
        if (isset($_GET['id_gab_page']) && $_GET['id_gab_page']) {
            $id_gab_page = $_GET['id_gab_page'];
        } elseif (isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page']) {
            $id_gab_page = $_COOKIE['id_gab_page'];
        }

        $gabaritId = 0;
        if (isset($_REQUEST['gabaritId'])) {
            $gabaritId = (int)$_REQUEST['gabaritId'];
        }

        if ($id_gab_page) {
            $targetTmp      = $this->upload_temp;
            $targetDir      = $id_gab_page;
            $vignetteDir    = $id_gab_page . Path::DS . $this->upload_vignette;
            $apercuDir      = $id_gab_page . Path::DS . $this->upload_apercu;

            $response = $this->fileManager->uploadGabPage(
                $this->upload_path,
                $id_gab_page,
                0,
                $targetTmp,
                $targetDir,
                $vignetteDir,
                $apercuDir
            );

            if ($response['status'] == 'error') {
                echo json_encode($response);
                exit();
            }

            $response['size'] = \Solire\Lib\Tools::format_taille($response['size']);

            if (isset($response['mini_path'])) {
                $response['mini_path'] = $this->view->prefixFileUrl
                                   . $response['mini_path'];
                $response['mini_url'] = $this->view->prefixFileUrl
                                  . $response['mini_url'];
                $response['image'] = array(
                    'url' => $this->view->prefixFileUrl . $id_gab_page
                             . '/' . $response['filename']
                );
            }

            $response['url']       = $this->view->prefixFileUrl . $response['url'];

            if (isset($response['minipath'])) {
                $response['minipath'] = $this->view->prefixFileUrl
                                  . $response['minipath'];
                $response['image'] = array(
                    'url' => $this->view->prefixFileUrl . $id_gab_page
                             . '/' . $response['filename']
                );
                $response['path'] = $this->view->prefixFileUrl . $response['path'];
            }
        } else {
            if (isset($_COOKIE['id_temp'])
                && is_numeric($_COOKIE['id_temp'])
                && $_COOKIE['id_temp'] > 0
            ) {
                $id_temp = (int) $_COOKIE['id_temp'];
                $target = 'temp-' . $id_temp;
            } else {
                $id_temp = 1;
                $target = 'temp-' . $id_temp;
                while (file_exists($this->upload_path . Path::DS . $target)) {
                    $id_temp++;
                    $target = 'temp-' . $id_temp;
                }
            }

            $targetTmp      = $this->upload_temp;
            $targetDir      = $target;
            $vignetteDir    = $target . Path::DS . $this->upload_vignette;
            $apercuDir      = $target . Path::DS . $this->upload_apercu;

            $response = $this->fileManager->uploadGabPage(
                $this->upload_path,
                0,
                $id_temp,
                $targetTmp,
                $targetDir,
                $vignetteDir,
                $apercuDir
            );

            if ($response['status'] == 'success') {
                if (isset($response['mini_path'])) {
                    $response['mini_path'] = $this->view->prefixFileUrl . $response['mini_path'];
                    $response['mini_url'] = $this->view->prefixFileUrl . $response['mini_url'];
                    $response['image'] = array(
                        'url' => $this->view->prefixFileUrl . $id_gab_page . DS . $response['filename']
                    );

                    // Génération de miniatures additionnelles
                    $filePath = $this->view->prefixFileUrl . $response['path'];
                    $this->miniatureProcess($gabaritId, $filePath);

                }
                $response['path'] = $this->view->prefixFileUrl . $response['path'];
                $response['url'] = $this->view->prefixFileUrl . $response['url'];
                $response['size'] = \Solire\Lib\Tools::format_taille($response['size']);
                $response['id_temp'] = $id_temp;
            }
        }

        if ($response['status'] == 'error') {
            $logTxt = '<b>Nom</b> : ' . $_REQUEST['name'] . '<br /><b>Page</b> : '
                    . $id_gab_page . '<br /><span style="color:red;">Error '
                    . $response['error']['code'] . ' : ' . $response['error']['message']
                    . '</span>';
            $this->log->logThis(
                'Upload échoué',
                $this->utilisateur->get('id'),
                $logTxt
            );
        } else {
            $logTxt = '<b>Nom</b> : ' . $_REQUEST['name']. '<br /><b>Page</b> : '
                    . $id_gab_page;
            $this->log->logThis(
                'Upload réussi',
                $this->utilisateur->get('id'),
                $logTxt
            );
        }

        $this->view->enable(false);
        $this->view->unsetMain();
        echo json_encode($response);
    }

    /**
     * Action de redimenssionnement d'une image
     *
     * @return void
     */
    public function cropAction()
    {
        if (isset($_GET['id_gab_page']) && $_GET['id_gab_page'] > 0) {
            $id_gab_page = $_GET['id_gab_page'];
        } elseif (isset($_COOKIE['id_gab_page']) && $_COOKIE['id_gab_page'] > 0) {
            $id_gab_page = $_COOKIE['id_gab_page'];
        } else {
            $id_gab_page = 0;
        }

        /* Dimensions de recadrage */
        $x = $_POST['x'];
        $y = $_POST['y'];
        $w = $_POST['w'];
        $h = $_POST['h'];

        /* Information sur le fichier */
        $newImageName   = \Solire\Lib\Format\String::urlSlug(
            $_POST['image-name'],
            '-',
            255
        );
        $filepath       = $_POST['filepath'];
        $filename       = pathinfo($filepath, PATHINFO_BASENAME);
        $ext            = pathinfo($filename, PATHINFO_EXTENSION);

        if ($id_gab_page) {
            /** Cas d'une édition de page */

            $targetDir      = $id_gab_page;
            $vignetteDir    = $id_gab_page . DS . $this->upload_vignette;
            $apercuDir      = $id_gab_page . DS . $this->upload_apercu;
        } elseif (isset($_COOKIE['id_temp'])
            && $_COOKIE['id_temp']
            && is_numeric($_COOKIE['id_temp'])
        ) {
            /** Cas d'une création de page */

            $id_temp = (int) $_COOKIE['id_temp'];
            $target = 'temp-' . $id_temp;

//            $targetTmp      = $this->upload_temp;
            $targetDir      = $target;
            $vignetteDir    = $target . DS . $this->upload_vignette;
            $apercuDir      = $target . DS . $this->upload_apercu;
        } else {
            exit();
        }

        $count_temp = 1;
        $target     = $newImageName . '.' . $ext;
        while (file_exists($this->upload_path . DS . $targetDir . DS . $target)) {
            $count_temp++;
            $target = $newImageName . '-' . $count_temp . '.' . $ext;
        }

        switch ($_POST['force-width']) {
            case 'width':
                $tw = $_POST['minwidth'];
                $th = ($_POST['minwidth'] / $w) * $h;
                break;

            case 'height':
                $th = $_POST['minheight'];
                $tw = ($_POST['minheight'] / $h) * $w;
                break;

            case 'width-height':
                $tw = $_POST['minwidth'];
                $th = $_POST['minheight'];
                break;

            default:
                $tw = false;
                $th = false;
                break;
        }

        if (intval($tw) <= 0) {
            $tw = false;
        }

        if (intval($th) <= 0) {
            $th = false;
        }

        if ($id_gab_page) {
            $this->fileManager->crop(
                $this->upload_path,
                $filepath,
                $ext,
                $targetDir,
                $target,
                $id_gab_page,
                0,
                $vignetteDir,
                $apercuDir,
                $x,
                $y,
                $w,
                $h,
                $tw,
                $th
            );
        } else {
            $response = $this->fileManager->crop(
                $this->upload_path,
                $filepath,
                $ext,
                $targetDir,
                $target,
                0,
                $id_temp,
                $vignetteDir,
                $apercuDir,
                $x,
                $y,
                $w,
                $h,
                $tw,
                $th
            );

            if (isset($response['minipath'])) {
                $response['minipath'] = $response['minipath'];
                $response['path'] = $response['path'];
                $response['size'] = \Solire\Lib\Tools::format_taille($response['size']);
                $response['id_temp'] = $id_temp;
            }
        }

        $response = array();
        $response['path']           = $targetDir . DS . $target;
        $response['filename']       = $target;
        $response['filename_front'] = $targetDir . '/' . $target;

        if (\Solire\Lib\Model\fileManager::isImage($response['filename'])) {
            $path       = $response['path'];
            $vignette   = $targetDir . DS
                        . $this->upload_vignette . DS
                        . $response['filename'];
            $serverpath = $this->upload_path . DS
                        . $targetDir . DS
                        . $response['filename'];

            if (\Solire\Lib\Model\fileManager::isImage($response['filename'])) {
                $sizes = getimagesize($serverpath);
                $size = $sizes[0] . ' x ' . $sizes[1];
                $response['vignette'] = $vignette;
                $response['label'] = $response['filename'];
                $response['size'] = $size;
                $response['value'] = $response['filename'];
                $response['utilise'] = 1;
            }
        }

        $this->view->enable(false);
        $this->view->unsetMain();
        echo json_encode($response);
    }

    /**
     * Action permettant de supprimer d'un fichier
     *
     * @return void
     */
    public function deleteAction()
    {
        $id_media_fichier = 0;
        if (isset($_COOKIE['id_media_fichier'])) {
            $id_media_fichier = $_COOKIE['id_media_fichier'];
        } elseif (isset($_REQUEST['id_media_fichier'])) {
            $id_media_fichier = $_REQUEST['id_media_fichier'];
        }

        $query = 'UPDATE `' . $this->mediaTableName . '` SET '
               . '`suppr` = NOW() '
               . 'WHERE `id` = ' . $id_media_fichier;
        $success = $this->db->exec($query);

        if (!$success) {
            $status = 'error';
            $logTitle = 'Suppression de fichier échouée';
            $logMessage = '<b>Id</b> : ' . $id_media_fichier . ' '
                        . '| <b>Table</b>' . $this->mediaTableName
                        . '<br /><span style="color:red;">Error</span>';
        } else {
            $status = 'success';
            $logTitle = 'Suppression de fichier réussie';
            $logMessage = '<b>Id</b> : ' . $id_media_fichier . ' '
                        . '| <b>Table</b>' . $this->mediaTableName . '';
        }

        $this->log->logThis(
            $logTitle,
            $this->utilisateur->get('id'),
            $logMessage
        );

        $response = array(
            'status' => $status
        );

        $this->view->enable(false);
        $this->view->unsetMain();
        echo json_encode($response);
    }

    /**
     * Action de recherche de medias
     *
     * @return void
     */
    public function autocompleteAction()
    {
        $this->view->enable(false);
        $this->view->unsetMain();

        $prefixPath = '';

        $id_gab_page = 0;
        if (isset($_GET['id_gab_page'])) {
            $id_gab_page = $_GET['id_gab_page'];
        } elseif (isset($_COOKIE['id_gab_page'])) {
            $id_gab_page = $_COOKIE['id_gab_page'];
        }

        $id_temp = 0;
        if (isset($_GET['id_temp'])) {
            $id_temp = $_GET['id_temp'];
        } elseif (isset($_COOKIE['id_temp'])) {
            $id_temp = $_COOKIE['id_temp'];
        }

        if (isset($_REQUEST['extensions'])
            && $_REQUEST['extensions'] != ''
        ) {
            $extensions = explode(';', $_REQUEST['extensions']);
        } else {
            $extensions = false;
        }

        $json = array();

        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $tinyMCE = isset($_GET['tinyMCE']);

        if ($id_gab_page || $id_temp) {
            $files = $this->fileManager->getSearch($term, $id_gab_page, $id_temp, $extensions);

            $dir = $id_gab_page ? $id_gab_page : 'temp-' . $id_temp;

            foreach ($files as $file) {
                if (!$tinyMCE || \Solire\Lib\Model\FileManager::isImage($file['rewriting'])) {
                    $path       = $dir . DS
                                . $file['rewriting'];
                    $vignette   = $dir . DS
                                . $this->upload_vignette . DS
                                . $file['rewriting'];
                    $serverpath = $this->upload_path . DS
                                . $dir . DS
                                . $file['rewriting'];

                    if (!file_exists($serverpath)) {
                        continue;
                    }

                    $realpath = \Solire\Lib\Registry::get('basehref') . $dir . '/' . $file['rewriting'];
                    if (\Solire\Lib\Model\FileManager::isImage($file['rewriting'])) {
                        $sizes = getimagesize($serverpath);
                        $size = $sizes[0] . ' x ' . $sizes[1];
                    } else {
                        $size = '';
                    }

                    if ($tinyMCE) {
                        $json[] = array(
                            'title' => $file['rewriting'] . ($size ? ' (' . $size . ')' : ''),
                            'value' => $realpath,
                        );
                    } else {
                        $json[] = array(
                            'path' => $path,
                            'vignette' => $vignette,
                            'label' => $file['rewriting'],
                            'utilise' => $file['utilise'],
                            'size' => ($size ? $size : ''),
                            'value' => $file['rewriting'],
                        );
                    }
                }
            }
        }

        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode($json);
    }

    /**
     * Défini le nom de la table des medias
     *
     * @param string $mediaTableName Nom de la table media
     *
     * @return void
     */
    public function setMediaTableName($mediaTableName)
    {
        $this->mediaTableName = $mediaTableName;
    }

    /**
     * Génération de miniatures en fonction des paramètres des champs d'un
     * gabarit
     *
     * @param int    $gabaritId Id du gabarit
     * @param string $filePath  Chemin du fichier
     *
     * @return void
     */
    protected function miniatureProcess($gabaritId, $filePath)
    {
        if ($gabaritId) {
            $gabarit        = $this->gabaritManager->getGabarit($gabaritId);
            $gabaritBlocs   = $this->gabaritManager->getBlocs($gabarit);
            $ext            = pathinfo($filePath, PATHINFO_EXTENSION);
            $miniatureDir   = pathinfo($filePath, PATHINFO_DIRNAME);
            $miniatureName  = pathinfo($filePath, PATHINFO_BASENAME);
            $miniatureSizes = array();

            // Parcours des champs du gabarit
            foreach ($gabarit->getChamps() as $champsGroupe) {
                foreach ($champsGroupe as $champ) {
                    if ($champ['type'] == 'FILE'
                        && isset($champ['params']['MINIATURE'])
                        && $champ['params']['MINIATURE'] != ''
                    ) {
                        $miniatureSizes = array_merge(
                            $miniatureSizes,
                            explode(';', $champ['params']['MINIATURE'])
                        );
                    }
                }
            }

            // Parcours des champs des blocs du gabarit
            foreach ($gabaritBlocs as $gabaritBloc) {
                foreach ($gabaritBloc->getGabarit()->getChamps() as $champ) {
                    if ($champ['type'] == 'FILE'
                        && isset($champ['params']['MINIATURE'])
                        && $champ['params']['MINIATURE'] != ''
                    ) {
                        $miniatureSizes = array_merge(
                            $miniatureSizes,
                            explode(';', $champ['params']['MINIATURE'])
                        );
                    }
                }
            }

            $miniatureSizes = array_unique($miniatureSizes);

            foreach ($miniatureSizes as $size) {
                list($maxWidth, $maxHeight) = explode('x', $size);

                $sizeDirectory = str_replace('*', '', $size);
                if (!file_exists($miniatureDir . DS . $sizeDirectory)) {
                    $this->fileManager->createFolder($miniatureDir . DS . $sizeDirectory);
                }

                $miniaturePath  = $miniatureDir . DS . $sizeDirectory . DS
                                . $miniatureName;

                $this->fileManager->vignette(
                    $filePath,
                    $ext,
                    $miniaturePath,
                    $maxWidth,
                    $maxHeight
                );
            }
        }
    }
}
