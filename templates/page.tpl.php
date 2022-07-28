

<link rel="stylesheet" type="text/css" href="<?php print module_path(); ?>/assets/list.css" />

<h1 class="list-header">Available Documents</h1>

<div class="container">


    <!-- My Documents -->
    <?php

        $table = $tables[0];

        $isMyDocs = in_array(current_user()->getContactId(), $table["linkedEntityIds"]) ? true : false;

        $entityId = $table["linkedEntityIds"][0];

        $table["link"] = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$entityId/related/CombinedAttachments/view";

        $table["title"] = $isMyDocs ? "My Documents" 
        : "Documents Shared with " . FileService::getEntityName($entityId)." ".trim(FileService::getSobjectType($entityId), "__c"). " members";

        component("DocumentsComponent", $table);

    ?> 

    <!-- Account Documents -->
    <?php

        $table = $tables[1];

        $isMyDocs = in_array(current_user()->getContactId(), $table["linkedEntityIds"]) ? true : false;

        $entityId = $table["linkedEntityIds"][0];

        $table["link"] = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$entityId/related/CombinedAttachments/view";

        $table["title"] = $isMyDocs ? "My Documents" 
        : "Documents Shared with " . FileService::getEntityName($entityId)." ".trim(FileService::getSobjectType($entityId), "__c"). " members";

        component("DocumentsComponent", $table);

    ?> 


    <!-- Account Documents -->
    <?php

        $table = $tables[2];

        $isMyDocs = in_array(current_user()->getContactId(), $table["linkedEntityIds"]) ? true : false;

        $entityId = $table["linkedEntityIds"][0];

        $table["link"] = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$entityId/related/CombinedAttachments/view";

        $table["title"] = $isMyDocs ? "My Documents" 
        : "Documents Shared with " . FileService::getEntityName($entityId)." ".trim(FileService::getSobjectType($entityId), "__c"). " members";

        component("DocumentsComponent", $table);

    ?> 
    

    
</div>