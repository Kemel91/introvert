<?php

require __DIR__ . '/../vendor/autoload.php';

$crm = new \App\AmoCrm();
$crm->set('studentnote@mail.ru', 'studentnote', 'd6ecbdc3d04e3c6b076888355c2a1cc46573ec7b');
$crm->auth();
$leads = $crm->getLeedsWithoutOpenTasks();
try {
    $crm->addTaskForLeads($leads);
} catch (Exception $e) {
    echo $e->getMessage();
}
