<?php

use Mysql\DbHelper;
use Salesforce\ContentDocument;



class FileService {



	private $linkedEntityIds = array();


	public function __construct($linkedEntityIds = array()) {

		$this->linkedEntityIds = $linkedEntityIds;
	}



	public static function getUserAssociatedEntityIds($user = null) {

		$user = empty($user) ? current_user() : $user;

		$contactId = $user->getContactId();
		$accountId = $user->query("Contact.AccountId");

		$associatedIds = [$contactId, $accountId];

		// Get the Id of any committees the user is associated with.
		$api = loadApi();
		$format = "SELECT Committee__c FROM Relationship__c WHERE Contact__c = '%s'";
		$query = sprintf($format, $contactId);
		$records = $api->query($query)->getRecords();

		foreach($records as $rec) $associatedIds[] = $rec["Committee__c"];

		return $associatedIds;
	}




	public function getDocuments() {

		// Get the ContentDocumentLinks with all of the ContentDocument data.
		$format = "SELECT Id, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileType, ContentDocument.FileExtension, ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE LinkedEntityId IN (:array)";
		$query = DbHelper::parseArray($format, $this->linkedEntityIds);
		$resp = loadApi()->query($query);
		
		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$docs = $resp->getQueryResult();

		if($docs->count() == 0) return [];

		// All of the linked entities for all of the documents
		$linkedEntities = $this->getLinkedEntities($docs);

		// The owner data for the documents.  An array of contacts keyed by ContentDocumentIds.
		$owners = $this->getOwners($linkedEntities);

		// Need docs to be an array, not a private row.
		$docs = $docs->key("ContentDocumentId");

		foreach($docs as $id => &$doc) {

			$doc["ownerName"] = $owners[$id]["Name"];
			$doc["ownerId"] = $owners[$id]["Id"];
		}

		return ContentDocument::fromContentDocumentLinkQueryResult($docs);
	}


	// Trying to get all the contacts that are linkedEntities for a given set of ContentDocumentLinks
	public function getOwners($linkedEntities) {

		$ids = $linkedEntities->getField("LinkedEntityId");

		$format = "SELECT Id, Name FROM Contact WHERE Id in (:array)";
		$query = DbHelper::parseArray($format, $ids);
		$resp = loadApi()->query($query);
		
		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$contacts = $resp->getQueryResult()->key("Id");

		// We only want the 
		$contactEntities = array_filter($linkedEntities->getRecords(), function($entity){

			return self::getSobjectType($entity["LinkedEntityId"]) == "Contact";
		});

		$owners = [];

		foreach($contactEntities as $entity) {

			$owners[$entity["ContentDocumentId"]] = $contacts[$entity["LinkedEntityId"]];
		
		}

		return $owners;
	}


	// Trying to figure out who owns the document
	public function getLinkedEntities($docs) {

		$ids = $docs->getField("ContentDocumentId");

		$format = "SELECT ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE ContentDocumentId in (:array)";
		$query = DbHelper::parseArray($format, $ids);
		$links = loadApi()->query($query)->getQueryResult();

		return $links;
	}



	// I think we need this function in core.
	public static function getEntityName($id) {

		$sObjectType = self::getSobjectType($id);
		$query = "SELECT Name FROM $sObjectType WHERE Id = '$id'";
		
		return loadApi()->query($query)->getRecord()["Name"];
	}


	// I think we need a more complete version of this function in core.
	public static function getSobjectType($id) {

		$prefix = substr($id, 0, 3);

		switch ($prefix) {
			case 'a2G':
				return "Committee__c";
				break;
			case '005':
				return "User";
				break;
			case '003':
				return "Contact";
				break;
			case '001':
				return "Account";
				break;
			default:
				throw new Exception("NO SOBJECT TYPE FOUND FOR PREFIX $prefix");
				break;
		}
	}


	public function downloadContentDocument($id) {

		$api = $this->loadForceApi();

		$query = "SELECT VersionData, Title FROM ContentVersion WHERE ContentDocumentId = '$id' AND IsLatest = True";

		$version = $api->query($query)->getRecord();
		$versionUrl = $version["VersionData"];

		$api2 = $this->loadForceApi();
		$resp = $api2->send($versionUrl);

		$file = new File($version["Title"]);
		$file->setContent($resp->getBody());
		$file->setType($resp->getHeader("Content-Type"));

		return $file;
	}
}