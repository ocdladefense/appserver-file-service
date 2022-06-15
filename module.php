<?php

use File\File;
use Salesforce\ContentDocument;
use Http\HttpRequest;


class FileUploadModule extends Module
{

	public function __construct() {
		
		parent::__construct();
	}


	public function showForm($sObjectId) {

		$committeeIdsA = ["1","2","3"];
		$committeeIdsB = ["4","5","6"];

		$tpl = new Template("upload");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render([
			"sObjectId" => $sObjectId,
			"comA"		=> implode(",", $committeeIdsA),
			"comB"		=> implode(",", $committeeIdsB)
		]);
	}


	public function upload(){

		//$contentDocumentId = "069050000025IaUAAU";

		var_dump($this->getRequest()->getBody());exit;

		$linkedEntityId = $this->getRequest()->getBody()->sObjectId;

		$file = $this->getRequest()->getFiles()->getFirst();

		$api = $this->loadForceApiFromFlow("usernamepassword");

		// Are you updating an existing ContentDocument or creating a new one?
		$isUpdate = !empty($contentDocumentId);

		$doc = ContentDocument::fromFile($file);


		if($isUpdate) {

			$doc->setContentDocumentId($contentDocumentId);

			// The "uploadFile" function returns the id field of the new ContentVersion.
			$resp = $api->uploadFile($doc);

		} else {

			$resp = $api->uploadFile($doc);
			$contentVersionId = $resp->getBody()["id"];

			$api = $this->loadForceApiFromFlow("usernamepassword");
			// Get the ContentDocumentId from the ContentVersion.
			$resp = $api->query("SELECT ContentDocumentId FROM ContentVersion WHERE Id = '{$contentVersionId}'");
			$contentDocumentId = $resp->getRecords()[0]["ContentDocumentId"];

			//$doc->setLinkedEntityId($linkedEntityId);

			$sobjects = ["a2C05000000qFiyEAE", "003j000000rU9NvAAK"];

			foreach($sobjects as $s) {

				$doc->setLinkedEntityId($s);

				$api = $this->loadForceApiFromFlow("usernamepassword");

				// Watch out for duplicates on the link object, because you dont have an Id field!
				$contentDocumentLink = new StdClass();
				$contentDocumentLink->contentDocumentId = $contentDocumentId;
				$contentDocumentLink->linkedEntityId = $doc->getLinkedEntityId();
				$contentDocumentLink->visibility = "AllUsers";

				$resp = $api->upsert("ContentDocumentLink", $contentDocumentLink);
			}
		}

		if(!$resp->isSuccess()){

			$message = $resp->getErrorMessage();
			throw new Exception($message);
		}

		// if the response is the result of an update, the id returned is that of the new ContentVersion.
		// if the response is the result of creating a new ContentDocument the id returned is that of the new ContentDocuemntLink. 
		$id = $resp->getBody()["id"];

		return "File uploaded successfuly";
	}





























	public function uploadOld() {

		$linkedEntityId = $this->getRequest()->getBody()->sObjectId;

		$file = $this->getRequest()->getFiles()->getFirst();

		// if you are updating an existing content document, you will have to query for it here.
		$contentDocumentId = "069050000025IaUAAU";

		$contentDocumentLinkId = $this->uploadContentDocument($linkedEntityId, $file, $contentDocumentId);

		return "Your file has been uploaded!";
	}







	// $linkedEntityId = the sObject that the contentdocument will be associated with.
	public function uploadContentDocument($linkedEntityId, $file, $contentDocumentId = null) {

		$title = $file->getName();

		if($contentDocumentId == null){

			//  Create a new custom "ContentDocument" object by passing in the file, and setting the id's
			$doc = ContentDocument::fromFile($file);
			$doc->setLinkedEntityId($linkedEntityId);

			// Handles inserts and updates, for now only uploading one file.
			$contentDocumentLinkId = $this->insertContentDocument($doc);

		} else if($contentDocumentId != null){

			//  Create a new custom "ContentDocument" object by passing in the file, and setting the id's
			$doc = ContentDocument::fromFile($file);
			$doc->setContentDocumentId($contentDocumentId);

			$contentDocumentLinkId = $this->updateContentDocument($doc);
		}

		return $contentDocumentLinkId;
	}

	public function insertContentDocument($doc){

		// Pass true as the second parameter to force the usernamepassword flow.
		$api = $this->loadForceApiFromFlow("usernamepassword");

		// Use "uploadFile" to upload a file as a Salesforce "ContentVersion" object.  A successful response contains the Id of the "ContentVersion" that was inserted.
		$resp = $api->uploadFile($doc);
		$contentVersionId = $resp->getBody()["id"];

		// Use the Id of the response to query for the "ContentVersion" object.  Then get the "ContentDocumentID" from the version.
		$api = $this->loadForceApiFromFlow("usernamepassword");
		$contentDocumentId = $api->query("SELECT ContentDocumentId FROM ContentVersion WHERE Id = '{$contentVersionId}'")->getRecords()[0]["ContentDocumentId"];
		
		// Create a standard class representing a Salesforce "ContentDocumentLink" object setting the "ContentDocumentId" to the Id of the "ContentDocument" that
		// was created when you inserted the "ContentVersion". 

		// Watch out for duplicates on the link object, because you dont have an Id field
		$link = new StdClass();
		$link->contentDocumentId = $contentDocumentId;
		$link->linkedEntityId = $doc->getLinkedEntityId();
		$link->visibility = "AllUsers";

		$resp = $api->upsert("ContentDocumentLink", $link);

		if(!$resp->isSuccess()){

			$message = $resp->getErrorMessage();
			throw new Exception($message);
		}

		return $resp->getBody()["id"];
	}

	public function updateContentDocument($doc){

		$api = $this->loadForceApiFromFlow("usernamepassword");

		// Use "uploadFile" to upload a file as a Salesforce "ContentVersion" object.  A successful response contains the Id of the "ContentVersion" that was inserted.
		$resp = $api->uploadFile($doc);

		$contentVersionId = $resp->getBody()["id"];

		if(!$resp->isSuccess()){

			$message = $resp->getErrorMessage();
			throw new Exception($message);
		}

		return $resp->getBody()["id"];
	}
}