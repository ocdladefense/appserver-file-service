<?php

use Mysql\Database;
use Http\HttpRequest;
use Http\HttpHeader;
use Mysql\DbHelper;
use Mysql\QueryBuilder;
use Http\HttpHeaderCollection;
use GIS\Political\Countries\US\Oregon;
use Ocdla\Date;

use function Html\createDataListElement;
use function Html\createSelectElement;




class DocumentsComponent extends Presentation\Component {


	public $active = true;

	private $linkedEntityIds;



	public function __construct($name, $tplName, $params) {
		
		parent::__construct($name);

		$this->template = $tplName;

		// var_dump($tplName, $this->template);exit;

		$input = $this->getInput();
	}


    public function getStyles() {
		//return array();
        return array(
            "active" => true,
            "href" => module_path() . "/components/documents/main.css?bust=001"
        );
    }

    public function getScripts() {
        return array(
            "src" => module_path() . "/components/documents/main.js?bust=001"
        );
    }


	/**
	 * Revise this to print the HTML table of documents for *either documents that are mine *or documents that are shared with me.  BUT NOT BOTH.
	 */
	public function toHtml($params = array()) {

		$api = loadApi();

		$contactId = current_user()->getContactId();

		$service = FileService::fromUser(current_user());

		// Possible sharing targets for the current user.
		// These usually include the contactId, accountId and any committeeIds.
		$sharePoints = $service->getSharePoints();

		// The actual shared documents.
		$targets = $service->getSharingTargets();


		// If no documents, then display accordingly.
		if($targets->count() == 0) {
			$tpl = new Template("no-records");
			$tpl->addPath(__DIR__ . "/templates");
			return $tpl;
		}

		
		$service->loadAvailableDocuments();

		$docs = $this->template == "my-documents" ? $service->getMyDocuments() : $service->getDocumentsSharedWithMe();

		$salesforceUrl = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$contactId/related/CombinedAttachments/view";

		//var_dump($this->template);exit;

		// Template depends on the params that get passed into this function; or maybe the $id value that is passed into the "component()" function call.
		$tpl = new Template($this->template);
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render(["documents" => $docs, "contactUrl" => $salesforceUrl]);
	}


}