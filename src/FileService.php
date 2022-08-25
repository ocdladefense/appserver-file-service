<?php

use Mysql\DbHelper;
use Salesforce\ContentDocument;

class FileService {

	private $linkedEntityIds = array();

	// Cache of all documents retrieved for the current request.
	private static $cache = null;



	public function __construct($linkedEntityIds = array()) {

		$this->linkedEntityIds = $linkedEntityIds;
	}


	// Get the ids for the user's contact, account, and any committees the user is a member of.
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

		// We've already cached all of the documents statically for the current request.

		// Given this instances linkedEntityIds, retrieve documents from the static cache matching those linkedEntityIds.

		// Then return them.
		$first = $this->linkedEntityIds[0];

		return array_filter(self::$cache, function($doc) use($first) {
			return in_array($first, $doc->linkedEntities);
		});
	}

	// Return an array of "Salesforce\ContentDocument" objects.
	public static function loadDocuments($linkedEntityIds) {

		// Get the ContentDocumentLinks with all of the ContentDocument data.
		$format = "SELECT Id, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileType, ContentDocument.FileExtension, ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE LinkedEntityId IN (:array)";
		$query = DbHelper::parseArray($format, $linkedEntityIds);
		$resp = loadApi()->query($query);
		$result = $resp->getQueryResult();

		if($result->count() == 0) return [];

		$data = $result->group(function($link){ return $link["ContentDocumentId"];});

		$ids = $result->getField("ContentDocumentId");

		// All of the linked entities for all of the documents
		$format = "SELECT ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE ContentDocumentId IN (:array)";
		$query = DbHelper::parseArray($format, $ids);
		$result = loadApi()->query($query)->getQueryResult();

		$linkedEntityIds = $result->getField("LinkedEntityId");

		$grouped = $result->group(function($link){ return $link["ContentDocumentId"];});

		$docs = array();

		// It all got grouped by ContentDocumentId, above.
		foreach($grouped as $id => $links) {

			$doc = new ContentDocument($id);
			// Only need the first element since all elements are the same with the exception of the LinkedEntityIds.
			// We are passing the linked entity data in seperatly.
			$doc->setDocumentData($data[$id][0]["ContentDocument"]);
			$doc->setLinkedEntities($links);
			$docs []= $doc;
		}


		// Associative array of the names of all of the linked entities, keyed by their ids."
		$sharedWith = self::getSharedWithNames($linkedEntityIds);

		// If the id of the shared with entity is in the docs linkedEntities array, add the name to the docs "sharedWith" array.
		foreach($docs as $doc) {

			$uploadedById = $doc->getUploadedById();
			$doc->setUploadedBy($sharedWith[$uploadedById]);

			foreach($doc->getLinkedEntities() as $id){
				$doc->addSharedWith($sharedWith[$id]);
			}
		}

		self::$cache = $docs;

		return $docs;
	}


	// Build an associative array of entity names, keyed by thier ids.
	public static function getSharedWithNames($ids) {

		$types = [];
		foreach($ids as $id) {
			$type = self::getSobjectType($id);
			if(!in_array($type, $types)) $types[$type][] = $id;
		}

		$api = loadApi();
		$names = [];
		foreach($types as $type => $typeIds) {

			$format = "SELECT Id, Name FROM $type WHERE Id IN (:array)";
			$query = DbHelper::parseArray($format, $typeIds);
			$results = $api->query($query)->getRecords();

			foreach($results as $result) {

				$names[$result["Id"]] = $result["Name"];
			}
		}

		return $names;
	}


	// I think we need this function in core.
	public static function getEntityName($id) {

		$sObjectType = self::getSobjectType($id);
		$query = "SELECT Name FROM $sObjectType WHERE Id = '$id'";
		
		return loadApi()->query($query)->getRecord()["Name"];
	}


	public static function getEntityNames($ids) {

		$api = loadApi();
		$names = [];

		foreach($ids as $id) {

			$type = self::getSobjecttype($id);
			$query = "SELECT Name FROM $type WHERE Id = '$id'";
			$names[] = $api->query($query)->getRecord()["Name"];
		}

		return implode(", ", $names);
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