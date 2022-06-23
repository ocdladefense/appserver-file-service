<?php

use File\File;
use Salesforce\ContentDocument;
use Http\HttpRequest;
use Mysql\DbHelper;


class FileUploadModule extends Module
{

	public function __construct() {
		
		parent::__construct();
	}


	public function showForm() {

		$api = $this->loadForceApi();

		$user = current_user();

		$sharing = array();

		$accountId = $user->query("Contact.AccountId");
		$accountName = $user->query("Account.Name");
		$contactId = $user->getContactId();

		$format = "SELECT Committee__c, Committee__r.Name FROM Relationship__c WHERE Contact__c = '%s'";

		$query = sprintf($format, $contactId);

		$result = $api->query($query);

		$records = $result->getRecords();


		// Always share with the original contactId
		// a new route that shows all of the contentDocumentLinks of docs that are shared with the current user.


		if(null != $accountId) {
			$sharing[$accountId] = "Others in {$accountName}";
		}


		foreach($records as $rel) {
			$key 			= $rel["Committee__c"];
			$name 			= $rel["Committee__r"]["Name"];
			$sharing[$key] 	= $name;
		}

		$tpl = new Template("upload");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render([
			"sharing"		=> $sharing
		]);
	}




	public function upload(){

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

			

			

			foreach(array_keys($sharing) as $id) {

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


	public function showAll() {

		$user = current_user();
		$contactId = $user->getContactId();
		$accountId = $user->query("Contact.AccountId");

		// Get the committee ids
		$api = $this->loadForceApi();
		$query = "SELECT Committee__c FROM Relationship__c WHERE Contact__c = '$contactId'";

		$committeeIds = $api->query($query)->getField("Committee__c");

		$linkedEntityIds = array_merge([$contactId, $accountId], $committeeIds);

		$query = "SELECT ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileExtension FROM ContentDocumentLink WHERE LinkedEntityId IN (:array)";
		$query = DbHelper::parseArray($query, $linkedEntityIds);

		$resp = $api->query($query);

		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$links = $resp->getRecords();
		$tpl = new Template("document-list");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render(["links" => $links]);
	}




	public function groupDocuments() {

		$sobjPrefix = [
			"003" => "Contact",
			"001" => "Account",
			"a2G" => "Committee"
		];
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