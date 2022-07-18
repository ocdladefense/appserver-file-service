

<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">



    <?php //print $myDocs; ?>
    <?php /** Convert $myDocs html to a component call.**/?>
    <?php 
        component("DocumentsComponent", "my-documents", array("targetObjectIds" => []));
    ?>
    
    
    <?php //print $sharedWithMeDocs; ?>
    <?php /** Convert $sharedWithMeDocs html to a component call.**/?>
    <?php 
        component("DocumentsComponent", "shared-with-me", array("targetObjectIds" => []));
    ?>

</div>