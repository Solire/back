<?php
$config = array(
    'plugins'   =>  array(
        "ShinForm",
    ),
    "additional_script" =>  array(
        "back/js/datatable/utilisateur.js",
    ),
    'table' => array(
        'title' => 'Liste des utilisateurs',
        'title_item' => 'utilisateur',
        'suffix_genre' => '',
        'fixedheader' => false,
        'detail' => false,
        'name' => 'utilisateur',
    ),
    'extra' => array(
        'copy' => false,
        'print' => false,
        'pdf' => false,
        'csv' => false,
        'hide_columns' => false,
        'highlightedSearch' => false,
        'creable' => true,
        'editable' => true,
        'deletable' => true,
    ),
    'style' => array(
        'form' => 'bootstrap',
    ),
    'columns' => array(
        array(
            'name' => 'id',
            'show' => false,
            'filter_field' => 'text',
            'title' => 'Id',
            'index' => true,
        ),
        array(
            'name' => 'civilite',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Civilite',
            'creable_field' => array(
                "type" => "select",
                "options" => array(
                    array(
                        "value" => "M.",
                        "text"  => "M.",
                    ),
                    array(
                        "value" => "Mme",
                        "text"  => "Mme",
                    ),
                ),
                'validate' => array(
                    'rules' => array(
                        "required" => true,
                    ),
                    'messages' => array(
                        "required" => "Ce champ est obligatoire.",
                    ),
                ),
            ),
        ),
        array(
            'name' => 'nom',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Nom',
            'creable_field' => array(
                "type" => "text",
                'validate' => array(
                    'rules' => array(
                        "required" => true,
                    ),
                    'messages' => array(
                        "required" => "Ce champ est obligatoire.",
                    ),
                ),
            ),
        ),
        array(
            'name' => 'prenom',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Prenom',
            'creable_field' => array(
                "type" => "text",
                'validate' => array(
                    'rules' => array(
                        "required" => true,
                    ),
                    'messages' => array(
                        "required" => "Ce champ est obligatoire.",
                    ),
                ),
            ),
        ),
        array(
            'name' => 'societe',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Societe',
            'creable_field' => array(
                "type" => "text",
            ),
        ),
        array(
            'name' => 'niveau',
            'show' => false,
            'filter_field' => 'text',
            'title' => 'Niveau',
            'creable_field' => array(
                "value" => "redacteur",
            ),
        ),
        array(
            'name' => 'email',
            "content"   =>  '<a href="mailto:[#THIS#]">[#THIS#]</a>',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Email',
            'creable_field' => array(
                "type" => "text",
                'validate' => array(
                    'rules' => array(
                        "required" => true,
                        "email" => true,
                    ),
                    'messages' => array(
                        "required" => "Saisissez un email.",
                        "email" => "Saisissez un email valide.",
                    ),
                ),
            ),
        ),
        array(
            'name' => 'pass',
            'show' => false,
            'filter_field' => 'text',
            'title' => 'Pass',
            'creable_field' => array(
                "type"       => "password",
                'validate' => array(
                    'rules' => array(
                        "required" => array(
                            'param' => true,
                            'depends' => array(
                                "form.this.add" => true
                            ),
                        ),
                        "minlength" => 5,
                    ),
                    'messages' => array(
                        "required" => "Ce champ est obligatoire.",
                        "minlength" => "Votre mot de passe doit contenir au moins 5 caractères.",
                    ),
                ),
            ),
        ),
        array(
            'name' => 'actif',
            'show' => true,
            'title' => 'Actif',
            "sql" => "IF(`utilisateur`.`actif` = 0, 'Non', 'Oui')",
            "filter_field" => "select",
            'creable_field' => array(
                "type" => "checkbox",
            ),
        ),
        array(
            'name' => 'date_crea',
            'show' => true,
            'filter_field' => 'text',
            'title' => 'Date de création',
            'format' => array(
                "dateTime::RelativeTime" => array(
                    "type"   => "RelativeTime",
                    "modeDate"  =>  true,
                ),
            ),
            'creable_field' => array(
                "value" => date('Y-m-d H:i:s'),
                "editable"  =>  false,
            ),
        ),
    ),
);