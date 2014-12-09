<?php
/**
 * Controleur du tableau de bord
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */

namespace Solire\Back\Controller;

/**
 * Controleur du tableau de bord
 *
 * @author  dev <dev@solire.fr>
 * @license CC by-nc http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Dashboard extends Main
{
    /**
     * Toujours executé avant l'action.
     *
     * @return void
     */
    public function start()
    {
        parent::start();
    }

    /**
     * Action par défaut
     *
     * @return void
     */
    public function startAction()
    {
        if (!isset($_GET['name'])) {
            $this->pageNotFound();
        }

        if (!is_array($_GET['name'])) {
            $configsName[] = $_GET['name'];
        } else {
            $configsName = $_GET['name'];
        }

        $this->view->datatableRender = '';

        foreach ($configsName as $configKey => $configName) {
            $datatableClassName = '\\App\\Back\\Datatable\\' . $configName;

            $configPath = \Solire\Lib\FrontController::search(
                'config/datatable/' . $configName . '.cfg.php'
            );

            if (!$configPath) {
                $this->pageNotFound();
            }

            $datatableClassName = 'Back\\Datatable\\' . $configName;
            $datatableClassName = \Solire\Lib\FrontController::searchClass(
                $datatableClassName
            );

            if ($datatableClassName === false) {
                $datatable = new \Solire\Lib\Datatable\Datatable(
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

            $datatable->start();
            $datatableString = $datatable;
            $data = $datatableString;

            if ($configKey == 0 &&
                (!isset($_GET['nomain']) || $_GET['nomain'] == 0)
            ) {
                /**
                 * On ajoute le chemin de fer
                 */

                $sBreadCrumbs = $this->buildBreadCrumbs(
                    $datatable->getBreadCrumbs()
                );
                $datatable->beforeHtml($sBreadCrumbs);
            }

            if (isset($_GET['json'])
                || (isset($_GET['nomain'])
                && $_GET['nomain'] == 1)
            ) {
                echo $data;
                exit();
            }

            $datatable = $data;
            $this->view->datatableRender .= $datatable;
            if (count($configsName) > 1) {
                $this->view->datatableRender .= '<hr />';
            }
        }
    }

    /**
     * Construction du fil d'ariane
     *
     * @param array $additionnalBreadCrumbs Tableau de fils d'ariane
     *
     * @return string Fil d'ariane au format HTML
     */
    private function buildBreadCrumbs($additionnalBreadCrumbs)
    {
        $this->view->breadCrumbs = array_merge(
            $this->view->breadCrumbs,
            $additionnalBreadCrumbs
        );
        ob_start();
        $this->view->add('breadcrumbs');
        $sBreadCrumbs = ob_get_clean();
        return $sBreadCrumbs;
    }
}
