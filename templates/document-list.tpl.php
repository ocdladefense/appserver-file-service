<h1>Available Documents</h1>

<br />
<br />
<br />

<div>
    <div class="row first">
        <p class="col">Title</p>
        <p class="col">Type</p>
        <p class="col">Size</p>
    </div>
</div>

<div class="">

    <?php foreach($links as $link) : ?>

        <div class="row data">
            <p class="col"><?php print $link["ContentDocument"]["Title"]; ?></p>
            <p class="col"><?php print $link["ContentDocument"]["FileExtension"]; ?></p>
            <p class="col"><?php print $link["ContentDocument"]["ContentSize"] . " kb"; ?></p>
            <p class="col"><a href="/file/download/<?php print $link["ContentDocumentId"]; ?>"><i class="fa-solid fa-download"></i></a></p>
        </div>
    <?php endforeach; ?>

</div>


<style>
    .first{
        font-size: 25px;
    }
    .row{
        border-bottom: solid 1px;
        margin: 2px;
    }
    .data .col{
        padding: 0 0 0 5px;
    }
    .col{
        width: 24%;
    }
</style>