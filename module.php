<?php

use File\File;
use Salesforce\ContentDocument;
use Http\HttpRequest;
use Mysql\DbHelper;


class FileServiceModule extends Module
{

	public function __construct() {
		
		parent::__construct();
	}




	public function showForm() {

		$tpl = new Template("upload");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render();
	}

	


	public function upload(){

		$linkedEntityIds = $this->getRequest()->getBody()->linkedEntityIds;

		$file = $this->getRequest()->getFiles()->getFirst();

		$api = loadApi();

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

			$api = loadApi();
			// Get the ContentDocumentId from the ContentVersion.
			$resp = $api->query("SELECT ContentDocumentId FROM ContentVersion WHERE Id = '{$contentVersionId}'");
			$contentDocumentId = $resp->getRecords()[0]["ContentDocumentId"];

			
			foreach($linkedEntityIds as $id) {

				$doc->setLinkedEntityId($id);

				$api = loadApi();

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



	public function list($list = null) {


		$sharingData = FileService::getSharingTargets();

		// Get the Committee Ids.
		$api = loadApi();


		$format = "SELECT ContentDocumentId, LinkedEntityId, ContentDocument.Title, ContentDocument.ContentSize, ContentDocument.FileExtension FROM ContentDocumentLink WHERE LinkedEntityId IN (:array) ORDER BY ContentDocumentId, LinkedEntityId";
		$query = DbHelper::parseArray($format, array_keys($sharingData));

		$resp = $api->query($query);

		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$links = $resp->getQueryResult();

		$found = $resp->getField("LinkedEntityId");

		$docIds = $resp->getField("ContentDocumentId");

		$fn = function($share) {
			return $share["ContentDocumentId"];
		};

		$groups = $resp->group($fn);

		var_dump($docs);exit;

		$format2 = "SELECT Id, CreatedById, CreatedDate, LastModifiedById, LastModifiedDate, IsDeleted, OwnerId, Title, PublishStatus, LatestPublishedVersionId, ParentId, LastViewedDate, Description, ContentSize, FileType, FileExtension, SharingOption, SharingPrivacy, ContentModifiedDate, ContentAssetId FROM ContentDocument WHERE Id IN (:array)";
		$query = DbHelper::parseArray($format2, $docIds);
		$resp = $api->query($query);
		
		if(!$resp->success()) throw new Exception($resp->getErrorMessage());

		$result = $resp->getQueryResult();
		// Key the result by the 
		$docs = $result->key("Id");

		// var_dump($records);exit;

		$sharedWith = [];



		// groupedDocs
		foreach($groups as $docId => $sharing)  {

			$cb = function($carry, $share) {
				$prev = $carry ?? "";
				$current = $share["targetName"];
				return empty($prev) ? $current : ($prev . "," . $current);
			};
			$foo = array_reduce($sharing, $cb);
			$sharedWith[$docId] = $foo;
		}



		$tpl = new Template("list");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render(["docs" => $docs, "sharing" => $sharedWith]);
	}



	public function downloadContentDocument($id){

		$api = loadApi();

		$query = "SELECT VersionData, Title FROM ContentVersion WHERE ContentDocumentId = '$id' AND IsLatest = True";

		$version = $api->query($query)->getRecord();
		$versionUrl = $version["VersionData"];

		$api2 = loadApi();
		$resp = $api2->send($versionUrl);

		$file = new File($version["Title"]);
		$file->setContent($resp->getBody());
		$file->setType($resp->getHeader("Content-Type"));

		return $file;
	}

	
	public function getAttachment($id) {

		// Get the attachment object.
		$api = loadApi();
		$results = $api->query("SELECT Id, Name, Body FROM Attachment WHERE Id = '{$id}'");
		$attachment = $results->getRecord();

		// Request the file content of the attachment using the blobfield endpoint returned in the "Body" field of the attachment.
		$endpoint = $attachment["Body"];
		$req = loadApi();
		$req->removeXHttpClientHeader();
		$resp = $req->send($endpoint);

		$file = new File($attachment["Name"]);
		$file->setContent($resp->getBody());
		$file->setType($resp->getHeader("Content-Type"));

		return $file;
	}




	/**
	 * Used by the appserver-jobs module.
	 * 
	 * Given a list of record ids, get the related ContentDocumentLink records.
	 */
	public function getContentDocument($jobRecords){

		// Get the job ids as a comma seperated string.
		$jobIds = array();
		foreach($jobRecords as $job){

			$jobIds[] = $job["Id"];
		}

		$links = $this->getContentDocumentLinks($jobIds);

		$contentVersions = $this->getContentDocumentIds($links);


		// Add the contentVersion to the job array at the index of "ContentDocument" if there is a contentversion.
		$updatedJobRecords = array();
		foreach($jobRecords as $job){

			foreach($links as $link){

				if($link["LinkedEntityId"] == $job["Id"]){

					foreach($contentVersions as $conDoc){

						// This is a cheat. 
						$conDoc["Id"] = $conDoc["ContentDocumentId"];

						if($conDoc["ContentDocumentId"] == $link["ContentDocumentId"]){

							$job["ContentDocument"] = $conDoc;

						}
					}
				}
			}

			$updatedJobRecords[] = $job;
		}

		return $updatedJobRecords;
	}

	/**
	 * Used by the appserver-jobs module.
	 * 
	 * Given a list of record ids, get the related ContentDocumentLink records.
	 */
	public function getContentDocumentLinks($jobIds){

		$jobIdString = "'" . implode("','", $jobIds) . "'";


		$api = loadApi();
		$query = "SELECT ContentDocumentId, LinkedEntityId FROM ContentDocumentLink WHERE LinkedEntityId IN ($jobIdString)";
		$resp = $api->query($query);

		if(!$resp->isSuccess()) throw new Exception($resp->getErrorMessage());

		return $resp->getRecords();
	}

	/**
	 * Used by the appserver-jobs module.
	 * 
	 * Given a list of record ids, get the related ContentDocumentLink records.
	 */
	public function getContentDocumentIds($links){

		$contentDocumentIds = array();
		foreach($links as $link){

			$contentDocumentIds[] = $link["ContentDocumentId"];
		}

		$conDocIdString = "'" . implode("','", $contentDocumentIds) . "'";


		// Get the contentVersions
		$api = loadApi();
		$query = "SELECT Id, Title, isLatest, ContentDocumentId FROM ContentVersion WHERE contentDocumentId IN ($conDocIdString) AND IsLatest = true";
		$resp = $api->query($query);

		if(!$resp->isSuccess()) throw new Exception($resp->getErrorMessage());

		return $resp->getRecords();
	}



/////////////////////////	ATTACHMENT STUFF	////////////////////////////////////////////////////////////////////////

		// Get the FileList" object from the request, use the first file to build an "Attachment/File" object,
	// insert the Attachment, and return the id.
	public function insertAttachment($jobId, $file){

		if($jobId == null) throw new Exception("ERROR_ADDING_ATTACHMENT:  The job id can not be null when adding attachments.");

		$fileClass = "Salesforce\Attachment";

		$file = $fileClass::fromFile($file);
		$file->setParentId($jobId);

		$api = loadApi();

		$resp = $api->uploadFile($file);

		if(!$resp->isSuccess()) throw new Exception($resp->getErrorMessage());

		$attachment = $fileClass::fromArray($resp->getBody());

		return $attachment->Id;
	}



	public function getAttachments($jobId) {

		$api = loadApi();
		
		$attResults = $api->query("SELECT Id, Name FROM Attachment WHERE ParentId = '{$jobId}'");

		return $attResults->getRecords();
	}




	public function groupDocuments() {

		$sobjPrefix = [
			"003" => "Contact",
			"001" => "Account",
			"a2G" => "Committee"
		];
	}
}


