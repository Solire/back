<?php
/**
 * Contrôleur des medias
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

use Solire\Lib\Format\String;
use Solire\Lib\Model\FileManager;
use Solire\Lib\Path;
use Solire\Lib\Registry;
use Solire\Lib\Tools;

/**
 * Contrôleur des medias
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
        $this->view->breadCrumbs[] = [
            'title' => 'Gestion des fichiers',
            'url' => '',
        ];
    }

    /**
     * Affiche la liste des fichiers
     *
     * @return void
     */
    public function listAction()
    {
        $this->view->unsetMain();
        $this->files = [];

        /** Permet plusieurs liste de fichier dans la meme page **/
        $this->view->idFilesList = null;
        if (isset($_REQUEST['id'])) {
            $this->view->idFilesList = '_' . $_REQUEST['id'];
        }

        $this->view->prefixFileUrl = null;
        if ($this->mainConfig->get('upload', 'prefixUrl')) {
            $this->view->prefixFileUrl = $this->mainConfig->get('upload', 'prefixUrl') . '/';
        }

        if (isset($_REQUEST['prefix_url'])) {
            $this->view->prefixFileUrl = $_REQUEST['prefix_url'] . '/';
        }

        $gabPageId = isset($_REQUEST['id_gab_page']) && $_REQUEST['id_gab_page'] ? $_REQUEST['id_gab_page'] : 0;

        if ($gabPageId) {
            $search = isset($_REQUEST['search']) ? $_REQUEST['search'] : '';
            $orderby = isset($_REQUEST['orderby']['champ']) ? $_REQUEST['orderby']['champ'] : '';
            $sens = isset($_REQUEST['orderby']['sens']) ? $_REQUEST['orderby']['sens'] : '';

            $this->page = $this->gabaritManager->getPage(BACK_ID_VERSION, BACK_ID_API, $gabPageId);

            $this->files = $this->fileManager->getList($this->page->getMeta('id'), 0, $search, $orderby, $sens);
        }

        $this->view->files = [];
        foreach ($this->files as $file) {
            $ext = strtolower(array_pop(explode('.', $file['rewriting'])));
            $file['path'] = $this->view->prefixFileUrl . $file['id_gab_page'] . Path::DS . $file['rewriting'];

            $serverpath = $this->upload_path . Path::DS . $file['id_gab_page']
                        . Path::DS . $file['rewriting'];

            if (!file_exists($serverpath)) {
                continue;
            }

            $file['class'] = 'hoverprevisu vignette';

            if (array_key_exists($ext, FileManager::$extensions['image'])) {
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
            $file['poids'] = Tools::formatTaille($file['taille']);

            $this->view->files[] = $file;
        }
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

        $nodes = [];

        if ($_REQUEST['id'] === '') {
            $nodes[] = [
                'id'       => 'node_0',
                'text'     => 'Ressources',
                'children' => true,
                'icon'     => 'fa fa-folder',
            ];
        } else {
            $rubriques = $this->gabaritManager->getList(BACK_ID_VERSION, $this->api['id'], (int) $_REQUEST['id']);
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

                /*
                 * On recupere les enfants
                 */
                $children = $this->gabaritManager->getList(
                    BACK_ID_VERSION,
                    $this->api['id'],
                    (int) $rubrique->getMeta('id')
                );

                $title = $rubrique->getMeta('titre');
                $node = [
                    'id'       => 'node_' . $rubrique->getMeta('id'),
                    'text'     => $title . (count($children) > 0 ? ' (' . count($children) . ')' : ''),
                    'rel'      => 'category',
                    'icon'     => count($children) > 0 ? 'fa fa-folder' : 'fa fa-file-text',
                    'children' => count($children) > 0 ? true : false
                ];
                $nodes[] = $node;
            }
        }

        echo json_encode($nodes);
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

        if ($this->mainConfig->get('upload', 'prefixUrl')) {
            $this->view->prefixFileUrl = $this->mainConfig->get('upload', 'prefixUrl') . '/';
        }

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
            $gabaritId = (int) $_REQUEST['gabaritId'];
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

            $response['size']  = Tools::formatTaille($response['size']);
            $response['value'] = $response['filename'];

            if (isset($response['mini_path'])) {
                $response['mini_path'] = $this->view->prefixFileUrl
                                   . $response['mini_path'];
                $response['mini_url'] = $this->view->prefixFileUrl
                                  . $response['mini_url'];
                $response['vignette'] = $response['mini_url'];
                $response['image'] = [
                    'url' => $this->view->prefixFileUrl . $id_gab_page
                             . '/' . $response['filename']
                ];

                // Génération de miniatures additionnelles
                $filePath = $this->view->prefixFileUrl . $response['path'];
                $this->miniatureProcess($gabaritId, $filePath);
            }

            $response['url']       = $this->view->prefixFileUrl . $response['url'];
            $response['isImage']   = FileManager::isImage($response['filename']) !== false;

            if (isset($response['minipath'])) {
                $response['minipath'] = $this->view->prefixFileUrl
                                  . $response['minipath'];
                $response['image'] = [
                    'url' => $this->view->prefixFileUrl . $id_gab_page
                             . '/' . $response['filename']
                ];
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
                    $response['image'] = [
                        'url' => $this->view->prefixFileUrl . $id_gab_page . Path::DS . $response['filename']
                    ];

                    // Génération de miniatures additionnelles
                    $filePath = $this->view->prefixFileUrl . $response['path'];
                    $this->miniatureProcess($gabaritId, $filePath);

                }
                $response['path'] = $this->view->prefixFileUrl . $response['path'];
                $response['url'] = $this->view->prefixFileUrl . $response['url'];
                $response['size'] = Tools::formatTaille($response['size']);
                $response['id_temp'] = $id_temp;
                $response['isImage'] = FileManager::isImage($response['filename']) !== false;
            }
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
        $gabaritId = 0;
        if (isset($_REQUEST['gabaritId'])) {
            $gabaritId = (int) $_REQUEST['gabaritId'];
        }

        $this->view->prefixFileUrl = null;

        if ($this->mainConfig->get('upload', 'prefixUrl')) {
            $this->view->prefixFileUrl = $this->mainConfig->get('upload', 'prefixUrl') . '/';
        }

        if (isset($_REQUEST['prefix_url'])) {
            $this->view->prefixFileUrl = $_REQUEST['prefix_url'] . DIRECTORY_SEPARATOR;
        }

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
        $newImageName   = String::urlSlug(
            pathinfo($_POST['dest'], PATHINFO_FILENAME),
            '-',
            255
        );
        $filepath       = preg_replace('#^' . preg_quote($this->view->prefixFileUrl) . '#', '', $_POST['src']);
        $filename       = pathinfo($filepath, PATHINFO_BASENAME);
        $ext            = pathinfo($filename, PATHINFO_EXTENSION);
        $id_temp        = null;

        if ($id_gab_page) {
            /** Cas d'une édition de page */

            $targetDir      = $id_gab_page;
            $vignetteDir    = $id_gab_page . Path::DS . $this->upload_vignette;
            $apercuDir      = $id_gab_page . Path::DS . $this->upload_apercu;
        } elseif (isset($_COOKIE['id_temp'])
            && $_COOKIE['id_temp']
            && is_numeric($_COOKIE['id_temp'])
        ) {
            /** Cas d'une création de page */

            $id_temp = (int) $_COOKIE['id_temp'];
            $target = 'temp-' . $id_temp;

//            $targetTmp      = $this->upload_temp;
            $targetDir      = $target;
            $vignetteDir    = $target . Path::DS . $this->upload_vignette;
            $apercuDir      = $target . Path::DS . $this->upload_apercu;
        } else {
            exit();
        }

        $count_temp = 1;
        $target     = $newImageName . '.' . $ext;
        while (file_exists($this->upload_path . Path::DS . $targetDir . Path::DS . $target)) {
            $count_temp++;
            $target = $newImageName . '-' . $count_temp . '.' . $ext;
        }

        $targetWidth = false;
        $targetHeight = false;
        if (isset($_POST['force-width'])) {
            switch ($_POST['force-width']) {
                case 'width':
                    $targetWidth = $_POST['minwidth'];
                    $targetHeight = ($_POST['minwidth'] / $w) * $h;
                    break;

                case 'height':
                    $targetHeight = $_POST['minheight'];
                    $targetWidth = ($_POST['minheight'] / $h) * $w;
                    break;

                case 'width-height':
                    $targetWidth = $_POST['minwidth'];
                    $targetHeight = $_POST['minheight'];
                    break;
            }
        }


        if (intval($targetWidth) <= 0) {
            $targetWidth = false;
        }

        if (intval($targetHeight) <= 0) {
            $targetHeight = false;
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
                $targetWidth,
                $targetHeight
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
                $targetWidth,
                $targetHeight
            );

            if (isset($response['minipath'])) {
                $response['size']     = Tools::formatTaille($response['size']);
                $response['id_temp']  = $id_temp;
            }
        }

        $response = [];
        $response['url']            = $this->view->prefixFileUrl . $targetDir . Path::DS . $target;
        $response['path']           = $this->view->prefixFileUrl . $targetDir . Path::DS . $target;
        $response['filename']       = $target;
        $response['filename_front'] = $this->view->prefixFileUrl . $targetDir . '/' . $target;

        if (FileManager::isImage($response['filename'])) {
            $vignette   = $this->view->prefixFileUrl
                        . $targetDir . Path::DS
                        . $this->upload_vignette . Path::DS
                        . $response['filename'];
            $serverpath = $this->upload_path . Path::DS
                        . $targetDir . Path::DS
                        . $response['filename'];

            $sizes = getimagesize($serverpath);
            $size = $sizes[0] . ' x ' . $sizes[1];
            $response['vignette'] = $vignette;
            $response['label'] = $response['filename'];
            $response['size'] = $size;
            $response['value'] = $response['filename'];
            $response['utilise'] = 1;
            $response['isImage'] = 1;

            $filePath = $this->view->prefixFileUrl . $this->upload_path . Path::DS . $response['path'];
            $this->miniatureProcess($gabaritId, $filePath);
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
        $mediaFileId = 0;
        if (isset($_COOKIE['id_media_fichier'])) {
            $mediaFileId = $_COOKIE['id_media_fichier'];
        } elseif (isset($_REQUEST['id_media_fichier'])) {
            $mediaFileId = $_REQUEST['id_media_fichier'];
        }

        $query = 'UPDATE `' . $this->mediaTableName . '` SET '
               . '`suppr` = NOW() '
               . 'WHERE `id` = ' . $mediaFileId;
        $success = $this->db->exec($query);

        if (!$success) {
            $this->userLogger->addError(
                'Suppression de fichier échouée',
                [
                    'user' => [
                        'id'    => $this->utilisateur->id,
                        'login' => $this->utilisateur->login,
                    ],
                    'file' => [
                        'id'    => $mediaFileId,
                        'table' => $this->mediaTableName,
                    ]
                ]
            );
        } else {
            $this->userLogger->addInfo(
                'Suppression de fichier réussie',
                [
                    'user' => [
                        'id'    => $this->utilisateur->id,
                        'login' => $this->utilisateur->login,
                    ],
                    'file' => [
                        'id'    => $mediaFileId,
                        'table' => $this->mediaTableName,
                    ]
                ]
            );
        }

        $response = [
            'status' => $status
        ];

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

        $json = [];
        $items = [];

        $term = isset($_GET['term']) ? $_GET['term'] : '';
        $tinyMCE = isset($_GET['tinyMCE']);

        if ($id_gab_page || $id_temp) {
            $files = $this->fileManager->getSearch(
                $term,
                $id_gab_page,
                $id_temp,
                $extensions
            );

            $dir = 'temp-' . $id_temp;
            if ($id_gab_page) {
                $dir = $id_gab_page;
            }

            $prefixFileUrl = null;

            if ($this->mainConfig->get('upload', 'prefixUrl')) {
                $prefixFileUrl = $this->mainConfig->get('upload', 'prefixUrl') . '/';
            }

            if (isset($_REQUEST['prefix_url'])) {
                $prefixFileUrl = $_REQUEST['prefix_url'] . '/';
            }

            foreach ($files as $file) {
                if (!$tinyMCE || FileManager::isImage($file['rewriting'])) {
                    $url = $dir . '/' . $file['rewriting'];
                    $vignette = $dir . '/'
                              . $this->upload_vignette . '/'
                              . $file['rewriting'];
                    $serverpath = $this->upload_path . Path::DS
                                . $dir . Path::DS
                                . $file['rewriting'];

                    if (!file_exists($serverpath)) {
                        continue;
                    }

                    $absUrl = Registry::get('basehref') . $url;
                    if (FileManager::isImage($file['rewriting'])) {
                        $sizes = getimagesize($serverpath);
                        $size = $sizes[0] . ' x ' . $sizes[1];
                    } else {
                        $size = '';
                    }

                    if ($tinyMCE) {
                        $items[] = [
                            'title' => $file['rewriting'] . ($size ? ' (' . $size . ')' : ''),
                            'value' => $absUrl,
                        ];
                    } else {
                        $items[] = [
                            'url'      => $prefixFileUrl . $url,
                            'path'     => $prefixFileUrl . $url,
                            'vignette' => $prefixFileUrl . $vignette,
                            'isImage'  => FileManager::isImage($file['rewriting']) !== false,
                            'label'    => $file['rewriting'],
                            'utilise'  => $file['utilise'],
                            'size'     => ($size ? $size : ''),
                            'value'    => $file['rewriting'],
                            'text'     => $file['rewriting'],
                            'id'       => $file['rewriting'],
                        ];
                    }
                }
            }
        }

        $json = [
            'items' => $items,
        ];

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
            $miniatureSizes = [];

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
                if (!file_exists($miniatureDir . Path::DS . $sizeDirectory)) {
                    $this->fileManager->createFolder(
                        $miniatureDir . Path::DS . $sizeDirectory
                    );
                }

                $miniaturePath  = $miniatureDir . Path::DS
                                . $sizeDirectory . Path::DS
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
