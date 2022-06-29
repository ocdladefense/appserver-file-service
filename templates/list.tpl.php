<h1 class="list-header">Available Documents</h1>



<div class="table">
    <div class="table-row first">
        <p class="table-header">Shared With</p>
        <p class="table-header">Title</p>
        <p class="table-header">Type</p>
        <p class="table-header">Size</p>
    </div>



    <?php foreach($docs as $id => $doc): ?>

        <?php
           $sharedWith = $sharing[$id];
        ?>
        
        <div class="table-row data">
            <p class="table-cell"><?php print $sharedWith; ?></p>
            <p class="table-cell"><?php print $doc["Title"]; ?></p>
            <p class="table-cell"><?php print $doc["FileExtension"]; ?></p>
            <p class="table-cell"><?php print $doc["ContentSize"] . " kb"; ?></p>
            <p class="table-cell">
                <a href="/file/download/<?php print $id; ?>"><i class="fa-solid fa-download"></i></a>
            </p>
        </div>
    
    <?php endforeach; ?>

</div>


