<?php


define( 'CCN_LIBRARY_PLUGIN_DIR', '..' );
require_once(CCN_LIBRARY_PLUGIN_DIR . '/tests/test.php');
require_once(CCN_LIBRARY_PLUGIN_DIR . '/forms/lib.forms.php'); use \ccn\lib\html_fields as fields;

$prefix = 'test';
$steps = array(
    array(
        'id' => 'je-suis',
        'title' => 'Présentation',
        'fields' => array(
            $prefix.'_key_jesuis', 
            $prefix.'key_ma_paroisse',
            $prefix.'_key_persontype'
        ),
    ),
    array(
        'id' => 'infos-personnelles',
        'title' => 'Informations personnelles',
        'switch' => array(
            array(
                'id' => 'infos-personnelles-individuel',
                'condition' => '{{'.$prefix.'_key_persontype}} == "individuel" || {{'.$prefix.'_key_persontype}} == "parent_seul"',
                'title' => 'Informations personnelles',
                'fields' => array($prefix.'_key_indiv', $prefix.'_key_genre', $prefix.'_key_birthdate', $prefix.'_key_email'),
            ),
            array(
                'id' => 'infos-personnelles-adresse',
                'fields' => array($prefix.'_key_address'),
            ),
            array(
                'id' => 'infos-personnelles-couple',
                'condition' => '{{'.$prefix.'_key_persontype}} == "famille" || {{'.$prefix.'_key_persontype}} == "couple_sans_enfants"',
                'title' => 'Informations du couple',
                'fields' => array(
                            $prefix.'_key_indiv_lui', $prefix.'_key_birthdate_lui', $prefix.'_key_email_lui',
                            $prefix.'_key_indiv_elle', $prefix.'_key_birthdate_elle', $prefix.'_key_email_elle'
                ),
            ),
            array(
                'id' => 'infos-enfants',
                'condition' => '{{'.$prefix.'_key_persontype}} == "famille" || {{'.$prefix.'_key_persontype}} == "parent_seul"',
                'fields' => array($prefix.'_childrenGR'),
            ),
        ),
    ),
    array(
        'id' => 'logement-transport',
        'title' => 'Logement & transport',
        'fields' => array(
            $prefix.'_key_logement', $prefix.'_key_logement_remarques',
            $prefix.'_key_moyen_transport_aller', $prefix.'_date_aller', $prefix.'_gare_aller',
            $prefix.'_key_moyen_transport_retour', $prefix.'_date_retour', $prefix.'_gare_retour'
        ),
        'field_conditions' => array(
            $prefix.'_date_aller' => '{{'.$prefix.'_key_moyen_transport_aller}} != "ne_sais_pas"',
            $prefix.'_gare_aller' => '{{'.$prefix.'_key_moyen_transport_aller}} == "avion" || {{'.$prefix.'_key_moyen_transport_aller}} == "train"',
            $prefix.'_date_retour' => '{{'.$prefix.'_key_moyen_transport_retour}} != "ne_sais_pas"',
            $prefix.'_gare_retour' => '{{'.$prefix.'_key_moyen_transport_retour}} == "avion" || {{'.$prefix.'_key_moyen_transport_retour}} == "train"',
        ),
    ),
    array(
        'id' => 'paiement',
        'title' => 'Confirmation',
        'fields' => array($prefix.'_paiement_modalite', $prefix.'_paiement_moyen', $prefix.'_html_paiement_description'),
        'field_conditions' => array(
            $prefix.'_paiement_moyen' => '{{'.$prefix.'_paiement_modalite}} == "now_all" || {{'.$prefix.'_paiement_modalite}} == "now_partial"',
            $prefix.'_html_paiement_description' => '{{'.$prefix.'_paiement_moyen}} == "cheque"',
        ),
    ),
);

function test_field_is_required() {
    global $steps;
    $field_values = [
        'test_key_persontype' => 'famille',
        'test_key_moyen_transport_aller' => 'avion',
    ];
    $res = [];
    $res[] = fields\field_is_required('test_key_email_elle', true, $steps, $field_values);
    $res[] = fields\field_is_required('test_key_email_elle', true, $steps, ['test_key_persontype' => 'individuel']);
    echo json_encode($res);
}
test_field_is_required();

function test_build_html2() {
    $prefix = 'ccnbtc';
    // Here we test the build of an HTML table when sending HTML emails with form data
    $fields = array(
        array( // Je suis (paroissien, communautaire, ...)
            'id' => $prefix.'_key_jesuis',
            'description'  => "D'où vient la personne (paroisse, communautaire, ...)",
            'html_label' => 'Je suis',
            'type' => "radio",
            'options' => array(
                'paroisse' => "Membre d'une paroisse (préciser)",
                'frat_paroissiale' => "Membre des Fraternités Paroissiales Missionnaires du Chemin-Neuf",
                'communautaire' => "Membre de la Communauté ou de la Communion du Chemin Neuf",
            ),
            //'options_preciser' => ['autre*', 'paroisse*'], // * veut dire que c'est requis
            'wrapper' => [
                'start' => '<p class="form-label">Je suis</p>',
                'end' => ''
            ],
        ),
        array(
            'id' => $prefix.'key_ma_paroisse',
            'html_label' => 'Ma paroisse',
            'type' => 'text',
        ),
        array( // Je viens comme (couple, famille, ...)
            'id' => $prefix.'_key_persontype',
            'description'  => "Le type de personne (individuel, couple, famille, ...)",
            'html_label' => 'Je viens comme',
            'type' => "dropdown",
            'options' => array(
                'individuel' => "Individuel",
                'couple_sans_enfants' => "Couple sans enfants",
                'famille' => "Famille",
                'parent_seul' => 'Parent seul avec enfants',
            ),
        ),
        // SPECIFIQUE CAS 1 - individuel ou parent_seul
        array( // Nom et Prénom
            'id' => $prefix.'_key_indiv', // le nom de la meta key (sera complété par _firstname et _name)
            'description'  => "Person first name and name for inscription",
            'html_label' => array(
                'prenom' => 'Prénom',
                'nom' => 'Nom'
            ),
            'type' => "nom_prenom",
        ),
        array( // Nom prénom - Elle
            'id' => $prefix.'_key_indiv_elle', 
            'copy' => $prefix.'_key_indiv', 
            'required' => [true, false],
            'html_label' => array('prenom' => 'Prénom', 'nom' => 'Nom si différent'), 
            'wrapper' => array('start' => '<p class="form-label">Elle</p>', 'end' => '')), // une copie de Nom prénom pour "Elle"
        array('id' => $prefix.'_key_indiv_lui', 'copy' => $prefix.'_key_indiv', 'wrapper' => array('start' => '<p class="form-label">Lui</p>', 'end' => '')), // une copie de Nom prénom pour "Lui"
        array( // Homme/Femme
            'id' => $prefix.'_key_genre',
            'description'  => "Person gender for inscription",
            'html_label' => 'Genre',
            'type' => "radio",
            'options' => array(
                'homme' => 'Homme',
                'femme' => 'Femme',
            ),
            'layout' => 'row',
        ),
        array( // Date de naissance
            'id' => $prefix.'_key_birthdate',
            'description'  => "Person birth date",
            'html_label' => 'Date de naissance',
            'type' => "date", // TODO restreindre aux personnes majeures
            'label' => 'placeholder',
            'wrapper' => 'bootstrap',
        ),
        array('id' => $prefix.'_key_birthdate_elle', 'copy' => $prefix.'_key_birthdate'), // une copie de birth date pour "Elle"
        array('id' => $prefix.'_key_birthdate_lui', 'copy' => $prefix.'_key_birthdate'), // une copie de birth date pour "Lui"
        array( // Email
            'id' => $prefix.'_key_email',
            'description'  => "Person email address",
            'html_label' => 'Email',
            'type' => "email",
            'label' => 'placeholder',
            'wrapper' => 'bootstrap',
        ),
        array('id' => $prefix.'_key_email_elle', 'copy' => $prefix.'_key_email'), // une copie de birth date pour "Elle"
        array('id' => $prefix.'_key_email_lui', 'copy' => $prefix.'_key_email'), // une copie de birth date pour "Elle"
        array( // Adresse
            'id' => $prefix.'_key_address',
            'description'  => "Person postal address",
            'html_label' => array(
                'street' => 'Rue',
                'postalcode' => 'Code postal',
                'city' => 'Ville'
            ),
            'type' => "address",
            'label' => 'placeholder',
            'wrapper' => array('start' => '<p class="form-label">Adresse</p>', 'end' => ''),
        ),
        array( // Repeat group children
            'type' => 'REPEAT-GROUP',
            'id' => $prefix.'_childrenGR',
            'fields' => array(
                array( // Nom et Prénom
                    'id' => $prefix.'_key_child', // le nom de la meta key (sera complété par _firstname et _name)
                    'description'  => "Child first name and name for inscription",
                    'html_label' => array(
                        'prenom' => 'Prénom',
                        'nom' => 'Nom (si différent)'
                    ),
                    'type' => "nom_prenom",
                    'required' => [true, false],
                ),
                array( // Date de naissance
                    'id' => $prefix.'_child_birthdate',
                    'description'  => "Child birth date",
                    'html_label' => 'Date de naissance',
                    'type' => "date",
                    'label' => 'placeholder',
                    'wrapper' => 'bootstrap',
                ),
                array( // Homme/Femme
                    'id' => $prefix.'_child_genre',
                    'description'  => "Child gender for inscription",
                    'html_label' => 'Genre',
                    'type' => "dropdown",
                    'options' => array(
                        'homme' => 'Garçon',
                        'femme' => 'Fille',
                    ),
                    'layout' => 'row',
                ),
            ),
            'wrapper' => ['start' => '<p class="form-label">Enfants</p>', 'end' => ''],
        ),
        array( // Logement
            'id' => $prefix.'_key_logement',
            'description'  => "Le type de logement",
            'html_label' => 'Logement',
            'type' => "radio",
            'options' => array(
                'tente_perso' => "Tente personnelle",
                'caravane_perso' => "Caravane personnelle",
                'camping_car_perso' => "Camping-car personnel",
                'tente_co' => 'Tente de la Communauté',
                'autre' => 'Autre (Je me loge par mes propres moyens)'
            ),
            'options_preciser' => array('autre*'),
            'wrapper' => array('start' => '<p class="form-label">Logement</p>', 'end' => ''),
        ),
        array( // Logement remarques
            'id' => $prefix.'_key_logement_remarques',
            'type' => 'textarea',
            'required' => false,
            'html_label' => 'Remarques',
            'description' => 'Remarques sur le logement',
        ),
        array( // moyen de transport aller
            'id' => $prefix.'_key_moyen_transport_aller',
            'description'  => "Le moyen de transport à l'aller",
            'html_label' => 'Moyen de transport',
            'type' => "dropdown",
            'options' => array(
                'avion' => "Avion",
                'train' => "Train",
                'voiture' => "Voiture",
                'ne_sais_pas' => 'Ne sais pas encore',
            ),
            'wrapper' => array('start' => '<p class="form-label">Transport aller</p>', 'end' => ''),
        ),
        array( // moyen de transport retour
            'id' => $prefix.'_key_moyen_transport_retour',
            'description'  => "Le moyen de transport au retour",
            'html_label' => 'Moyen de transport',
            'type' => "dropdown",
            'options' => array(
                'avion' => "Avion",
                'train' => "Train",
                'voiture' => "Voiture",
                'ne_sais_pas' => 'Ne sais pas encore',
            ),
            'wrapper' => array('start' => '<p class="form-label">Transport retour</p>', 'end' => ''),
        ),
        array( // Date aller (si avion ou train)
            'id' => $prefix.'_date_aller',
            'description'  => "Date d'arrivée à l'aller",
            'html_label' => "Date d'arrivée",
            'type' => "date",
            'required' => false, // TODO faire mieux que ça : required uniquement si transport = avion ou train !
        ),
        array( // Date retour (si avion ou train)
            'id' => $prefix.'_date_retour',
            'description'  => "Date de départ",
            'html_label' => "Date de départ",
            'type' => "date",
            'required' => false, // TODO faire mieux que ça : required uniquement si transport = avion ou train !
        ),
        array( // Gare/aéroport aller
            'id' => $prefix.'_gare_aller',
            'html_label' => "Gare/aéroport d'arrivée",
            'type' => 'text',
            "required" => false,
        ),
        array( // Gare/aéroport retour
            'id' => $prefix.'_gare_retour',
            'html_label' => "Gare/aéroport de départ",
            'type' => 'text',
            "required" => false,
        ),
        array(
            'id' => $prefix.'_paiement_modalite',
            'html_label' => 'Je paye',
            "type" => 'dropdown',
            "options" => array(
                "now_all" => "maintenant la totalité",
                "now_partial" => "maintenant une partie",
                "on_site" => "sur place",
            ),
            "wrapper" => array('start' => '<p class="form-label">Je paye</p>', 'end' => ''),
        ),
        array(
            'id' => $prefix.'_paiement_moyen',
            'type' => 'radio',
            'html_label' => 'Moyen de paiement',
            'options' => array(
                'cb' => 'Carte Bleue (disponible prochainement)',
                'cheque' => 'Chèque',
            ),
            "wrapper" => array('start' => '<p class="form-label">Moyen de paiement</p>', 'end' => ''),
        ),
        array(
            'id' => $prefix.'_html_paiement_description',
            'type' => 'html',
            'html' => '<p class="form-description">
                Chèque à l\'ordre de la <u>Communauté du Chemin Neuf</u>.
                </p>
                <p class="form-description">
                    <p class="form-description">À envoyer à l\'adresse suivante :</p>
                    <p class="form-description bg-green p-2 txt-white rounded"><b>Secrétariat Festival Be The Church</b><br>
                    Abbaye d\'Hautecombe<br>3700 route de l\'Abbaye<br>73310 ST PIERRE DE CURTILLE</p>
                </p>',
        ),
    );
    $steps = array(
        array(
            'id' => 'je-suis',
            'title' => 'Présentation',
            'fields' => array(
                $prefix.'_key_jesuis', 
                $prefix.'key_ma_paroisse',
                $prefix.'_key_persontype'
            ),
        ),
        array(
            'id' => 'infos-personnelles',
            'title' => 'Informations personnelles',
            'switch' => array(
                array(
                    'id' => 'infos-personnelles-individuel',
                    'condition' => '{{'.$prefix.'_key_persontype}} == "individuel" || {{'.$prefix.'_key_persontype}} == "parent_seul"',
                    'title' => 'Informations personnelles',
                    'fields' => array($prefix.'_key_indiv', $prefix.'_key_genre', $prefix.'_key_birthdate', $prefix.'_key_email'),
                ),
                array(
                    'id' => 'infos-personnelles-adresse',
                    'fields' => array($prefix.'_key_address'),
                ),
                array(
                    'id' => 'infos-personnelles-couple',
                    'condition' => '{{'.$prefix.'_key_persontype}} == "famille" || {{'.$prefix.'_key_persontype}} == "couple_sans_enfants"',
                    'title' => 'Informations du couple',
                    'fields' => array(
                                $prefix.'_key_indiv_lui', $prefix.'_key_birthdate_lui', $prefix.'_key_email_lui',
                                $prefix.'_key_indiv_elle', $prefix.'_key_birthdate_elle', $prefix.'_key_email_elle'
                    ),
                ),
                array(
                    'id' => 'infos-enfants',
                    'condition' => '{{'.$prefix.'_key_persontype}} == "famille" || {{'.$prefix.'_key_persontype}} == "parent_seul"',
                    'fields' => array($prefix.'_childrenGR'),
                ),
            ),
        ),
    );
    $form_data = json_decode('{"ccnbtc_key_jesuis":"frat_paroissiale","ccnbtckey_ma_paroisse":"tpkymiw","ccnbtc_key_persontype":"famille","ccnbtc_key_indiv_firstname":"Clark","ccnbtc_key_indiv_name":"Kent","ccnbtc_key_indiv_elle_firstname":"Helen","ccnbtc_key_indiv_elle_name":"","ccnbtc_key_indiv_lui_firstname":"Bruce","ccnbtc_key_indiv_lui_name":"Stark","ccnbtc_key_genre":"homme","ccnbtc_key_birthdate":"09-02-1966","ccnbtc_key_birthdate_elle":"07-04-1931","ccnbtc_key_birthdate_lui":"01-07-1933","ccnbtc_key_email":"tony.stark@chemin-neuf.org","ccnbtc_key_email_elle":"bruce.wayne@chemin-neuf.org","ccnbtc_key_email_lui":"bruce.bergstein@wanadoo.fr","ccnbtc_childrenGR":"[{\"ccnbtc_key_child_firstname\":\"Bruce\",\"ccnbtc_key_child_name\":\"Stark\",\"ccnbtc_child_birthdate\":\"07-05-1926\",\"ccnbtc_child_genre\":\"homme\"},{\"ccnbtc_key_child_firstname\":\"Galadriel\",\"ccnbtc_key_child_name\":\"\",\"ccnbtc_child_birthdate\":\"09-02-1927\",\"ccnbtc_child_genre\":\"femme\"},{\"ccnbtc_key_child_firstname\":\"Bruce\",\"ccnbtc_key_child_name\":\"Stark\",\"ccnbtc_child_birthdate\":\"07-05-1926\",\"ccnbtc_child_genre\":\"homme\"}]","ccnbtc_key_logement":"camping_car_perso","ccnbtc_key_logement_remarques":"remarques diverses","ccnbtc_key_moyen_transport_aller":"train","ccnbtc_key_moyen_transport_retour":"voiture","ccnbtc_date_aller":"02-03-1929","ccnbtc_date_retour":"07-00-1955","ccnbtc_gare_aller":"hqlxhyi","ccnbtc_gare_retour":"islupqq","ccnbtc_paiement_modalite":"now_all","ccnbtc_paiement_moyen":"cheque","post_title":"Bruce & Helen Stark"}', true);

    $res = fields\build_html_from_form_data($form_data, $fields, $steps);
    file_put_contents('./lib.test_email_data_table.html', $res);
    //print_out($res);
}
//test_build_html2();

?>