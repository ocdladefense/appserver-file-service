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


		if(null != $accountId) {
			$sharing[$accountId] = "Others in {$accountName}";
		}


		foreach($records as $rel) {
			$key 			= $rel["Committee__c"];
			$name 			= $rel["Committee__r"]["Name"];
			$sharing[$key] 	= $name;
		}

		$tpl = new Template("form");
		$tpl->addPath(__DIR__ . "/templates");

		return $tpl->render([
			"sharing"		=> $sharing,
			"contactId"		=> $contactId
		]);
	}


}