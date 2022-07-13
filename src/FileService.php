<?php

use Mysql\DbHelper;



class FileService {



	private $sharePoints = array();

				
	private $myDocuments = array();


	private $sharedWithMe = array();


	private $contactId = null;



	// Add a "share point" i.e., a LinkedEntityId that
	// can be used to query the ContentDocumentLink table.
	public function addSharePoint($id, $name) {
		$this->sharePoints[$id] = $name;
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



/*
	public static function getUploadedBy($documents) {

		// Query for ContentDocumentLinks WHERE ContentDocumentId IN(:array)

		$query = DbHelper::parseArray($format, array_keys($documents));

		$records = $api->query($query)->getRecords();

		$contactPrefix = "abc";


		$owners = array();


		foreach($records as $record) {
			// if $record starts with $contactPrefix {
				$owners[$record["ContentDocumentId"]] = $record["LinkedEntityId"];
			}
			else {
				continue;
			}
		}

		// Perform a subquery against the contact object using array_values($owners);
		$contactResult = $resp->getQueryResult();
		

		// Key the result by the document Id.
		$docs = $contactResult->key("Id");

		// Add more info to $owners so that we can eventually display the name.

		return $owners;
	}
*/


	public function loadAvailableDocuments() {


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

		// Return an array of ContentDocumentLink records that is keyed by ContentDocumentId;
		// in other words, same keys as $doc, above.
		// $owners = self::getOwners($docs);
		$sharePoints = $this->getSharePoints();
		$contactId = $this->getContactId();

		foreach($groups as $docId => $sharing)  {

			$cb = function($carry, $share) use ($sharePoints, $contactId){
				$prev = $carry ?? "";
				$linkId = $share["LinkedEntityId"];
				$current = $sharePoints[$linkId];

				if($linkId == $contactId) return $prev;

				return empty($prev) ? $current : ($prev . ", " . $current);
			};

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
		// Return a QueryResult object.
		// return $result;
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

		$api = loadApi(); // global 

		
		$service = new FileService();

		$contactId = $user->getContactId();
		$service->setContactId($contactId);
		$accountId = $user->query("Contact.AccountId");
		$accountName = $user->query("Account.Name");

		if(null != $contactId) {
			$service->addSharePoint($contactId, "Me");
		}

		if(null != $accountId) {
			$service->addSharePoint($accountId, "Others in {$accountName}");
		}



		$format = "SELECT Committee__c, Committee__r.Name FROM Relationship__c WHERE Contact__c = '%s'";

		$query = sprintf($format, $contactId);

		$result = $api->query($query);

		$records = $result->getRecords();




		foreach($records as $rel) {
			$id 			= $rel["Committee__c"];
			$name 			= $rel["Committee__r"]["Name"];
			$service->addSharePoint($id, $name);
		}

		return $service;
	}

}