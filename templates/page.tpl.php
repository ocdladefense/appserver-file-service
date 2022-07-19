

<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">


    <?php
        component("DocumentsComponent", array(
            "id"        => "shared-with-me",
            "entity-data"   => $entityData
        ));
    ?>

    
</div>