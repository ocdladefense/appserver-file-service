<?php

use Mysql\DbHelper;



class FileService {


	



	public static function getSharingTargets($sharingTargets) {
		$api = loadApi();


		$format = "SELECT ContentDocumentId, LinkedEntityId, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileExtension FROM ContentDocumentLink WHERE LinkedEntityId IN (:array) ORDER BY ContentDocumentId, LinkedEntityId";
		$query = DbHelper::parseArray($format, $sharingTargets);

		$resp = $api->query($query);

		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		return $resp->getQueryResult();
	}



	public static function getCurrentUserSharingTargets() {

		$api = loadApi(); // global 

		$user = current_user();

		$sharing = array();

		$accountId = $user->query("Contact.AccountId");
		$accountName = $user->query("Account.Name");
		$contactId = $user->getContactId();

		$format = "SELECT Committee__c, Committee__r.Name FROM Relationship__c WHERE Contact__c = '%s'";

		$query = sprintf($format, $contactId);

		$result = $api->query($query);

		$records = $result->getRecords();

		if(null != $contactId) {
			$sharing[$contactId] = "Me";
		}

		if(null != $accountId) {
			$sharing[$accountId] = "Others in {$accountName}";
		}


		foreach($records as $rel) {
			$key 			= $rel["Committee__c"];
			$name 			= $rel["Committee__r"]["Name"];
			$sharing[$key] 	= $name;
		}

		return $sharing;
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