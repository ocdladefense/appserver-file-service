

<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">


    <!-- My Documents -->
    <?php
        foreach($entityData as $id => $title) {
            component("DocumentsComponent", array(
                "entity-data"   => [$id => $title],
                "title" => $title
            ));
        }
    ?>
<!-- 
    <?php
        // component("DocumentsComponent", array(
        //     "id"        => "shared-with-me",
        //     "entity-data"   => $entityData,
        //     "title" => $title
        // ));
    ?>


    <?php
        // component("DocumentsComponent", array(
        //     "id"        => "shared-with-me",
        //     "entity-data"   => $entityData,
        //     "title" => $title
        // ));
    ?> 
    

    
</div>