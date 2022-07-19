<?php
$sharing = FileService::getUserSharePoints($user);


$service = new FileService($sharing);
$service->setContactId($contactId);

$targets = $service->getSharingTargets();

$service->loadAvailableDocuments();


$docs = $service->getDocuments();


//new algo

$service = new FileService($targetIds);

$docs = $service->getDocuments();