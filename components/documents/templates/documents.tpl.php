<?php
/**
 * Template to render documents shared with me (not uploaded by me.)
 */
?>
<div class="doc-list">

<h3 style="display:inline;"><?php print $title; ?></h3> - <a href="<?php print $link; ?>" target="_blank">view on salesforce</a>

    <div class="table">
        <div class="table-row first">
            <p class="table-header">Shared By</p>
            <p class="table-header">Title</p>
            <p class="table-header">Type</p>
            <p class="table-header">Size</p>
            <p class="table-header">Download</p>
            <p class="table-header">Delete</p>
        </div>



        <?php foreach($documents as $doc): ?>
            
            <div class="table-row data">

                <p class="table-cell">
                    <a href="/directory/members/<?php print $doc->ownerId(); ?>">
                        <?php print $doc->uploadedBy();?>
                    </a>
                </p>


                <p class="table-cell">
                    <a href="/file/download/<?php print $id; ?>"><?php print $doc->title(); ?></a>
                </p>

                <p class="table-cell">
                    <?php print $doc->extension(); ?>
                </p>

                <p class="table-cell">
                    <?php print $doc->fileSize(); ?>
                </p>

                <p class="table-cell icon-cell">
                    <a href="/file/download/<?php print $doc->id(); ?>">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </p>

                <?php if($doc->userIsOwner()) : ?>
                    <p class="table-cell icon-cell">
                        <a href="/file/delete/<?php print $doc->id(); ?>">
                            <i class="fa-solid fa-trash"></i>
                        </a>
                    </p>
                <?php endif; ?>

            </div>
        
        <?php endforeach; ?>

    </div>
</div>