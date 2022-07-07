<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">

    <div>
        <h2 style="display:inline; margin-right:15px;">My Documents</h2>

        <?php if(current_user()->isAdmin()): ?>
            <form action="<?php print $contactUrl; ?>" target="_blank" style="display:inline;">
                <button type="submit">View on Salesforce</button>
            </form>
        <?php endif; ?>

        <br />
        <br />

        <div class="table">
            <div class="table-row first">
                <p class="table-header">Shared With</p>
                <p class="table-header">Title</p>
                <p class="table-header">Type</p>
                <p class="table-header">Size</p>
                <p class="table-header">Download</p>
                <p class="table-header">Delete</p>
            </div>



            <?php foreach($myDocuments as $id => $doc): ?>
                
                <div class="table-row data">
                    <p class="table-cell"><?php print $doc["targetNames"]; ?></p>
                    <p class="table-cell">
                        <a href="/file/download/<?php print $id; ?>"><?php print $doc["Title"]; ?></a>
                    </p>
                    <p class="table-cell"><?php print $doc["FileExtension"]; ?></p>
                    <p class="table-cell"><?php print $doc["fileSize"]; ?></p>
                    <p class="table-cell icon-cell">
                        <a href="/file/download/<?php print $id; ?>"><i class="fa-solid fa-download"></i></a>
                    </p>

                    <p class="table-cell icon-cell">
                        <a href="/file/delete/<?php print $id; ?>" onClick="return confirm('Are you sure you want to delete this document?');">
                            <i class='fa fa-trash'></i>
                        </a>
                    </p>
                </div>
            
            <?php endforeach; ?>

        </div>
    </div>
         <br />
         <br />
    <div>
        <h2>Shared Documents</h2>
        <br />

        <div class="table">
            <div class="table-row first">
                <p class="table-header">Shared By</p>
                <p class="table-header">Title</p>
                <p class="table-header">Type</p>
                <p class="table-header">Size</p>
                <p class="table-header">Download</p>
                <p class="table-header"></p>
            </div>



            <?php foreach($sharedDocuments as $id => $doc): ?>
                
                <div class="table-row data">
                <p class="table-cell"><?php print $doc["targetNames"]; ?></p>
                    <p class="table-cell">
                        <a href="/file/download/<?php print $id; ?>"><?php print $doc["Title"]; ?></a>
                    </p>
                    <p class="table-cell"><?php print $doc["FileExtension"]; ?></p>
                    <p class="table-cell"><?php print $doc["fileSize"]; ?></p>
                    <p class="table-cell icon-cell">
                        <a href="/file/download/<?php print $id; ?>"><i class="fa-solid fa-download"></i></a>
                    </p>
                    <p class="table-cell"></p>

                </div>
            
            <?php endforeach; ?>

        </div>
    </div>
    
</div>


