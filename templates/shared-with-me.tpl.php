<?php
/**
 * Template to render documents shared with me (not uploaded by me.)
 */
?>
<div>
    <h2>Documents Shared With Me</h2>

    <div class="table">
        <div class="table-row first">
            <p class="table-header">Shared By</p><!-- Person who uploaded it. -->
            <p class="table-header">Shared With</p><!-- Non-contact entities this document is shared with. -->
            <p class="table-header">Title</p>
            <p class="table-header">Type</p>
            <p class="table-header">Size</p>
            <p class="table-header">Download</p>
            <p class="table-header"></p>
        </div>



        <?php foreach($documents as $id => $doc): ?>

            <?php
                $names = explode(", ", $doc["targetNames"]);
                $links = array_map(function($name){
                    return "<a href='/committee/" . Identifier::toMachineName($name) . "'>$name</a>";
                }, $names);

                ?>
            
            <div class="table-row data">

                <!-- Shared By -->
                <p class="table-cell">
                    <a href="/directory/members/<?php print $doc["uploadedById"]; ?>">
                        <?php print $doc["uploadedBy"]; ?>
                    </a>
                </p>

                <!-- Shared With -->
                <p class="table-cell">
                    <?php print implode(", ", $links); ?>
                </p>

                <p class="table-cell">
                    <a href="/file/download/<?php print $id; ?>"><?php print $doc["Title"]; ?></a>
                </p>

                <p class="table-cell">
                    <?php print $doc["FileExtension"]; ?>
                </p>

                <p class="table-cell">
                    <?php print $doc["fileSize"]; ?>
                </p>

                <p class="table-cell icon-cell">
                    <a href="/file/download/<?php print $id; ?>">
                        <i class="fa-solid fa-download"></i>
                    </a>
                </p>

                <p class="table-cell"></p>

            </div>
        
        <?php endforeach; ?>

    </div>
</div>