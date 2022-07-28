<?php 

class UploadedDocument {

    private $id;
    private $title;
    private $fileSize;
    private $extension;
    private $fileType;
    private $linkedEntityId;
    private $uploadedBy;
    private $ownerId;
    

    public static function fromContentDocumentLinkQueryResult($contentDocumentLinkQueryResults) {

        $documents = [];

        foreach($contentDocumentLinkQueryResults as $result) {

            $data = $result["ContentDocument"];

            $doc = new self();
            $doc->id = $result["ContentDocumentId"];
            $doc->title = $data["Title"];
            $doc->fileSize = calculateFileSize($data["ContentSize"]);
            $doc->fileType = $data["FileType"];
            $doc->extension = $data["FileExtension"];
            $doc->uploadedBy = $result["ownerName"];
            $doc->ownerId = $result["ownerId"];
            $doc->linkedEntityId = $result["LinkedEntityId"];
            

            $documents[] = $doc;
        }

        return $documents;
    }

    public function id() {

        return $this->id;
    }

    public function title() {

        return $this->title;
    }

    public function fileSize() {

        return $this->fileSize;
    }

    public function fileType() {

        return $this->fileType;
    }

    public function extension() {

        return $this->extension;
    }

    public function uploadedBy() {

        return empty($this->uploadedBy) ? "OCDLA APP" : $this->uploadedBy;
    }

    public function ownerId() {

        return $this->ownerId;
    }

    public function linkedEntityId() {

        return $this->linkedEntityId;
    }

    public function userIsOwner() {

        return current_user()->getContactId() == $this->ownerId;
    }
}