# Créer un nouveau HTML field

1. créer un fichier PHP dans le dossier html_fields portant le nom du custom HTML field, par exemple `my_field.php`
2. dans ce fichier créer une fonction portant le nom "render_HTML_{nom du field}", par exemple `render_HTML_my_field` (s'inspirer des autres fichiers pour avoir une structure similaire)
3. la fonction ainsi créée doit prendre en arguments 2 paramètres : 
    * le premier est l'objet `$field` qui contient les paramètres du champs 
    * le deuxième est l'objet `$options` qui contient les options de rendu HTML du field

# Conventions pour créer de nouveaux html_fields

* Il faut créer les fields pour le framework Bootstrap (voir la doc : https://getbootstrap.com/docs/4.0/components/forms/)
* $options a souvent l'option `$options['style'] = 'simple'` qui permet de renvoyer l'élément HTML simple sans label ou wrapper <div> ou autre
* Sauf dans le cas où style='simple' (cf point précédent), chaque champs est 
    * entouré/wrappé par un <div class="form-group">
    * dans ce wrapper il y a un élément `<small>`  pour afficher un petit message informatif/explicatif pour remplir le champs en question
    * il y a aussi un élément `<label>` pour afficher le titre du champs
    * il y a aussi un élément `<` 
* on peut ajouter une fonction `get_value_from_db_{nom_du_field}` pour récupérer les valeurs de champs complexes depuis la DB
* on peut ajouter une fonction `save_field_to_db_{nom_du_field}` pour sauver les valeurs de champs complexes dans la DB

