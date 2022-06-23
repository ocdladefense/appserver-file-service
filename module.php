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
			"sharing"		=> $sharing,
			"contactId"		=> $contactId
		]);
	}



	public function upload(){

		$linkedEntityIds = $this->getRequest()->getBody()->linkedEntityIds;

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

			
			foreach($linkedEntityIds as $id) {

				$doc->setLinkedEntityId($id);

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

		return redirect("/file/list");
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

		$query = "SELECT ContentDocumentId, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileExtension FROM ContentDocumentLink WHERE LinkedEntityId IN (:array)";
		$query = DbHelper::parseArray($query, $linkedEntityIds);

		$resp = $api->query($query);

		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$links = $resp->getRecords();

		$tpl = new Template("document-list");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render(["links" => $links]);
	}



	public function downloadContentDocument($id){

		$api = $this->loadForceApi();

		$query = "SELECT VersionData, Title FROM ContentVersion WHERE ContentDocumentId = '$id' AND IsLatest = true";

		$version = $api->query($query)->getRecord();
		$versionUrl = $version["VersionData"];

		$api2 = $this->loadForceApi();
		$resp = $api2->send($versionUrl);

		$file = new File($version["Title"]);
		$file->setContent($resp->getBody());
		$file->setType($resp->getHeader("Content-Type"));

		return $file;
	}



	public function groupDocuments() {

		$sobjPrefix = [
			"003" => "Contact",
			"001" => "Account",
			"a2G" => "Committee"
		];
	}
}


