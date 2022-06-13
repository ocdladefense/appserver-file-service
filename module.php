<?php

use File\File;
use Salesforce\ContentDocument;


class FileUploadModule extends Module
{

	public function __construct() {
		
		parent::__construct();
	}

	public function showForm() {

		$tpl = new Template("upload");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render();
	}


	public function upload() {

		$file = $this->getRequest()->getFiles()->getFirst();
		$linkedEntityId = "a2C05000000qFiyEAE";
		$contentDocumentId = "069050000025IaUAAU";

		$contentDocumentLinkId = $this->uploadContentDocument($linkedEntityId, $contentDocumentId, $file);

		return "Your file has been uploaded!";
	}


	// $linkedEntityId = the sObject that the contentdocument will be associated with.
	public function uploadContentDocument($linkedEntityId, $contentDocumentId, $file) {

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

	public function downloadContentDocument($id){

		$api = $this->loadForceApi();

		$veriondataQuery = "SELECT Versiondata, Title FROM ContentVersion WHERE ContentDocumentId = '$id' AND IsLatest = true";

		$contentVersion = $api->query($veriondataQuery)->getRecord();
		$versionData = $contentVersion["VersionData"];

		$api2 = $this->loadForceApi();
		$resp = $api2->send($versionData);

		$file = new File($contentVersion["Title"]);
		$file->setContent($resp->getBody());
		$file->setType($resp->getHeader("Content-Type"));

		return $file;
	}
}