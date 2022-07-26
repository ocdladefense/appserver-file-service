<?php

class DocumentsComponent extends Presentation\Component {


	public $active = true;

	private $linkedEntityIds;



	public function __construct($name, $params) {
		
		parent::__construct($name, $params);

		// Need to figure out why the params are not being passed to the "toHtml" function.
		$this->params = $params;
	}


    public function getStyles() {
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

		// An associative array of entity aliases keyed by entity ids.
		$entityData = $this->params["linkedEntityIds"];
		$title = $this->params["title"];


		// Only useful if the table contains docs for a *single linkedEntityId.
		// I.e., we are currently only generating one $salesforceUrl, below.
		// $entityId = array_keys($entityData)[0];

		// $salesforceUrl = cache_get("instance_url") . "/lightning/r/CombinedAttachment/$entityId/related/CombinedAttachments/view";




		$service = new FileService($entityData);

		$docs = $service->getDocuments();

		//If no documents, then display accordingly.
		if(count($docs) == 0) {
			$tpl = new Template("no-records");
			$tpl->addPath(__DIR__ . "/templates");
			return $tpl;
		}

		$tpl = new Template("documents");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render([
			"documents" 	=> $docs,
			"title" 		=> $title,
			"isMyDocs" 		=> false
		]);
	}


}