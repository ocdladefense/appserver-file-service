<h1 class="list-header">Available Documents</h1>



<div class="table">
    <div class="table-row first">
        <p class="table-header">Shared With</p>
        <p class="table-header">Title</p>
        <p class="table-header">Type</p>
        <p class="table-header">Size</p>
    </div>




    <?php foreach($links as $link): ?>

        <div class="table-row data">
            <p class="table-cell"><?php print $link["targetName"]; ?></p>
            <p class="table-cell"><?php print $link["ContentDocument"]["Title"]; ?></p>
            <p class="table-cell"><?php print $link["ContentDocument"]["FileExtension"]; ?></p>
            <p class="table-cell"><?php print $link["ContentDocument"]["ContentSize"] . " kb"; ?></p>
            <p class="table-cell"><a href="/file/download/<?php print $link["ContentDocumentId"]; ?>"><i class="fa-solid fa-download"></i></a></p>
        </div>
    <?php endforeach; ?>

</div>


