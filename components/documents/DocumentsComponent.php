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



	public function __construct($name) {
		
		parent::__construct($name);
		$this->template = "form";

		$input = $this->getInput();
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


	/**
	 * Revise this to print the HTML table of documents for *either documents that are mine *or documents that are shared with me.  BUT NOT BOTH.
	 */
	public function toHtml($params = array()) {

		$contactId = current_user()->getContactId();
		//$contactId = "0035b00002fonLKAAY";

		$service = FileService::fromUser(current_user());
		$sharing = $service->getSharePoints();

		array_shift($sharing);


		// Doing this for now!  Not really sure how Im going to deal with this yet.
		// Gonna probably want to remove the contact id from the sharing array.  don't need it in the sharing here anymore.
		// Now just gotta figure out how to not need it in the sharing array in the list function either.
		$sharing = array_filter($sharing, function($item){

			return $item !== "Me";
		});



		// Template depends on the params that get passed into this function; or maybe the $id value that is passed into the "component()" function call.
		$tpl = new Template("my-documents");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render([
			"sharing"		=> $sharing,
			"contactId"		=> $contactId
		]);
	}


}