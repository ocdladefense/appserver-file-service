

<h1 class="list-header">Available Documents</h1>

<div class="container">
    
    <!-- All Documents -->
    <?php
        foreach($tables as $table) {

            $isMyDocs = in_array(current_user()->getContactId(), $table["linkedEntityIds"]) ? true : false;

            $entityId = $table["linkedEntityIds"][0];

            $table["link"] = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$entityId/related/CombinedAttachments/view";

            $table["title"] = $isMyDocs ? "My Documents" 
            : "Documents Shared with " . FileService::getEntityName($entityId)." ".trim(FileService::getSobjectType($entityId), "__c"). " members";

            component("DocumentsComponent", $table);
        }
    ?>


    
</div>