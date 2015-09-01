<?php

namespace Solire\Back\Controller;

use Solire\Lib\DB;
use Solire\Lib\Format\String;
use Solire\Lib\FrontController;
use Solire\Trieur\Trieur;
use Solire\Conf\Loader as ConfLoader;
use Doctrine\DBAL\DriverManager;

/**
 * Contrôleur qui permet de gérer les tableaux de données interactifs (datatable)
 *
 * @author  Stéphane Monnot <smonnot@solire.fr>
 * @license CC by-nc        http://creativecommons.org/licenses/by-nc/3.0/fr/
 */
class Datatable extends Main
{
    /**
     * Action affichant le tableau
     *
     * @return void
     */
    public function listAction()
    {
        $this->view->name = isset($_GET['name']) ? $_GET['name'] : null;
    }

    /**
     * Action permettant de renvoyer la configuration de la liste
     *
     * @return void
     */
    public function listconfigAction()
    {
        $this->view->enable(false);

        $configName = filter_var($_GET['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        // Defining the trieur configuration
        $trieurConfigPath = FrontController::search('config/' . $configName . '.yml');
        if ($trieurConfigPath === false) {
            $trieurConfigPath = FrontController::search('config/datatable/' . $configName . '.yml');
        }
        $trieurConfig     = ConfLoader::load($trieurConfigPath);

        $trieur = new Trieur($trieurConfig);

        $jsConfig = $trieur->getDriver()->getJsConfig();
        $jsColumnFilterConfig = $trieur->getDriver()->getColumnFilterConfig();

        header('Content-type: application/json');
        echo json_encode([
            'config' => $jsConfig,
            'columnFilterConfig' => $jsColumnFilterConfig,
        ]);
    }

    /**
     * Action renvoyant les données
     *
     * @return void
     */
    public function listdataAction()
    {
        $this->view->enable(false);

        $configName = filter_var($_GET['name'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);

        // Defining the trieur configuration
        $trieurConfigPath = FrontController::search('config/' . $configName . '.yml');
        if ($trieurConfigPath === false) {
            $trieurConfigPath = FrontController::search('config/datatable/' . $configName . '.yml');
        }
        $trieurConfig     = ConfLoader::load($trieurConfigPath);

        $configDb = $this->envConfig->get('database');
        $configDb['driverOptions'] = [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
        ];
        $configDb['driver'] = 'pdo_mysql';
        $doctrineConnection = DriverManager::getConnection((array) $configDb);
        $trieur = new Trieur($trieurConfig, $doctrineConnection);
        $trieur->setRequest($_POST);
        $response = $trieur->getResponse();
        $response['debug'] = $trieur->getSource()->getDataQuery()->getSQL();

        header('Content-type: application/json');
        echo json_encode($response);
    }
}
