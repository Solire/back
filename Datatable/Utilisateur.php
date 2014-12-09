<?php

namespace Solire\Back\Datatable;

class Utilisateur extends \Solire\Lib\Datatable\Datatable
{
    /**
     * Utilisateur courant
     *
     * @var \Solire\Lib\Session
     */
    protected $utilisateur;

    /**
     * Défini l'utilisateur
     *
     * @param utilisateur $utilisateur Utilisateur courant
     *
     * @return void
     */
    public function setUtilisateur($utilisateur)
    {
        $this->utilisateur = $utilisateur;
    }

    protected function beforeRunAction()
    {
        /**
         * Dans le cas d'un utilisateur de noveau solire, on affiche un select
         * pour choisir le niveau de l'utilisateur à créer
         */

        if ($this->utilisateur->getUser('niveau') == 'solire') {
            $niveaux = $this->db->getEnumValues('utilisateur', 'niveau');
            foreach ($niveaux as $niveau) {
                $options[] = array(
                    'value' => $niveau,
                    'text' => $niveau,
                );
            }

            $niveauKey = \Solire\Lib\Tools::multidimensionalSearch(
                    $this->config["columns"],
                    array("name" => "niveau")
            );

            $this->config["columns"][$niveauKey]["creable_field"] = array(
                "type" => "select",
                "options" => $options,
                'validate' => array(
                    'rules' => array(
                        "required" => true,
                    ),
                    'messages' => array(
                        "required" => "Ce champ est obligatoire.",
                    ),
                ),
            );
        }

        $showButton = '<a'
                    . ' href="' . $this->url . '&amp;dt_action=sendMail&amp;index=[#id#]"'
                    . ' title="Envoyer identifiant par email"'
                    . ' class="btn btn-success btn-small send-info-ajax">'
                    . '<img'
                    . ' width="12"'
                    . ' alt="Envoyer identifiant par email"'
                    . ' src="public/default/back/img/white/mail_16x12.png">'
                    . '</a>';
        array_unshift($this->columnActionButtons, $showButton);
        parent::beforeRunAction();
    }

    public function sendMailAction()
    {
        $idClient = intval($_GET['index']);
        $clientData = $this->db->query('
            SELECT utilisateur.*
            FROM utilisateur
            WHERE utilisateur.id = ' . $idClient)->fetch();
        $password = \Solire\Lib\Format\String::random(10);

        $mail = new \Solire\Lib\Mail('utilisateur_identifiant');
        $mail->setMainUse();
        $mail->to      = $clientData['email'];
        $mail->from    = 'contact@solire.fr';
        $mail->subject = 'Informations de connexion à l\'outil d\'administration'
                       . ' de votre site';

        $mail->urlAcces = \Solire\Lib\Registry::get("basehref") . 'back/';

        $clientData['pass'] = $password;
        $mail->clientData = $clientData;
        $mail->send();

        $passwordCrypt = \Solire\Lib\Session::prepareMdp($password);
        $values = array(
            "pass" => $passwordCrypt,
        );
        $this->db->update('utilisateur', $values, 'id = ' . $idClient);

        exit(json_encode(array('status' => 1)));
    }

    public function afterAddAction($insertId)
    {

        if ($this->utilisateur->getUser('niveau') != 'solire') {
            $niveau = 'redacteur';
            $query  = 'UPDATE utilisateur SET'
                    . ' niveau = ' . $this->db->quote($niveau)
                    . ' WHERE id = ' . $insertId;
            $this->db->exec($query);
        }


    }
}

