<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">

    <div class="table">
        <div class="table-row first">
            <p class="table-header">Shared With</p>
            <p class="table-header">Title</p>
            <p class="table-header">Type</p>
            <p class="table-header">Size</p>
            <p class="table-header">Download</p>
            <p class="table-header">Delete</p>
        </div>



        <?php foreach($docs as $id => $doc): ?>

            <?php $sharedWith = $sharing[$id]["targetNames"]; ?>
            
            <div class="table-row data">
                <p class="table-cell"><?php print $sharedWith; ?></p>
                <p class="table-cell"><?php print $doc["Title"]; ?></p>
                <p class="table-cell"><?php print $doc["FileExtension"]; ?></p>
                <p class="table-cell"><?php print $doc["ContentSize"] . " kb"; ?></p>
                <p class="table-cell icon-cell">
                    <a href="/file/download/<?php print $id; ?>"><i class="fa-solid fa-download"></i></a>
                </p>
                
                <?php if($sharing[$id]["isOwner"]) : ?>
                    <p class="table-cell icon-cell">
                        <a href="/file/delete/<?php print $id; ?>" onClick="return confirm('Are you sure you want to delete this document?');">
                            <i class='fa fa-trash'></i>
                        </a>
                    </p>
                <?php else : ?>
                    <p class="table-cell icon-cell">--</p>
                <?php endif; ?>
            </div>
        
        <?php endforeach; ?>

    </div>
</div>


