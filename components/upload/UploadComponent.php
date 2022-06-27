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




class UploadComponent extends Presentation\Component {


	public $active = true;

	private $linkedEntityIds;



	public function __construct($name) {
		
		parent::__construct($name);
		$this->template = "form";

		$input = $this->getInput();

		// var_dump((array)$input);exit;

		$this->year = $input->year;
	}


    public function getStyles() {
		return array();
        return array(
            "active" => true,
            "href" => module_path() . "/components/search/main.css?bust=001"
        );
    }

    public function getScripts() {
        return array(
            "src" => module_path() . "/components/search/main.js?bust=001"
        );
    }



	public function toHtml($params = array()) {

		$contactId = current_user()->getContactId();
		$sharing = FileService::getSharingData();

		$tpl = new Template("form");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render([
			"sharing"		=> $sharing,
			"contactId"		=> $contactId
		]);
	}


}