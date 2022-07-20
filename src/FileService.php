<?php

use Mysql\DbHelper;



class FileService {



	private $sharePoints = array();

				
	private $myDocuments = array();


	private $sharedWithMe = array();


	private $contactId = null;




	public function __construct($sharePoints = array()) {

		$this->sharePoints = $sharePoints;
	}


	// Add a "share point" i.e., a LinkedEntityId that
	// can be used to query the ContentDocumentLink table.
	public function addSharePoint($id, $name) {
		$this->sharePoints[$id] = $name;
	}


	public function addSharePoints($sharing) {
		$this->sharePoints = $sharing;
	}


	public function getSharePoints() {
		return $this->sharePoints;
	}


	public function setContactId($contactId) {
		$this->contactId = $contactId;
	}


	public function getContactId() {
		return $this->contactId;
	}


	public static function getUserSharePoints($user) {

		// This will be returned.
		$sharing = array();

		$api = loadApi(); // global 

		$contactId = $user->getContactId();
		$accountId = $user->query("Contact.AccountId");
		$accountName = $user->query("Account.Name");

		if(null != $contactId) {
			$sharing[$contactId] = "Me";
		}

		if(null != $accountId) {
			$sharing[$accountId] = "Others in {$accountName}";
		}



		$format = "SELECT Committee__c, Committee__r.Name FROM Relationship__c WHERE Contact__c = '%s'";

		$query = sprintf($format, $contactId);

		$result = $api->query($query);

		$records = $result->getRecords();




		foreach($records as $rel) {
			$id 			= $rel["Committee__c"];
			$name 			= $rel["Committee__r"]["Name"];
			$sharing[$id] 	= $name;
		}

		return $sharing;
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

		var_dump($prefix, $id);exit;
	}






	public function getSharingTargets() {

		// Get the Ids only. Don't need the names to perform the query.
		$targets = array_keys($this->sharePoints);

		$api = loadApi();

		$format = "SELECT ContentDocumentId, LinkedEntityId, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileExtension FROM ContentDocumentLink WHERE LinkedEntityId IN (:array) ORDER BY ContentDocumentId, LinkedEntityId";
		$query = DbHelper::parseArray($format, $targets);

		$resp = $api->query($query);

		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		return $resp->getQueryResult();
	}



	public function includeUploadedBy() {

		$format = "SELECT ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE ContentDocumentId IN (:array)";
		$query = DbHelper::parseArray($format, array_keys($this->sharedWithMe));
		$records = loadApi()->query($query)->getRecords();

		$contactPrefix = "003";
		$docs = [];

		$systemAdminUserId = "005j0000000TYQpAAO"; 

		foreach($records as $record) {

			$prefix = substr($record["LinkedEntityId"], 0, 3);

			if($prefix == $contactPrefix) {
				$docs[$record["ContentDocumentId"]] = $record["LinkedEntityId"];
			}
			else {
				$docs[$record["ContentDocumentId"]] = $systemAdminUserId;
			}
		}

		// Perform a subquery against the contact object using array_values($owners);
		$format = "SELECT Id, Name FROM Contact WHERE Id IN (:array)";
		$query = DbHelper::parseArray($format, $docs);
		$records = loadApi()->query($query)->getQueryResult();
		$contacts = $records->key("Id");
		

		foreach($docs as $docId => $contactId) {

			if($contactId == $systemAdminUserId) {

				$this->sharedWithMe[$docId]["uploadedBy"] = "OCDLA App";

			} else {
				
				$this->sharedWithMe[$docId]["uploadedBy"] = $contacts[$contactId]["Name"];
				$this->sharedWithMe[$docId]["uploadedById"] = $contactId;
			}
		}
	}


	public function getDocuments() {


		$api = loadApi();

		// Group the ContentDocumentLink results by DocumentId.
		// This gets used below to build our tables.
		$fn = function($share) {
			return $share["ContentDocumentId"];
		};

		$targets = $this->getSharingTargets();

		

		// var_dump($targets,$groups);
		// exit;

		
		// Get all meta-data about our documents.
		$docIds = $targets->getField("ContentDocumentId");

		$string = "SELECT Id, CreatedById, CreatedDate, LastModifiedById, LastModifiedDate, IsDeleted, OwnerId, Title, PublishStatus, LatestPublishedVersionId, ParentId, LastViewedDate, Description, ContentSize, FileType, FileExtension, SharingOption, SharingPrivacy, ContentModifiedDate, ContentAssetId FROM ContentDocument WHERE Id IN (:array)";
		$query = DbHelper::parseArray($string, $docIds);
		$resp = $api->query($query);
		
		
		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$result = $resp->getQueryResult();
		
		$groups = $targets->group($fn);

		// Key the result by the document Id.
		$docs = $result->key("Id");

		return $docs;

	}

	public function foobar() {
		// Return an array of ContentDocumentLink records that is keyed by ContentDocumentId;
		// In other words, same keys as $doc, above.
		$sharePoints = $this->getSharePoints();
		$contactId = $this->getContactId();


		$cb = function($carry, $share) use ($sharePoints, $contactId){
			$prev = $carry ?? "";
			$linkId = $share["LinkedEntityId"];
			$current = $sharePoints[$linkId];

			if($linkId == $contactId) return $prev;

			return empty($prev) ? $current : ($prev . ", " . $current);
		};


		foreach($groups as $docId => $sharing)  {

			$targetNames = array_reduce($sharing, $cb);

			$targetIds = array_map(function($share){
				return $share["LinkedEntityId"];
			}, $sharing);

			$docs[$docId]["targetIds"] = $targetIds;
			$docs[$docId]["targetNames"] = $targetNames;
			// $docs[$docId]["uploadedBy"] = // Given the document id, search for all contentdocumentlinks related to this document; from the list, identify the one record that is associated with a Contact; that record represents the "owner" of the document, i.e., the person who uploaded it.


			$docs[$docId]["fileSize"] = calculateFileSize($docs[$docId]["ContentSize"]);
			// Calculate the filesize here, not in the template file.

			if(in_array($this->contactId, $targetIds)) {

				$this->myDocuments[$docId] = $docs[$docId];

			} else {
				
				$this->sharedWithMe[$docId] = $docs[$docId];
			}

		}

		// $this->includeUploadedBy();
	}


	public function getMyDocuments() {

		return $this->myDocuments;
	}


	public function getDocumentsSharedWithMe() {

		return $this->sharedWithMe;
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



	public static function fromUser($user) {
		
		$service = new FileService();

		$contactId = $user->getContactId();
		$service->setContactId($contactId);

		$sharing = self::getCurrentUserSharePoints($user);
		$service->addSharePoints($sharing);

		return $service;
	}

}