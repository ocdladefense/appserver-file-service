<h1>Here are the documents that have been shared with you!</h1>

<br />
<br />
<br />

<div>
    <div class="row first">
        <p class="col">Title</p>
        <p class="col">MimeType</p>
        <p class="col">Size</p>
    </div>
</div>

<div class="">

    <?php foreach($links as $link) : ?>

        <div class="row data">
            <p class="col"><?php print $link["ContentDocument"]["Title"]; ?></p>
            <p class="col"><?php print $link["ContentDocument"]["FileExtension"]; ?></p>
            <p class="col"><?php print $link["ContentDocument"]["ContentSize"] . " kb"; ?></p>
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
</style>