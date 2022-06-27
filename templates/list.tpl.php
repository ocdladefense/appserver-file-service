<h1 class="list-header">Available Documents</h1>



<div class="table">
    <div class="table-row first">
        <p class="table-header">Shared With</p>
        <p class="table-header">Title</p>
        <p class="table-header">Type</p>
        <p class="table-header">Size</p>
    </div>



    <?php foreach($grouped as $id => $doc) : ?>

        <?php
            $names = [];
            foreach($doc as $cd) $names[] = $cd["targetName"];
            $targetNames = implode(", ", $names);
        ?>

        <?php $info = $doc[0]["ContentDocument"]; ?>
        
        <div class="table-row data">
            <p class="table-cell"><?php print $targetNames; ?></p>
            <p class="table-cell"><?php print $info["Title"]; ?></p>
            <p class="table-cell"><?php print $info["FileExtension"]; ?></p>
            <p class="table-cell"><?php print $info["ContentSize"] . " kb"; ?></p>
            <p class="table-cell"><a href="/file/download/<?php print $id; ?>"><i class="fa-solid fa-download"></i></a></p>
        </div>
    
    <?php endforeach; ?>

</div>


