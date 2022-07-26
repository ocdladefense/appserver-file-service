

<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">


    <!-- My Documents -->
    <?php

        $table = $tables[0];

        component("DocumentsComponent", $table);

    ?>


    <!-- Account Documents -->
    <?php
        $table = $tables[1];

        component("DocumentsComponent", $table);
    ?>


    <!-- All Committee Documents (i.e., webgov) -->
    <?php
        $table = $tables[2];

        component("DocumentsComponent", $table);
    ?> 
    

    
</div>